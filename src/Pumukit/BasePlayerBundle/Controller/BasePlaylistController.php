<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\WebTVBundle\Controller\WebTVController;

abstract class BasePlaylistController extends Controller implements WebTVController
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     */
    abstract public function indexAction(Series $series, Request $request);
}
