<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class PumukitCompressPicsCommand extends ContainerAwareCommand
{
    private $dm;
    private $picCompressor;
    private $output;
    private $input;
    private $limitSize = 100;

    protected function configure()
    {
        $this
            ->setName('pumukit:compress:pics')
            ->setDescription('Pumukit compress pics size')
            ->addArgument('limit', InputArgument::OPTIONAL, 'Limit size in KB of pic to compress or not.')
            ->setHelp(<<<'EOT'
                php app/console pumukit:compress:pics
EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->picCompressor = $this->getContainer()->get('pumukitencoder.piccompressor');
        $this->output = $output;
        $this->input = $input;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool|int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($this->limitSize !== ($limit = intval($input->getArgument('limit')))) {
                $this->limitSize = $limit;
            }
        } catch (\Exception $e) {
        }

        $criteria = array(
            'pics' => array('$exists' => true),
            'pics.tags' => array('$nin' => array('poster')),
        );
        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);
        $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findBy($criteria);

        if (!$multimediaObjects && !$series) {
            $output->writeln('No series neither multimedia objects found to compress pics');

            return true;
        }

        try {
            if ($multimediaObjects) {
                $this->compressPicsOfObjects($multimediaObjects);
            }
            if ($series) {
                $this->compressPicsOfObjects($series);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $multimediaObjects
     *
     * @throws \Exception
     */
    private function compressPicsOfObjects($objects)
    {
        foreach ($objects as $object) {
            foreach ($object->getPics() as $pic) {
                if (!$pic->containsTag('poster')) {
                    try {
                        $path = $pic->getPath();
                        $fileOriginalSize = round(filesize($path) / 1024, 1);
                        $this->output->writeln('Checking image "'.$path.'" with original size '.$fileOriginalSize.'KB');
                        $this->picCompressor->compressPic($path);
                        clearstatcache();
                        $fileNewSize = round(filesize($path) / 1024, 1);
                        if ($fileOriginalSize !== $fileNewSize) {
                            $this->output->writeln('Compressed image "'.$path.'" from original size '.$fileOriginalSize.'KB to new size '.$fileNewSize.'KB');
                        } else {
                            $this->output->writeln('Not compressed image "'.$path.'"');
                        }
                    } catch (\Exception $e) {
                        $this->output->writeln('Error in compressing pics: '.$e->getMessage());
                        throw $e;
                    }
                }
            }
        }
    }
}
