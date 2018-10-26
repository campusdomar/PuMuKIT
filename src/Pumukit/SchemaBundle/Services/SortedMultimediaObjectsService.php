<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;

/**
 * Service to get the multimedia objects of a series sorted.
 */
class SortedMultimediaObjectsService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    /**
     * Reorder multimedia objects of a series using the current sorting attribute.
     *
     * @param Series $series
     */
    public function reorder(Series $series)
    {
        $sorting = $series->getSortingCriteria();

        $mms = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject')
          ->findOrderedBy($series, $sorting);

        $rank = 1;
        foreach ($mms as $mm) {
            $this->dm
                ->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
                ->update()
                ->field('rank')->set($rank++)
                ->field('_id')->equals($mm->getId())
                ->getQuery()
                ->execute();
        }
    }
}
