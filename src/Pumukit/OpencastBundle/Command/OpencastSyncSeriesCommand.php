<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpencastSyncSeriesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('opencast:sync:series')
            ->setDescription('Syncs all series without an "opencast" property with Opencast')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $numSynced = $this->syncSeries();
        $output->writeln(sprintf('Synced %s series', $numSynced));
    }

    protected function syncSeries()
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');
        $allSeries = $seriesRepo->findBy(array('properties.opencast' => array('$exists' => 0)));
        $dispatcher = $this->getContainer()->get('pumukitschema.series_dispatcher');

        $numSynced = 0;
        foreach ($allSeries as $series) {
            $dispatcher->dispatchCreate($series);
            $numSynced += 1;
        }

        return $numSynced;
    }
}
