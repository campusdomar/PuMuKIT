<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class BasePlaylistController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true})
     *
     * @param Request $request
     * @param Series  $series
     *
     * @return mixed
     */
    abstract public function indexAction(Request $request, Series $series);
}
