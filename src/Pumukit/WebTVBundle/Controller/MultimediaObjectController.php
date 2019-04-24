<?php

namespace Pumukit\WebTVBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

/**
 * Class MultimediaObjectController.
 */
class MultimediaObjectController extends PlayerController implements WebTVControllerInterface
{
    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index", defaults={"show_block": true})
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->doRender($request, $multimediaObject, false);
    }

    /**
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:index', [
            'request' => $request,
            'multimediaObject' => $multimediaObject,
        ]);
    }

    /**
     * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"show_block": true})
     * @Template("PumukitWebTVBundle:MultimediaObject:template.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->doRender($request, $multimediaObject, true);
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     * @param                  $isMagicUrl
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function doRender(Request $request, MultimediaObject $multimediaObject, $isMagicUrl = false)
    {
        $track = null;
        if ($request->query->has('track_id')) {
            $track = $multimediaObject->getTrackById($request->query->get('track_id'));
            if (!$track) {
                throw $this->createNotFoundException();
            }
            if ($track->containsTag('download')) {
                $url = $track->getUrl();
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').'forcedl=1';

                return $this->redirect($url);
            }
        }
        if (in_array($multimediaObject->getStatus(), [1, 2]) || $isMagicUrl) {
            $request->attributes->set('noindex', true);
        }
        $this->updateBreadcrumbs($multimediaObject);
        $editorChapters = $this->getChapterMarks($multimediaObject);
        $intro = $this->get('pumukit_baseplayer.intro')->getIntroForMultimediaObject(
            $request->query->get('intro'),
            $multimediaObject->getProperty('intro')
        );

        return [
            'autostart' => $request->query->get('autostart', 'true'),
            'intro' => $intro,
            'multimediaObject' => $multimediaObject,
            'track' => $track,
            'editor_chapters' => $editorChapters,
            'magic_url' => $isMagicUrl,
            'cinema_mode' => $this->getParameter('pumukit_web_tv.cinema_mode'),
            'fullMagicUrl' => $this->getMagicUrlConfiguration(),
        ];
    }

    /**
     * @Route("/iframe/magic/{secret}", name="pumukit_webtv_multimediaobject_magiciframe", defaults={"show_hide": true})
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    public function magicIframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:magic', [
            'request' => $request,
            'multimediaObject' => $multimediaObject,
        ]);
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_series.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array
     */
    public function seriesAction(MultimediaObject $multimediaObject, Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $limit = $this->container->getParameter('limit_objs_player_series');
        $series = $multimediaObject->getSeries();
        $referer = $request->headers->get('referer');
        $fromSecret = false;
        if (!$series->isHide() && $series->getSecret()) {
            $secretSeriesUrl = $this->generateUrl(
                'pumukit_webtv_series_magicindex',
                ['secret' => $series->getSecret()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $fromSecret = 0 === strpos($referer, $secretSeriesUrl);
        }
        $relatedLink = strpos($referer, 'magic');
        $multimediaObjectMagicUrl = $request->get('magicUrl', false);
        $showMagicUrl = ($fromSecret || $relatedLink || $multimediaObjectMagicUrl);

        $status = ($showMagicUrl) ? [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN] : [MultimediaObject::STATUS_PUBLISHED];
        $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findWithStatus(
            $series,
            $status,
            $limit
        );

        return [
            'series' => $series,
            'multimediaObjects' => $multimediaObjects,
            'showMagicUrl' => $showMagicUrl,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    /**
     * @Template("PumukitWebTVBundle:MultimediaObject:template_related.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $relatedMms = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findRelatedMultimediaObjects($multimediaObject);

        return ['multimediaObjects' => $relatedMms];
    }

    /**
     * @Route("/video/{id}/info", name="pumukit_webtv_multimediaobject_info")
     * @Template("PumukitWebTVBundle:MultimediaObject:template_info.html.twig")
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array
     */
    public function multimediaInfoAction(MultimediaObject $multimediaObject, Request $request)
    {
        $requestRoute = $this->container->get('request_stack')->getMasterRequest()->get('_route');
        $isMagicRoute = false;
        if (false !== strpos($requestRoute, 'magic')) {
            $isMagicRoute = true;
        }

        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');
        $showDownloads = true;
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
        if ($response instanceof Response) {
            $showDownloads = false;
        }
        $editorChapters = $this->getChapterMarks($multimediaObject);

        $fullMagicUrl = $this->getMagicUrlConfiguration();

        return [
            'multimediaObject' => $multimediaObject,
            'editor_chapters' => $editorChapters,
            'showDownloads' => $showDownloads,
            'isMagicRoute' => $isMagicRoute,
            'fullMagicUrl' => $fullMagicUrl,
        ];
    }

    /**
     * @return bool
     */
    private function getMagicUrlConfiguration()
    {
        return $this->container->getParameter('pumukit.full_magic_url');
    }
}
