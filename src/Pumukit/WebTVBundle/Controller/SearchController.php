<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SearchController extends Controller implements WebTVController
{
    /**
     * @Route("/searchseries")
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function seriesAction(Request $request)
    {
        $templateTitle = 'Series search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_search_series');

        // --- Get Variables ---
        $searchFound = $request->query->get('search');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        // --- END Get Variables --
        // --- Get valid series ids ---
        $validSeries = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')
            ->createStandardQueryBuilder()
            ->distinct('series')
            ->getQuery()
            ->execute()->toArray();
        // --- END Get valid series ids ---
        // --- Create QueryBuilder ---
        $queryBuilder = $this->createSeriesQueryBuilder();
        $queryBuilder = $queryBuilder->field('_id')->in($validSeries);
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, 'public_date');
        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder count --
        $countQuery = clone $queryBuilder;
        $totalObjects = $countQuery->count()->getQuery()->execute();
        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

        // -- Get years array --
        $searchYears = $this->getSeriesYears($queryBuilder);

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return array(
            'type' => 'series',
            'objects' => $pagerfanta,
            'search_years' => $searchYears,
            'number_cols' => $numberCols,
            'total_objects' => $totalObjects,
        );
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     *
     * @param Request  $request
     * @param Tag|null $blockedTag
     * @param bool     $useTagAsGeneral
     *
     * @return array
     *
     * @throws \Exception
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        //Add translated title to breadcrumbs.
        $templateTitle = $this->container->getParameter('menu.search_title') ?: 'Multimedia objects search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle, 'pumukit_webtv_search_multimediaobjects');

        // --- Get Tag Parent for Tag Fields ---
        $parentTag = $this->getParentTag();
        $parentTagOptional = $this->getOptionalParentTag();
        // --- END Get Tag Parent for Tag Fields ---

        $aChildrenTagOptional = array();
        if ($parentTagOptional) {
            foreach ($parentTagOptional->getChildren() as $children) {
                $aChildrenTagOptional[$children->getTitle()] = $children;
            }
            ksort($aChildrenTagOptional);
        }

        // --- Get Variables ---
        $searchFound = $request->query->get('search');
        $tagsFound = $request->query->get('tags');
        $typeFound = $request->query->get('type');
        $durationFound = $request->query->get('duration');
        $startFound = $request->query->get('start');
        $endFound = $request->query->get('end');
        $yearFound = $request->query->get('year');
        $languageFound = $request->query->get('language');
        // --- END Get Variables --
        // --- Create QueryBuilder ---
        $queryBuilder = $this->createMultimediaObjectQueryBuilder();
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->typeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->durationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->languageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral, $parentTag->getCod());
        $queryBuilder = $queryBuilder->sort('record_date', 'desc');
        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));
        $totalObjects = $pagerfanta->getNbResults();
        // --- Query to get existing languages, years, types... ---
        $searchLanguages = $this->getMmobjsLanguages($queryBuilder, $languageFound);
        $searchYears = $this->getMmobjsYears($queryBuilder);
        $searchTypes = $this->getMmobjsTypes($queryBuilder);
        $searchDuration = $this->getMmobjsDuration($queryBuilder);
        $searchTags = $this->getMmobjsTags($queryBuilder);
        $searchWithoutTags = $this->getMmobjsWithoutTags($queryBuilder);
        $searchWithoutTags = $totalObjects - $searchWithoutTags;
        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');
        // --- RETURN ---
        return array(
            'type' => 'multimediaObject',
            'template_title' => $templateTitle,
            'objects' => $pagerfanta,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'children_tag_optional' => $aChildrenTagOptional,
            'tags_found' => $tagsFound,
            'number_cols' => $numberCols,
            'languages' => $searchLanguages,
            'blocked_tag' => $blockedTag,
            'types' => $searchTypes,
            'durations' => $searchDuration,
            'tags' => $searchTags,
            'without_tags' => $searchWithoutTags,
            'search_years' => $searchYears,
            'total_objects' => $totalObjects,
        );
    }

    /**
     * @param $objects
     * @param $page
     *
     * @return Pagerfanta
     */
    protected function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_search');

        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');

        $parentTag = $tagRepo->findOneByCod($searchByTagCod);
        if (!isset($parentTag)) {
            throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)', $searchByTagCod));
        }

        return $parentTag;
    }

    protected function getOptionalParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

        $searchByTagCod = $this->container->getParameter('search.parent_tag_2.cod');
        $parentTagOptional = null;
        if ($searchByTagCod) {
            $parentTagOptional = $tagRepo->findOneByCod($searchByTagCod);
        }

        return $parentTagOptional;
    }

    // ========= queryBuilder functions ==========

    protected function searchQueryBuilder($queryBuilder, $searchFound)
    {
        if ($searchFound != '') {
            $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
        }

        return $queryBuilder;
    }

    protected function typeQueryBuilder($queryBuilder, $typeFound)
    {
        if ($typeFound != '') {
            $queryBuilder->field('tracks.only_audio')->equals($typeFound == 'audio');
        }

        return $queryBuilder;
    }

    protected function durationQueryBuilder($queryBuilder, $durationFound)
    {
        if ($durationFound != '') {
            if ($durationFound == '0') {
                $queryBuilder->field('duration')->equals(0);
            }
            if ($durationFound == '-5') {
                $queryBuilder->field('duration')->gt(0);
                $queryBuilder->field('duration')->lte(300);
            }
            if ($durationFound == '-10') {
                $queryBuilder->field('duration')->gt(300);
                $queryBuilder->field('duration')->lte(600);
            }
            if ($durationFound == '-30') {
                $queryBuilder->field('duration')->gt(600);
                $queryBuilder->field('duration')->lte(1800);
            }
            if ($durationFound == '-60') {
                $queryBuilder->field('duration')->gt(1800);
                $queryBuilder->field('duration')->lte(3600);
            }
            if ($durationFound == '+60') {
                $queryBuilder->field('duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    protected function dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, $dateField = 'record_date')
    {
        if ($yearFound) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', $yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', ($yearFound) + 1));
            $queryBuilder->field($dateField)->gte($start);
            $queryBuilder->field($dateField)->lt($end);
        } else {
            if ($startFound != '') {
                $start = \DateTime::createFromFormat('!Y-m-d', $startFound);
                $queryBuilder->field($dateField)->gt($start);
            }
            if ($endFound != '') {
                $end = \DateTime::createFromFormat('!Y-m-d', $endFound);
                $end->modify('+1 day');
                $queryBuilder->field($dateField)->lt($end);
            }
        }

        return $queryBuilder;
    }

    protected function languageQueryBuilder($queryBuilder, $languageFound)
    {
        if ($languageFound != '') {
            $queryBuilder->field('tracks')->elemMatch(
                $queryBuilder->expr()->field('language')->equals($languageFound)
                    ->field('tags')->equals('display')
            );
        }

        return $queryBuilder;
    }

    protected function tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral = false, $parentTagCod = null)
    {
        if ($parentTagCod && $tagsFound && in_array('unknown', $tagsFound)) {
            $queryBuilder->field('tags.cod')->notIn(array($parentTagCod));
            $pos = array_search('unknown', $tagsFound);
            unset($tagsFound[$pos]);
        }
        if ($blockedTag !== null) {
            $tagsFound[] = $blockedTag->getCod();
        }
        if ($tagsFound !== null) {
            $tagsFound = array_values(array_diff($tagsFound, array('All', '')));
        }
        if (count($tagsFound) > 0) {
            $queryBuilder->field('tags.cod')->all($tagsFound);
        }
        if ($useTagAsGeneral && $blockedTag !== null) {
            $queryBuilder->field('tags.path')->notIn(
                array(new \MongoRegex('/'.preg_quote($blockedTag->getPath()).'.*\|/'))
            );
        }

        return $queryBuilder;
    }

    // ========== END queryBuilder functions =========

    protected function getMmobjsLanguages($queryBuilder = null, $languageFound = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$project' => array('tracks' => 1));
        $pipeline[] = array('$unwind' => '$tracks');
        if ($languageFound) {
            $pipeline[] = array('$match' => array('tracks.language' => $languageFound));
        }

        $pipeline[] = array('$match' => array('tracks.tags' => 'display'));
        $pipeline[] = array('$group' => array('_id' => array('language' => '$tracks.language', 'mmoid' => '$_id')));

        $pipeline[] = array('$group' => array('_id' => '$_id.language', 'count' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('_id' => 1));

        $languageResults = $mmObjColl->aggregate($pipeline);

        $languages = array();
        foreach ($languageResults as $language) {
            if (!isset($languages[$language['_id']])) {
                $languages[$language['_id']] = 0;
            }
            $languages[$language['_id']] += $language['count'];
        }

        return $languages;
    }

    protected function getMmobjsYears($queryBuilder = null)
    {
        return $this->getMmobjsFaceted(array('$year' => '$record_date'), $queryBuilder, $sort = -1);
    }

    protected function getMmobjsDuration($queryBuilder)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$group' => array('_id' => '$duration', 'count' => array('$sum' => 1)));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array(
            0 => 0,
            -5 => 0,
            -10 => 0,
            -30 => 0,
            -60 => 0,
            +60 => 0,
        );

        foreach ($facetedResults as $result) {
            if ($result['_id'] > 60 * 60) {
                $faceted[+60] += $result['count'];
            } elseif (($result['_id'] <= 60 * 60) and ($result['_id'] > 30 * 60)) {
                $faceted[-60] += $result['count'];
            } elseif (($result['_id'] <= 30 * 60) and ($result['_id'] > 10 * 60)) {
                $faceted[-30] += $result['count'];
            } elseif (($result['_id'] <= 10 * 60) and ($result['_id'] > 5 * 60)) {
                $faceted[-10] += $result['count'];
            } elseif (($result['_id'] <= 5 * 60) and ($result['_id'] > 0)) {
                $faceted[-5] += $result['count'];
            } else {
                $faceted[0] += $result['count'];
            }
        }

        return $faceted;
    }

    protected function getMmobjsTags($queryBuilder = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$project' => array('_id' => '$tags.cod'));
        $pipeline[] = array('$unwind' => '$_id');
        $pipeline[] = array('$group' => array('_id' => '$_id', 'count' => array('$sum' => 1)));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array();
        foreach ($facetedResults as $result) {
            $faceted[$result['_id']] = $result['count'];
        }

        return $faceted;
    }

    protected function getMmobjsWithoutTags($queryBuilder = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());
        $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');
        $pipeline = array();
        if ($queryBuilder) {
            $pipeline[] = array('$match' => $queryBuilder->getQueryArray());
        }
        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }
        $pipeline[] = array('$match' => array('tags.cod' => array('$eq' => $searchByTagCod)));
        $pipeline[] = array('$group' => array('_id' => null, 'count' => array('$sum' => 1)));
        $faceted = array();
        $facetedResults = $mmObjColl->aggregate($pipeline);
        foreach ($facetedResults as $result) {
            $faceted[$result['_id']] = $result['count'];
        }

        return reset($faceted);
    }

    protected function getMmobjsTypes($queryBuilder = null)
    {
        return $this->getMmobjsFaceted('$type', $queryBuilder);
    }

    /**
     * @param array $idGroup
     * @param null  $queryBuilder
     *
     * @return array
     */
    protected function getMmobjsFaceted($idGroup, $queryBuilder = null, $sort = 1)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());

        if ($queryBuilder) {
            $pipeline = array(
                array('$match' => $queryBuilder->getQueryArray()),
            );
        } else {
            $pipeline = array(
                array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            );
        }

        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }

        $pipeline[] = array('$group' => array('_id' => $idGroup, 'count' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('_id' => $sort));

        $facetedResults = $mmObjColl->aggregate($pipeline);
        $faceted = array();
        foreach ($facetedResults as $result) {
            $faceted[$result['_id']] = $result['count'];
        }

        return $faceted;
    }

    /**
     * @param null $queryBuilder
     *
     * @return array
     */
    protected function getSeriesYears($queryBuilder = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmObjColl = $dm->getDocumentCollection('PumukitSchemaBundle:Series');
        $mmObjRepo = $dm->getRepository('PumukitSchemaBundle:Series');
        $criteria = $dm->getFilterCollection()->getFilterCriteria($mmObjRepo->getClassMetadata());
        $pipeline = array();
        if ($queryBuilder) {
            $pipeline[] = array('$match' => $queryBuilder->getQueryArray());
        }
        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }
        $pipeline[] = array('$group' => array('_id' => array('$year' => '$public_date'), 'count' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('_id' => 1));
        $yearResults = $mmObjColl->aggregate($pipeline);
        $years = array();
        foreach ($yearResults as $year) {
            $years[$year['_id']] = $year['count'];
        }

        return $years;
    }

    protected function createSeriesQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        return $repo->createQueryBuilder();
    }

    protected function createMultimediaObjectQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        return $repo->createStandardQueryBuilder();
    }
}
