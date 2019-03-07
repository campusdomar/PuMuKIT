<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ModulesController.
 */
class ModulesController extends Controller implements WebTVController
{
    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function mostViewedAction()
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $limit = $this->container->getParameter('limit_objs_mostviewed');
        $showLastMonth = $this->container->getParameter('show_mostviewed_lastmonth');
        $translator = $this->get('translator');

        if ($showLastMonth) {
            $objects = $this->get('pumukit_stats.stats')->getMostViewedUsingFilters(30, $limit);
            $title = $translator->trans('Most viewed on the last month');
        } else {
            $objects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findStandardBy(array(), array('numview' => -1), $limit, 0);
            $title = $translator->trans('Most viewed');
        }

        return array(
            'objects' => $objects,
            'objectByCol' => $this->container->getParameter('mostviewed.objects_by_col'),
            'title' => $title,
            'class' => 'mostviewed',
            'show_info' => true,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function recentlyAddedAction()
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Recently added');

        $limit = $this->container->getParameter('limit_objs_recentlyadded');
        $showPudenew = false;

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudenew);

        return array(
            'objects' => $last,
            'objectByCol' => $this->container->getParameter('recentlyadded.objects_by_col'),
            'title' => $title,
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_media.html.twig")
     *
     * @return array
     */
    public function highlightAction()
    {
        $translator = $this->get('translator');
        $title = $translator->trans('Hightlight');

        $limit = $this->container->getParameter('limit_objs_hightlight');
        $showPudenew = $this->container->getParameter('show_latest_with_pudenew');

        $last = $this->get('pumukitschema.announce')->getLast($limit, $showPudenew);

        return array(
            'objects' => $last,
            'objectByCol' => $this->container->getParameter('hightlight.objects_by_col'),
            'class' => 'highlight',
            'title' => $title,
            'show_info' => false,
            'show_more' => false,
        );
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_stats.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function statsAction(Request $request)
    {
        $apcuKey = 'pumukit-stats-'.md5($request->getHost());
        $apcuTTL = 3 * 60 * 60;

        if (extension_loaded('apcu')) {
            $counts = apcu_fetch($apcuKey);
            if ($counts) {
                return array('counts' => $counts);
            }
        }

        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array(
            'series' => $seriesRepo->countPublic(),
            'mms' => $mmRepo->count(),
            'hours' => $mmRepo->countDuration(),
        );

        if (extension_loaded('apcu')) {
            apcu_store($apcuKey, $counts, $apcuTTL);
        }

        return array('counts' => $counts);
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_breadcrumb.html.twig")
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_language.html.twig")
     */
    public function languageAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return array('languages' => $array_locales);
    }

    /**
     * @Template("PumukitWebTVBundle:Modules:widget_categories.html.twig")
     *
     * @param Request $request
     * @param         $title
     * @param         $class
     * @param array   $categories
     * @param int     $cols
     *
     * @return array
     */
    public function categoriesAction(Request $request, $title, $class, array $categories, $cols = 6)
    {
        if (!$categories) {
            throw new NotFoundHttpException('Categories not found');
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $tags = $dm->createQueryBuilder('PumukitSchemaBundle:Tag')
            ->field('cod')->in($categories)
            ->field('display')->equals(true)
            ->sort('title.'.$request->getLocale(), 1)
            ->getQuery()
            ->execute();

        return array(
            'objectByCol' => $cols,
            'objects' => $tags,
            'objectsData' => $categories,
            'title' => $title,
            'class' => $class,
        );
    }

    /**
     * This module represents old categories block of PuMuKIT. Remember fix responsive design ( depends of height of images ).
     *
     * @Template("PumukitWebTVBundle:Modules:widget_block_categories.html.twig")
     *
     * @return array
     */
    public function blockCategoriesAction()
    {
        return array();
    }

    public static $menuResponse = null;
    private $menuTemplate = 'PumukitWebTVBundle:Modules:widget_menu.html.twig';

    /**
     * This module represents old menu block of PuMuKIT ( vertical menu ). This design is just bootstrap panel example.
     *
     * @Template("PumukitWebTVBundle:Modules:widget_menu.html.twig")
     *
     * @return null|Response
     *
     * @throws \Exception
     */
    public function menuAction()
    {
        if (self::$menuResponse) {
            return self::$menuResponse;
        }

        $params = $this->getMenuElements();

        self::$menuResponse = $this->render($this->menuTemplate, $params);

        return self::$menuResponse;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getMenuElements()
    {
        $menuService = $this->get('pumukit_web_tv.menu_service');
        list($events, $channels, $liveEventTypeSession) = $menuService->getMenuEventsElement();
        $selected = $this->get('request_stack')->getMasterRequest()->get('_route');

        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $catalogueTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');

        return array(
            'events' => $events,
            'channels' => $channels,
            'type' => $liveEventTypeSession,
            'menu_selected' => $selected,
            'home_title' => $homeTitle,
            'announces_title' => $announcesTitle,
            'search_title' => $searchTitle,
            'catalogue_title' => $catalogueTitle,
            'categories_title' => $categoriesTitle,
        );
    }
}
