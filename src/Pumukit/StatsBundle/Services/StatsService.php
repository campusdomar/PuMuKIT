<?php

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class StatsService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoSeries = $this->dm->getRepository('PumukitSchemaBundle:Series');
    }


    public function doGetMostViewed(array $criteria = array(), $days = 30, $limit = 3)
    {
        $ids = array();
        $fromDate = new \DateTime(sprintf("-%s days", $days));
        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate))),
            array('$group' => array('_id' => '$multimediaObject', 'numView' => array('$sum' => 1))),
            array('$sort' => array('numView' => -1)),
            array('$limit' => $limit*2 ), //Get more elements due to tags post-filter.
        );

        $aggregation = $viewsLogColl->aggregate($pipeline);

        $mostViewed = array();

        foreach($aggregation as $element) {
            $ids[] =  $element['_id'];
            $criteria['_id'] = $element['_id'];
            $multimediaObject = $this->repo->findBy($criteria, null, 1);

            if ($multimediaObject) {
                $mostViewed[] = $multimediaObject[0];
                if (0 == --$limit) break;
            }
        }

        if (0 !== $limit) {
            $criteria['_id'] = array('$nin' => $ids);
            return array_merge($mostViewed, $this->repo->findStandardBy($criteria, null, $limit));
        }

        return $mostViewed;
    }

    public function getMostViewed(array $tags, $days = 30, $limit = 3)
    {
        $criteria = array();
        if ($tags) $criteria['tags.cod'] = array('$all' => $tags);
        return $this->doGetMostViewed($criteria, $days, $limit);
    }

    public function getMostViewedUsingFilters($days = 30, $limit = 3)
    {
        $filters = $this->dm->getFilterCollection()->getFilterCriteria($this->repo->getClassMetadata());
        return $this->doGetMostViewed($filters, $days, $limit);
    }



    public function getMmobjsMostViewedByRange(\DateTime $fromDate = null, \DateTime $toDate = null, $limit = 10, array $criteria = array(), $sort = -1)
    {
        $ids = array();
        if(!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime();
        }

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
            array('$group' => array('_id' => '$multimediaObject', 'numView' => array('$sum' => 1))),
            array('$sort' => array('numView' => $sort)),
            array('$limit' => $limit ),
        );
        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();
        foreach($aggregation as $element) {
            $ids[] =  $element['_id'];
            $multimediaObject = $this->repo->find($element['_id']);
            if ($multimediaObject) {
                $mostViewed[] = array('mmobj' => $multimediaObject,
                                      'num_viewed' => $element['numView'],
                );
            }
        }

        return $mostViewed;
    }


    public function getSeriesMostViewedByRange(\DateTime $fromDate = null, \DateTime $toDate = null, $limit = 10, array $criteria = array(), $sort = -1)
    {
        $ids = array();
        if(!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime();
        }

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
            array('$group' => array('_id' => '$series', 'numView' => array('$sum' => 1))),
            array('$sort' => array('numView' => $sort)),
            array('$limit' => $limit ), //Get more elements due to tags post-filter.
        );
        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();
        foreach($aggregation as $element) {
            $ids[] =  $element['_id'];
            $series = $this->repoSeries->find($element['_id']);
            if ($series) {
                $mostViewed[] = array('series' => $series,
                                      'num_viewed' => $element['numView'],
                );
            }
        }

        return $mostViewed;
    }

    public function getTotalViewedGrouped(\DateTime $fromDate = null, \DateTime $toDate = null, $limit = 10, array $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $ids = array();
        if(!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime();
        }

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');
        $mongoProject = $this->getMongoProjectArray($groupBy);
        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
            array('$project' => array('date' => array('$concat' => $mongoProject))),
            array('$group' => array('_id' => '$date',
                                    'numView' => array('$sum' => 1))
            ),
            array('$sort' => array('_id' => $sort)),
            array('$limit' => $limit ),
        );
        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();

        return $aggregation->toArray();
    }

    public function getTotalViewedGroupedByMmobj(\MongoId $mmobjId,\DateTime $fromDate = null, \DateTime $toDate = null, $limit = 10, array $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $ids = array();
        if(!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime();
        }

        $mongoProject = $this->getMongoProjectArray($groupBy);

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('multimediaObject' => $mmobjId,
                                    'date' => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
            array('$project' => array('date' => array('$concat' => $mongoProject))),
            array('$group' => array('_id' => '$date',
                                    'numView' => array('$sum' => 1))
            ),
            array('$sort' => array('_id' => $sort)),
            array('$limit' => $limit ),
        );
        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();

        return $aggregation->toArray();
    }

    public function getTotalViewedGroupedBySeries(\MongoId $seriesId,\DateTime $fromDate = null, \DateTime $toDate = null, $limit = 10, array $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $ids = array();
        if(!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime();
        }

        $mongoProject = $this->getMongoProjectArray($groupBy);

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('series' => $seriesId,
                                    'date' => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
            array('$project' => array('date' => array('$concat' => $mongoProject))),
            array('$group' => array('_id' => '$date',
                                    'numView' => array('$sum' => 1))
            ),
            array('$sort' => array('_id' => $sort)),
            array('$limit' => $limit ),
        );
        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();

        return $aggregation->toArray();
    }


    //TEMP
    private function getMongoProjectArray($groupBy) {
        $mongoProject = array();
        switch($groupBy) {
            case 'hour':
                $mongoProject[] = 'H';
                $mongoProject[] = array('$substr' => array('$date',0,2));
                $mongoProject[] = 'T';
            case 'day':
                $mongoProject[] = array('$substr' => array('$date',8,2));
                $mongoProject[] = '-';
            default: //If it doesn't exists, it's 'month'
            case 'month':
                $mongoProject[] = array('$substr' => array('$date',5,2));
                $mongoProject[] = '-';
            case 'year':
                $mongoProject[] = array('$substr' => array('$date',0,4));
                break;
        }

        return array_reverse($mongoProject);        
    }
}
