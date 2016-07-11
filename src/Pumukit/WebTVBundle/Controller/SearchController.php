<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SearchController extends Controller implements WebTVController
{
    /**
     * @Route("/searchseries")
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
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
        $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $queryBuilder = $repository_series->createQueryBuilder();
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
        $searchYears = $this->getSeriesYears();

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return array('type' => 'series',
        'objects' => $pagerfanta,
        'search_years' => $searchYears,
        'number_cols' => $numberCols,
        'total_objects' => $totalObjects);
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        //Add translated title to breadcrumbs.
        $templateTitle = $this->container->getParameter('menu.search_title')?:'Multimedia objects search';
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag ? $blockedTag->getTitle() : $templateTitle, 'pumukit_webtv_search_multimediaobjects');

        // --- Get Tag Parent for Tag Fields ---
        $parentTag = $this->getParentTag();
        $parentTagOptional = $this->getOptionalParentTag();
        // --- END Get Tag Parent for Tag Fields ---

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
        $mmobjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $queryBuilder = $mmobjRepo->createStandardQueryBuilder();
        $queryBuilder = $this->searchQueryBuilder($queryBuilder, $searchFound);
        $queryBuilder = $this->typeQueryBuilder($queryBuilder, $typeFound);
        $queryBuilder = $this->durationQueryBuilder($queryBuilder, $durationFound);
        $queryBuilder = $this->dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound);
        $queryBuilder = $this->languageQueryBuilder($queryBuilder, $languageFound);
        $queryBuilder = $this->tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $queryBuilder = $queryBuilder->sort('record_date','desc');
        // --- END Create QueryBuilder ---

        // --- Execute QueryBuilder count --
        $countQuery = clone $queryBuilder;
        $totalObjects = $countQuery->count()->getQuery()->execute();
        // --- Execute QueryBuilder and get paged results ---
        $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));
        // --- Query to get existing languages ---
        $searchLanguages = $this->get('doctrine_mongodb')
        ->getRepository('PumukitSchemaBundle:MultimediaObject')
        ->createStandardQueryBuilder()
        ->distinct('tracks.language')
        ->getQuery()->execute();
        // --- Query to get oldest date ---
        $firstMmobj = $this->get('doctrine_mongodb')
        ->getRepository('PumukitSchemaBundle:MultimediaObject')
        ->createStandardQueryBuilder()->sort('record_date','asc')->limit(1)
        ->getQuery()->getSingleResult();
        // --- Get years array ---
        $searchYears = $this->getMmobjsYears();

        // -- Init Number Cols for showing results ---
        $numberCols = $this->container->getParameter('columns_objs_search');

        // --- RETURN ---
        return array('type' => 'multimediaObject',
        'template_title' => $templateTitle,
        'objects' => $pagerfanta,
        'parent_tag' => $parentTag,
        'parent_tag_optional' => $parentTagOptional,
        'tags_found' => $tagsFound,
        'number_cols' => $numberCols,
        'languages' => $searchLanguages,
        'blocked_tag' => $blockedTag,
        'search_years' => $searchYears,
        'total_objects' => $totalObjects);
    }

    private function createPager($objects, $page)
    {
        $limit = $this->container->getParameter('limit_objs_search');

        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }


    private function getParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');

        $parentTag = $tagRepo->findOneByCod($searchByTagCod);
        if (!isset($parentTag)) {
            throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)', $searchByTagCod));
        }
        return $parentTag;
    }

    private function getOptionalParentTag()
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

        $searchByTagCod = $this->container->getParameter('search.parent_tag_2.cod');
        $parentTagOptional = null;
        if($searchByTagCod) {
            $parentTagOptional = $tagRepo->findOneByCod($searchByTagCod);
        }

        return $parentTagOptional;
    }

    // ========= queryBuilder functions ==========

    private function searchQueryBuilder($queryBuilder, $searchFound)
    {
        if ($searchFound != '') {
            $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
        }

        return $queryBuilder;
    }

    private function typeQueryBuilder($queryBuilder, $typeFound)
    {
        if ($typeFound != '') {
            $queryBuilder->field('tracks.only_audio')->equals($typeFound == 'Audio');
        }

        return $queryBuilder;
    }

    private function durationQueryBuilder($queryBuilder, $durationFound)
    {
        if ($durationFound != '') {
            if ($durationFound == '-5') {
                $queryBuilder->field('tracks.duration')->lte(300);
            }
            if ($durationFound == '-10') {
                $queryBuilder->field('tracks.duration')->lte(600);
            }
            if ($durationFound == '-30') {
                $queryBuilder->field('tracks.duration')->lte(1800);
            }
            if ($durationFound == '-60') {
                $queryBuilder->field('tracks.duration')->lte(3600);
            }
            if ($durationFound == '+60') {
                $queryBuilder->field('tracks.duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    private function dateQueryBuilder($queryBuilder, $startFound, $endFound, $yearFound, $dateField = 'record_date')
    {
        if( $yearFound ) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01',$yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01',($yearFound)+1));
            $queryBuilder->field($dateField)->gte($start);
            $queryBuilder->field($dateField)->lt($end);
        }
        else {
            if ($startFound != '') {
                $start = \DateTime::createFromFormat('!Y-m-d', $startFound);
                $queryBuilder->field($dateField)->gt($start);
            }
            if ($endFound != '') {
                $end = \DateTime::createFromFormat('!Y-m-d', $endFound);
                $end->modify("+1 day");
                $queryBuilder->field($dateField)->lt($end);
            }
        }

        return $queryBuilder;
    }

    private function languageQueryBuilder($queryBuilder, $languageFound)
    {
        if ($languageFound != '') {
            $queryBuilder->field('tracks.language')->equals($languageFound);
        }

        return $queryBuilder;
    }

    private function tagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral = false)
    {
        $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
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
            $queryBuilder->field('tags.path')->notIn(array(new \MongoRegex('/'.preg_quote($blockedTag->getPath()).'.*\|/')));
        }

        return $queryBuilder;
    }
    // ========== END queryBuilder functions =========

    private function getMmobjsYears()
    {
        $mmObjColl = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $pipeline = array(
            array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            array('$group' => array('_id' => array('$year' => '$record_date'))),
            array('$sort' => array('_id' => 1)),
        );
        $yearResults = $mmObjColl->aggregate($pipeline);
        $years = array();
        foreach($yearResults as $year) {
            $years[] = $year['_id'];
        }
        return $years;
    }

    private function getSeriesYears()
    {
        $mmObjColl = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection('PumukitSchemaBundle:Series');
        $pipeline = array(
            array('$group' => array('_id' => array('$year' => '$public_date'))),
            array('$sort' => array('_id' => 1)),
        );
        $yearResults = $mmObjColl->aggregate($pipeline);
        $years = array();
        foreach($yearResults as $year) {
            $years[] = $year['_id'];
        }
        return $years;
    }
}
