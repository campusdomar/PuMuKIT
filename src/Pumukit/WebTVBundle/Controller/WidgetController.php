<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class WidgetController extends Controller implements WebTVController
{
    /**
     * @Template()
     */
    public function menuAction()
    {
        if ($this->container->hasParameter('pumukit_new_admin.advance_live_event') and $this->container->getParameter('pumukit_new_admin.advance_live_event')) {
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsGroupBy();
            foreach ($events as $key => $event) {
                if ($event['_id'] == 'past') {
                    unset($events[$key]);
                }
            }

            $liveEventTypeSession = true;
        } else {
            $events = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live')->findAll();
            $liveEventTypeSession = false;
        }

        $selected = $this->container->get('request_stack')->getMasterRequest()->get('_route');

        $menuStats = $this->container->getParameter('menu.show_stats');
        $homeTitle = $this->container->getParameter('menu.home_title');
        $announcesTitle = $this->container->getParameter('menu.announces_title');
        $searchTitle = $this->container->getParameter('menu.search_title');
        $mediatecaTitle = $this->container->getParameter('menu.mediateca_title');
        $categoriesTitle = $this->container->getParameter('menu.categories_title');

        return array('live_channels' => array('events' => $events, 'type' => $liveEventTypeSession), 'menu_selected' => $selected, 'menu_stats' => $menuStats,
        'home_title' => $homeTitle,
        'announces_title' => $announcesTitle,
        'search_title' => $searchTitle,
        'mediateca_title' => $mediatecaTitle,
        'categories_title' => $categoriesTitle, );
    }

    /**
     * @Template()
     */
    public function breadcrumbsAction()
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');

        return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template()
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

        $counts = array('series' => $seriesRepo->countPublic(),
                        'mms' => $mmRepo->count(),
                        'hours' => bcdiv($mmRepo->countDuration(), 3600, 2), );

        return array('counts' => $counts);
    }

    /**
     * @Template()
     */
    public function contactAction()
    {
        return array();
    }

    /**
     * @Template("PumukitWebTVBundle:Widget:upcomingliveevents.html.twig")
     */
    public function upcomingLiveEventsAction()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $eventRepo = $dm->getRepository('PumukitLiveBundle:Event');
        $events = $eventRepo->findFutureAndNotFinished(5);

        return array('events' => $events);
    }

    /**
     * @Template()
     */
    public function languageselectAction()
    {
        $array_locales = $this->container->getParameter('pumukit2.locales');
        if (count($array_locales) <= 1) {
            return new Response('');
        }

        return array('languages' => $array_locales);
    }
}
