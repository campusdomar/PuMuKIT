<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

abstract class BasePlayerController extends Controller
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return mixed
     */
    abstract public function indexAction(MultimediaObject $multimediaObject, Request $request);

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return mixed
     */
    abstract public function magicAction(MultimediaObject $multimediaObject, Request $request);

    /**
     * @param MultimediaObject $multimediaObject
     * @param Track|null       $track
     */
    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }
}
