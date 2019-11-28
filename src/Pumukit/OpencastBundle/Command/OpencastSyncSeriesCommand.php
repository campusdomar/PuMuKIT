<?php

namespace Pumukit\OpencastBundle\Command;

use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\SchemaBundle\Document\EmbeddedSegment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpencastSyncSeriesCommand extends ContainerAwareCommand
{
    private $output;
    private $input;
    private $dm;
    private $opencastImportService;
    private $logger;
    private $user;
    private $password;
    private $host;
    private $id;
    private $force;
    private $clientService;

    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:sync:series')
            ->setDescription('Synchronize PuMuKIT series in Opencast. This command is not idempotent.')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Opencast user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Opencast password')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Path to selected tracks from PMK using regex')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'ID of multimedia object to import')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(
                <<<'EOT'
            
            
            Command to synchronize PuMuKIT series in Opencast
            
            <info> ** Example ( check and list ):</info>
            
            <comment>php app/console pumukit:opencast:sync:series --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es"</comment>
            <comment>php app/console pumukit:opencast:sync:series --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581"</comment>
            
            This example will be check the connection with these Opencast and list all multimedia objects from PuMuKIT find by regex host.
            
            <info> ** Example ( <error>execute</error> ):</info>
            
            <comment>php app/console pumukit:opencast:sync:series --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --force</comment>
            <comment>php app/console pumukit:opencast:sync:series --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581" --force</comment>

EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $this->opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $this->logger = $this->getContainer()->get('logger');

        $this->user = trim($this->input->getOption('user'));
        $this->password = trim($this->input->getOption('password'));
        $this->host = trim($this->input->getOption('host'));
        $this->id = $this->input->getOption('id');
        $this->force = (true === $this->input->getOption('force'));

        $this->clientService = new ClientService(
            $this->host,
            $this->user,
            $this->password,
            '/engage/ui/watch.html',
            '/admin/index.html#/recordings',
            '/dashboard/index.html',
            false,
            'delete-archive',
            false,
            true,
            null,
            $this->logger,
            null
        );
    }

    /**
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkInputs();

        if ($this->checkOpencastStatus()) {
            $series = $this->getSeries();
            if ($this->force) {
                $this->syncSeries($series);
            } else {
                $this->showSeries($series);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function checkInputs()
    {
        if (!$this->user || !$this->password || !$this->host) {
            throw new \Exception('Please, set values for user, password and host');
        }

        if ($this->id) {
            $validate = preg_match('/^[a-f\d]{24}$/i', $this->id);
            if (0 === $validate || false === $validate) {
                throw new \Exception('Please, use a valid ID');
            }
        }
    }

    /**
     * @return bool
     */
    private function checkOpencastStatus()
    {
        if ($this->clientService->getAdminUrl()) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function getSeries()
    {
        $criteria['opencast'] = ['$exists' => true];

        if ($this->id) {
            $criteria['_id'] = new \MongoId($this->id);
        }

        $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findBy($criteria);

        return $series;
    }

    /**
     * @param $series
     */
    private function syncSeries($series)
    {
        $this->output->writeln(
            [
                '',
                '<info> **** Import segments on multimedia object **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($series),
            ]
        );

        foreach ($series as $serie) {
            $serie->getProperty('opencast');
            if (!$this->clientService->getOpencastSerie($serie)) {
                $this->output->writeln(' Serie: '.$serie->getId().' MediaPackage: -'.$serie->getProperty('opencast').' - no existe en Opencast');
            }
        }
    }

    /**
     * @param $series
     */
    private function showSeries($series)
    {
        $this->output->writeln(
            [
                '',
                '<info> **** Finding Multimedia Objects **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($series),
            ]
        );

        foreach ($series as $serie) {
            if (!$this->clientService->getOpencastSerie($serie)) {
                $this->output->writeln(' Serie: '.$serie->getId().' Opencast Serie: -'.$serie->getProperty('opencast').' - no existe en Opencast');
            } else {
                $this->output->writeln(' Serie: '.$serie->getId().' Opencast Serie: -'.$serie->getProperty('opencast').' - ya existe en Opencast');
            }
        }
    }

    private function createNewSegment($segment)
    {
        $embeddedSegment = new EmbeddedSegment();

        $embeddedSegment->setIndex($segment['index']);
        $embeddedSegment->setTime($segment['time']);
        $embeddedSegment->setDuration($segment['duration']);
        $embeddedSegment->setRelevance($segment['relevance']);
        $embeddedSegment->setHit(boolval($segment['hit']));
        $embeddedSegment->setText($segment['text']);

        $image = '';
        if (isset($segment['previews']['preview']['$'])) {
            $image = $segment['previews']['preview']['$'];
        }
        $embeddedSegment->setPreview($image);

        $this->dm->persist($embeddedSegment);

        return $embeddedSegment;
    }
}
