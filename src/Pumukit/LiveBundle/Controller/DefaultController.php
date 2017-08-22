<?php

namespace Pumukit\LiveBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\ContactType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Controller
{
    /**
     * @Route("/live/{id}", name="pumukit_live_id")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function indexAction(Live $live, Request $request)
    {
        $this->updateBreadcrumbs($live->getName(), 'pumukit_live_id', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    /**
     * @Route("/live/iframe/{id}", name="pumukit_live_iframe_id")
     * @Template("PumukitLiveBundle:Default:iframe.html.twig")
     */
    public function iframeAction(Live $live, Request $request, $iframe = true)
    {
        if ($live->getPasswd() && $live->getPasswd() !== $request->get('broadcast_password')) {
            return $this->render($iframe ?
                'PumukitLiveBundle:Default:iframepassword.html.twig' :
                'PumukitLiveBundle:Default:indexpassword.html.twig',
                array('live' => $live, 'invalid_password' => boolval($request->get('broadcast_password')))
            );
        }
        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? floatval($isIE) : 11.0;

        return array(
            'live' => $live,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
        );
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/live/event/{id}", name="pumukit_live_event_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitLiveBundle:Advance:index.html.twig")
     */
    public function indexEventAction(MultimediaObject $multimediaObject, Request $request)
    {
        if ($multimediaObject->getIsLive()) {
            $this->updateBreadcrumbs($multimediaObject->getEmbeddedEvent()->getName(), 'pumukit_live_event_id', array('id' => $multimediaObject->getId()));

            return $this->iframeEventAction($multimediaObject, $request, false);
        } else {
            $series = $multimediaObject->getSeries();
            if (1 === count($series->getMultimediaObjects())) {
                return $this->redirectToRoute('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId()));
            } else {
                return $this->redirectToRoute('pumukit_webtv_series_index', array('id' => $series->getId()));
            }
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     * @param bool             $iframe
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("/live/event/iframe/{id}", name="pumukit_live_event_iframe_id")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitLiveBundle:Advance:iframe.html.twig")
     */
    public function iframeEventAction(MultimediaObject $multimediaObject, Request $request, $iframe = true)
    {
        if ($multimediaObject->getEmbeddedBroadcast()->getType() === embeddedBroadcast::TYPE_PASSWORD && $multimediaObject->getEmbeddedBroadcast()->getPassword() !== $request->get('broadcast_password')) {
            return $this->render($iframe ? 'PumukitLiveBundle:Default:iframepassword.html.twig' : 'PumukitLiveBundle:Default:indexpassword.html.twig', array('live' => $multimediaObject->getEmbeddedEvent(), 'invalid_password' => boolval($request->get('broadcast_password'))));
        }

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $userAgent = $request->headers->get('user-agent');
        $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
        $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
        $isIE = $mobileDetectorService->version('IE');
        $versionIE = $isIE ? floatval($isIE) : 11.0;

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new ContactType($translator, $locale));
        $captchaPublicKey = $this->container->getParameter('captcha_public_key');

        $nowSessions = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNowEventSessions($multimediaObject->getId());
        $nextSessions = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEventSessions($multimediaObject->getId());

        return array(
            'multimediaObject' => $multimediaObject,
            'nowSessions' => $nowSessions,
            'nextSessions' => $nextSessions,
            'captcha_public_key' => $captchaPublicKey,
            'live' => $multimediaObject->getEmbeddedEvent()->getLive(),
            'contact' => $form->createView(),
            'success' => -1,
            'mobile_device' => $mobileDevice,
            'isIE' => $isIE,
            'versionIE' => $versionIE,
            );
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("/live", name="pumukit_live")
     * @Template("PumukitLiveBundle:Default:index.html.twig")
     */
    public function defaultAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitLiveBundle:Live');
        $live = $repo->findOneBy(array());

        if (!$live) {
            throw $this->createNotFoundException('The live channel does not exist');
        }

        $this->updateBreadcrumbs($live->getName(), 'pumukit_live', array('id' => $live->getId()));

        return $this->iframeAction($live, $request, false);
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    /**
     * @Route("/live/playlist/{id}", name="pumukit_live_playlist_id", defaults={"_format": "xml"})
     * @Template("PumukitLiveBundle:Default:playlist.xml.twig")
     */
    public function playlistAction(Live $live)
    {
        $intro = $this->container->hasParameter('pumukit2.intro') ? $this->container->getParameter('pumukit2.intro') : null;

        return array('live' => $live, 'intro' => $intro);
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     *
     * @Route("/event/contact/{id}", name="pumukit_webtv_contact_event")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     */
    public function contactAction($multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        if ('POST' == $request->getMethod() && $this->checkCaptcha($request->request->get('g-recaptcha-response'), $request->getClientIp())) {
            $mail = $this->container->hasParameter('pumukit_notification.sender_email') ? $this->container->getParameter('pumukit_notification.sender_email') : 'noreplay@yourplatform.es';
            $to = $multimediaObject->getEmbeddedSocial()->getEmail();

            $data = $request->request->get('pumukit_multimedia_object_contact');
            $bodyMail = sprintf("Mail desde contacto de %s\n * Correo: %s\n * Nombre: %s\n * Asunto: %s\n ", $request->getUri(), $data['email'], $data['name'], $data['content']);

            $message = \Swift_Message::newInstance();
            $message->setSubject($translator->trans('Contact Live'))->setSender($mail)->setFrom($mail)->setTo($to)->setBody($bodyMail, 'text/plain');
            $sent = $this->get('mailer')->send($message);

            if ($sent == 0) {
                $this->get('logger')->error('Live contact: Error enviando mensaje de: ' + $request->request->get('email'));
            }

            return new JsonResponse(array('success' => true, 'message' => $translator->trans('email send')));
        } else {
            return new JsonResponse(array('success' => false, 'message' => $translator->trans('please verify form data')));
        }
    }

    /**
     * @param string $response $request->request->get('g-recaptcha-response')
     * @param string $remoteip optional $request->getClientIp()
     *
     * @return json
     */
    private function checkCaptcha($response, $remoteip = '')
    {
        $privatekey = $this->container->getParameter('captcha_private_key');

        if ($response == null || strlen($response) == 0) {
            return false;
        }

        $response = $this->_recaptcha_http_post(array('secret' => $privatekey, 'remoteip' => $remoteip, 'response' => $response));

        $res = json_decode($response);

        return $res->success;
    }

    /**
     * Submits an HTTP POST to a reCAPTCHA server.
     *
     * @param array $data
     * @param int port
     *
     * @return array response
     */
    private function _recaptcha_http_post($data)
    {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($verify);

        return $response;
    }
}
