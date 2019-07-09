<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpencastSyncSeriesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('opencast:sync:series')
            ->setDescription('Syncs all series without an "opencast" property with Opencast')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'If set, the command will only show text output')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Starting command</info>'), OutputInterface::VERBOSITY_VERBOSE);
        $dryRun = $input->getOption('dry-run');
        $numSynced = $this->syncSeries($output, $dryRun);
        $output->writeln(sprintf('<info>Synced %s series</info>', $numSynced));
    }

    protected function syncSeries(OutputInterface $output = null, $dryRun = false)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');
        $allSeries = $seriesRepo->findBy(array('properties.opencast' => array('$exists' => 0)));
        $dispatcher = $this->getContainer()->get('pumukitschema.series_dispatcher');

        $numSynced = 0;
        foreach ($allSeries as $series) {
            $numSynced += 1;
            if ($dryRun == false) {
                $dispatcher->dispatchCreate($series);
            }
            if ($output) {
                $output->writeln(sprintf('<info>- Synced series with id %s </info>(%s)', $series->getId(), $numSynced), OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        return $numSynced;
    }
}
