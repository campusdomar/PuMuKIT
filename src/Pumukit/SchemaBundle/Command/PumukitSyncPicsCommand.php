<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitSyncPicsCommand extends ContainerAwareCommand
{
    private $picExtractorService;

    protected function configure()
    {
        $this
            ->setName('pumukit:sync:pics')
            ->setDescription('Sync Pics path with URL value')
            ->setHelp(<<<EOT

Sync corrupted database of Pics path with the value of the URL.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->picExtractorService = $this->getContainer()->get('pumukitencoder.picextractor');
        $this->syncPics($input, $output);
    }

    private function syncPics(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $mmRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:MultimediaObject");
        $multimediaObjects = $mmRepo->findAll();
        foreach ($multimediaObjects as $multimediaObject) {
            $track = $multimediaObject->getTrackWithTag('master');
            foreach ($multimediaObject->getPics() as $pic) {
                $pic = $this->fixPath($pic, $track);
            }
            $dm->persist($multimediaObject);
        }
        $dm->flush();
    }

    private function fixPath($pic, $track)
    {
        $urlFilename = pathinfo($pic->getUrl(), PATHINFO_FILENAME);
        $pathFilename = pathinfo($pic->getPath(), PATHINFO_FILENAME);
        if ($urlFilename != $pathFilename) {
            $fixedPathFilename = str_replace($pathFilename, $urlFilename, $pic->getPath());
            $pic->setPath($fixedPathFilename);
            $pic = $this->fixDimensions($pic, $track);
        }

        return $pic;
    }

    private function fixDimensions($pic, $track)
    {
        if ($pic && $track) {
            $dimensions = $this->picExtractorService->getHeightAndWidth($track);
            $pic->setHeight($dimensions['height']);
            $pic->setWidth($dimensions['width']);
        }

        return $pic;
    }
}
