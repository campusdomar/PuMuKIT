<?php

namespace Pumukit\WebTVBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\WebTVBundle\Controller\WebTVControllerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FilterListener.
 */
class FilterListener
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * FilterListener constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        dump('xxxxxxxxxxX');
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');
        $isFilterActivated = (!isset($routeParams['filter']) || $routeParams['filter']);

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         * From Symfony Docs: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
         */
        $controller = $event->getController();
        dump($event->getController());
        if (!is_array($controller)) {
            dump('fuera');
            return;
        }

        //@deprecated: PuMuKIT 2.2: This logic will be removed eventually. Please implement the interface WebTVBundleController to use the filter.
        $deprecatedCheck = false && (false !== strpos($req->attributes->get('_controller'), 'WebTVBundle'));

        if (($controller[0] instanceof WebTVControllerInterface /*deprecated*/ || $deprecatedCheck)
            && $isFilterActivated) {
            if ($this->dm->getFilterCollection()->isEnabled('frontend')) {
                dump('esta activo el filtro');
                return;
            }

            dump('hola2');
            $filter = $this->dm->getFilterCollection()->enable('frontend');

            if (isset($routeParams['show_hide']) && $routeParams['show_hide']) {
                $filter->setParameter('status', ['$in' => [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE]]);
            } elseif (isset($routeParams['show_block']) && $routeParams['show_block']) {
                $filter->setParameter(
                    'status',
                    ['$in' => [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE, MultimediaObject::STATUS_BLOCKED]]
                );
            } else {
                $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
            }

            if (!isset($routeParams['track']) || $routeParams['track']) {
                $filter->setParameter('display_track_tag', 'display');
            }

            if (!isset($routeParams['no_channels']) || !$routeParams['no_channels']) {
                $filter->setParameter('pub_channel_tag', 'PUCHWEBTV');
            }

            $filter->setParameter('islive', false);
        } else {
            dump('entra');
        }
    }
}
