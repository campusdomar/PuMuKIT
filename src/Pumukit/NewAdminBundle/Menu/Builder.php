<?php

namespace Pumukit\NewAdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Pumukit\SchemaBundle\Security\Permission;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        // Add translations in src/Pumukit/NewAdminBundle/Resource/translations/NewAdminBundle.locale.yml
        $authorizationChecker = $this->container->get('security.authorization_checker');
        $masterRequest = $this->container->get('request_stack')->getMasterRequest();
        $showImporterTab = $this->container->hasParameter('pumukit_opencast.show_importer_tab') && $this->container->getParameter('pumukit_opencast.show_importer_tab');
        $showDashboardTab = $this->container->getParameter('pumukit2.show_dashboard_tab');
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');

        if ($showDashboardTab && false !== $authorizationChecker->isGranted(Permission::ACCESS_DASHBOARD)) {
            $menu->addChild('Dashboard', array('route' => 'pumukit_newadmin_dashboard_index'));
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_WIZARD_UPLOAD) &&
            $authorizationChecker->isGranted(Permission::SHOW_WIZARD_MENU) &&
            !$authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $class = ($masterRequest && (0 === strpos($masterRequest->attributes->get('_route'), 'pumukitwizard_default_'))) ? 'active' : '';
            $menu->addChild('Upload new videos', array('route' => 'pumukitwizard_default_series', 'attributes' => array('class' => $class)));
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_SERIES)) {
            $mediaManager = $menu->addChild('Media Manager');
            $series = $mediaManager->addChild('Series', array('route' => 'pumukitnewadmin_series_index'));
            $series->addChild('Multimedia', array('route' => 'pumukitnewadmin_mms_index'));
            $series->setDisplayChildren(false);

            $activeMmsListAll = $this->container->getParameter('pumukit2.show_mms_list_all_menu');
            if ($activeMmsListAll) {
                $mmslist = $mediaManager->addChild($this->container->getParameter('pumukit_new_admin.multimedia_object_label'), array('route' => 'pumukitnewadmin_mms_indexall'));
                $mmslist->addChild('Multimedia', array('route' => 'pumukitnewadmin_mms_indexall'));
                $mmslist->setDisplayChildren(false);
            }

            $unesco = $mediaManager->addChild('UNESCO catalogue', array('route' => 'pumukitnewadmin_unesco_index'));
            $unesco->setDisplayChildren(false);
        }
        if ($authorizationChecker->isGranted(Permission::ACCESS_EDIT_PLAYLIST)) {
            if (!isset($mediaManager)) {
                $mediaManager = $menu->addChild('Media Manager');
            }
            $playlists = $mediaManager->addChild('Moodle Playlists', array('route' => 'pumukitnewadmin_playlist_index'));
            $playlists->addChild('Multimedia', array('route' => 'pumukitnewadmin_playlistmms_index'));
            $playlists->setDisplayChildren(false);
        }

        if ($authorizationChecker->isGranted('ROLE_ACCESS_STATS')) {
            $stats = $menu->addChild('Stats');
            $stats->addChild('Series', array('route' => 'pumukit_stats_series_index'));
            $stats->addChild('Multimedia Objects', array('route' => 'pumukit_stats_mmobj_index'));

            //TODO: Use VOTERS: https://github.com/KnpLabs/KnpMenu/blob/master/doc/01-Basic-Menus.markdown#the-current-menu-item
            //Voters are a way to check if a menu item is the current one. Now we are just checking the routes and setting the Current element manually
            $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');
            $statsRoutes = array('pumukit_stats_series_index', 'pumukit_stats_mmobj_index', 'pumukit_stats_series_index_id', 'pumukit_stats_mmobj_index_id', 'pumukit_stats_versions');
            if (in_array($route, $statsRoutes)) {
                $stats->setCurrent(true);
            }
        }

        $advanceLiveEvent = $this->container->hasParameter('pumukit_new_admin.advance_live_event') ? $this->container->getParameter('pumukit_new_admin.advance_live_event') : false;
        if ($advanceLiveEvent) {
            if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                $live = $menu->addChild('Live management');
                if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
                    $live->addChild('Live Events', array('route' => 'pumukit_new_admin_live_event_index'));
                }
                if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                    $live->addChild('Channel configuration', array('route' => 'pumukitnewadmin_live_index'));
                }
            }
        } else {
            if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS) || $authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                $live = $menu->addChild('Live');
                if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_CHANNELS)) {
                    $live->addChild('Live Channels', array('route' => 'pumukitnewadmin_live_index'));
                }
                if ($authorizationChecker->isGranted(Permission::ACCESS_LIVE_EVENTS)) {
                    $live->addChild('Live Events', array('route' => 'pumukitnewadmin_event_index'));
                }
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_JOBS)) {
            $menu->addChild('Encoder jobs', array('route' => 'pumukit_encoder_info'));
        }
        if (($authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU)) ||
            $authorizationChecker->isGranted(Permission::ACCESS_TAGS) ||
            $authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
            $tables = $menu->addChild('Tables');
            if ($authorizationChecker->isGranted(Permission::ACCESS_PEOPLE) && $authorizationChecker->isGranted(Permission::SHOW_PEOPLE_MENU)) {
                $tables->addChild('People', array('route' => 'pumukitnewadmin_person_index'));
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_TAGS)) {
                $tables->addChild('Tags', array('route' => 'pumukitnewadmin_tag_index'));
            }

            $menuPlaceAndPrecinct = $this->container->hasParameter('pumukit_new_admin.show_menu_place_and_precinct') ? $this->container->getParameter('pumukit_new_admin.show_menu_place_and_precinct') : false;
            if ($authorizationChecker->isGranted(Permission::ACCESS_TAGS) && $menuPlaceAndPrecinct) {
                $tables->addChild('Places and precinct', array('route' => 'pumukitnewadmin_places_index'));
            }

            if ($showSeriesTypeTab && $authorizationChecker->isGranted(Permission::ACCESS_SERIES_TYPES)) {
                $tables->addChild('Series types', array('route' => 'pumukitnewadmin_seriestype_index'));
            }
        }

        if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS) ||
            $authorizationChecker->isGranted(Permission::ACCESS_GROUPS) ||
            $authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES) ||
            $authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
            $management = $menu->addChild('Management');
            if ($authorizationChecker->isGranted(Permission::ACCESS_ADMIN_USERS)) {
                $management->addChild('Admin users', array('route' => 'pumukitnewadmin_user_index'));
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_GROUPS)) {
                $management->addChild('Groups', array('route' => 'pumukitnewadmin_group_index'));
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_PERMISSION_PROFILES)) {
                $management->addChild('Permission profiles', array('route' => 'pumukitnewadmin_permissionprofile_index'));
            }
            if ($authorizationChecker->isGranted(Permission::ACCESS_ROLES)) {
                $management->addChild('Roles', array('route' => 'pumukitnewadmin_role_index'));
            }
        }

        $tools = null;
        if ($showImporterTab && $authorizationChecker->isGranted('ROLE_ACCESS_IMPORTER')) {
            $tools = $menu->addChild('Tools');
            $tools->addChild('OC-Importer', array('route' => 'pumukitopencast'));
        }

        if ($authorizationChecker->isGranted('ROLE_ACCESS_SERIES_STYLE')) {
            if (!$tools) {
                $tools = $menu->addChild('Tools');
            }
            $tools->addChild('Series style', array('route' => 'pumukit_newadmin_series_styles'));
        }

        foreach ($this->container->get('pumukitnewadmin.menu')->items() as $item) {
            if ($authorizationChecker->isGranted($item->getAccessRole())) {
                if (!$tools) {
                    $tools = $menu->addChild('Tools');
                }
                $tools->addChild($item->getName(), array('route' => $item->getUri()));
            }
        }

        return $menu;
    }
}
