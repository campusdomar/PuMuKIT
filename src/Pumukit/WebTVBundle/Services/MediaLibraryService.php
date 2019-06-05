<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class MediaLibraryService.
 */
class MediaLibraryService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;
    /**
     * @var PaginationService
     */
    private $paginationService;

    /**
     * MediaLibraryService constructor.
     *
     * @param DocumentManager   $documentManager
     * @param PaginationService $paginationService
     */
    public function __construct(DocumentManager $documentManager, PaginationService $paginationService)
    {
        $this->documentManager = $documentManager;
        $this->paginationService = $paginationService;
    }

    /**
     * @param array $criteria
     * @param       $sort
     * @param       $locale
     * @param array $options
     *
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getMediaLibrary(array $criteria, $sort, $locale, array $options)
    {
        $series_repo = $this->documentManager->getRepository('PumukitSchemaBundle:Series');
        $tags_repo = $this->documentManager->getRepository('PumukitSchemaBundle:Tag');

        $result = [];

        $aggregatedNumMmobjs = $this->documentManager->getRepository('PumukitSchemaBundle:MultimediaObject')->countMmobjsBySeries();

        switch ($sort) {
            case 'alphabetically':
                $sortField = 'title.'.$locale;
                $series = $series_repo->findBy($criteria, [$sortField => 1]);

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = mb_substr(trim($serie->getTitle()), 0, 1, 'UTF-8');
                    if (!isset($result[$key])) {
                        $result[$key] = [];
                    }
                    $result[$key][] = $serie;
                }
                break;
            case 'date':
                $sortField = 'public_date';
                $series = $series_repo->findBy($criteria, [$sortField => -1]);

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = $serie->getPublicDate()->format('m/Y');
                    if (!isset($result[$key])) {
                        $result[$key] = [];
                    }

                    $title = $serie->getTitle();
                    if (!isset($result[$key][$title])) {
                        $result[$key][$title] = $serie;
                    } else {
                        $result[$key][$title.rand()] = $serie;
                    }
                }

                array_walk(
                    $result,
                    function (&$e) {
                        ksort($e);

                        return array_values($e);
                    }
                );

                break;
            case 'tags':
                $p_cod = $options['tag'];
                $parentTag = $tags_repo->findOneBy(['cod' => $p_cod]);
                if (!isset($parentTag)) {
                    break;
                }
                $tags = $parentTag->getChildren();

                foreach ($tags as $tag) {
                    if ($tag->getNumberMultimediaObjects() < 1) {
                        continue;
                    }
                    $key = $tag->getTitle();

                    $sortField = 'title.'.$locale;
                    $seriesQB = $series_repo->createBuilderWithTag($tag, [$sortField => 1]);
                    if ($criteria) {
                        $seriesQB->addAnd($criteria);
                    }
                    $series = $seriesQB->getQuery()->execute();

                    if (!$series) {
                        continue;
                    }

                    foreach ($series as $serie) {
                        if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                            continue;
                        }

                        if (!isset($result[$key])) {
                            $result[$key] = [];
                        }
                        $result[$key][] = $serie;
                    }
                }
                break;
        }

        $result = $this->paginationService->createArrayAdapter($result, $options['page'], 16);

        return [
            $result,
            $aggregatedNumMmobjs,
        ];
    }
}
