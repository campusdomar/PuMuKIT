<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\NewAdminBundle\Controller\NewAdminController;

/**
 * @Route("/api/media")
 */
class APIController extends Controller implements NewAdminController
{
    /**
     * @Route("/stats.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function statsAction(Request $request)
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');
        $liveRepo = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live');
        $serializer = $this->get('serializer');

        $totalSeries = $seriesRepo->countPublic();
        $totalMmobjs = $mmRepo->count();
        $totalHours = bcdiv($mmRepo->countDuration(), 3600, 2);
        $totalLiveChannels = $liveRepo->createQueryBuilder()
                                      ->count()
                                      ->getQuery()
                                      ->execute();

        $counts = array(
            'series' => $totalSeries,
            'mms' => $totalMmobjs,
            'hours' => $totalHours,
            'live_channels' => $totalLiveChannels,
        );

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function multimediaObjectsAction(Request $request)
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $serializer = $this->get('serializer');

        $limit = $request->get('limit');
        $page = $request->get('page');
        $skip = $request->get('skip');
        try {
            $criteria = $this->getMultimediaObjectCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $data = array('error' => sprintf('Invalid criteria (%s)', $e->getMessage()));
            $data = $serializer->serialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: array();
        $prototypes = $request->get('prototypes') ?: false;

        if (!$limit || $limit > 100) {
            $limit = 100;
        }
        if ($page && $page > 0) {
            $limit = $limit ?: 10;
            $skip = $limit * ($page - 1);
        } else {
            $page = null;
        }

        if ($prototypes) {
            $qb = $mmRepo->createQueryBuilder();
        } else {
            $qb = $mmRepo->createStandardQueryBuilder();
        }

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_mmobjs = clone $qb;
        $qb_mmobjs = $qb_mmobjs->limit($limit)
                               ->skip($skip)
                               ->sort($sort);

        $total = $qb->count()->getQuery()->execute();
        $mmobjs = $qb_mmobjs->getQuery()->execute()->toArray();

        $counts = array(
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'mmobjs' => $mmobjs,
        );

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesAction(Request $request)
    {
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $serializer = $this->get('serializer');
        $limit = $request->get('limit');
        $page = $request->get('page');
        $skip = $request->get('skip');

        try {
            $criteria = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $data = array('error' => sprintf('Invalid criteria (%s)', $e->getMessage()));
            $data = $serializer->serialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: array();
        $prototypes = $request->get('prototypes') ?: false;

        if (!$limit || $limit > 100) {
            $limit = 100;
        }
        if ($page && $page > 0) {
            $limit = $limit ?: 10;
            $skip = $limit * ($page - 1);
        } else {
            $page = null;
        }

        $qb = $seriesRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->skip($skip)
                               ->sort($sort);

        $total = $qb->count()->getQuery()->execute();
        $series = $qb_series->getQuery()->execute()->toArray();

        $counts = array(
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'series' => $series,
        );

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/live.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function liveAction(Request $request)
    {
        $liveRepo = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live');
        $serializer = $this->get('serializer');

        $limit = $request->get('limit');
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        try {
            $criteria = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $data = array('error' => sprintf('Invalid criteria (%s)', $e->getMessage()));
            $data = $serializer->serialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: array();

        $qb = $liveRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->sort($sort);

        $qb_live = clone $qb;

        $total = $qb->count()->getQuery()->execute();
        $live = $qb_live->getQuery()->execute()->toArray();

        $counts = array(
            'total' => $total,
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'live' => $live,
        );

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/locales.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function localesAction(Request $request)
    {
        $serializer = $this->get('serializer');

        $locales = $this->container->getParameter('pumukit2.locales');
        $data = $serializer->serialize($locales, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * Custom case for multimediaobject croteria. For Backward Compatibility (BC).
     *
     * @see APIController::getCriteria
     */
    private function getMultimediaObjectCriteria($row, $json)
    {
        if ($json) {
            $criteria = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
        } else {
            $criteria = (array) $row;

            if (isset($criteria['status'])) {
                if (is_array($criteria['status'])) {
                    $newStatus = array();
                    foreach ($criteria['status'] as $k => $v) {
                        $newStatus[$k] = array_map('intval', (array) $v);
                    }
                    $criteria['status'] = $newStatus;
                } else {
                    $criteria['status'] = (int) $criteria['status'];
                }
            }
        }

        return $criteria;
    }

    /**
     * Get criteria to filter objects from the requets.
     *
     * JSON criteria has priority over row criteria.
     *
     * @param $row
     * @param $json
     *
     * @return array
     */
    private function getCriteria($row, $json)
    {
        if ($json) {
            $criteria = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
        } else {
            $criteria = (array) $row;
        }

        return $criteria;
    }
}
