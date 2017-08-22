<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedSocial;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\NewAdminBundle\Form\Type\EmbeddedEventSessionType;
use Pumukit\NewAdminBundle\Form\Type\SeriesType;
use Pumukit\NewAdminBundle\Form\Type\EventsType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Person;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_CHANNELS')")
 * @Route("/admin/liveevent/")
 */
class EventsController extends Controller
{
    /**
     * @Route("index/", name="pumukit_new_admin_live_event_index")
     * @Template("PumukitNewAdminBundle:LiveEvent:index.html.twig")
     */
    public function indexEventAction()
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $aRoles = $dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        $aPubChannel = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PUBCHANNELS'));
        $aChannels = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($aPubChannel->getId())));

        $statusPub = array(MultimediaObject::STATUS_PUBLISHED => 'Published', MultimediaObject::STATUS_BLOQ => 'Blocked', MultimediaObject::STATUS_HIDE => 'Hidden');

        $object = array();

        return array('object' => $object, 'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'), 'roles' => $aRoles, 'statusPub' => $statusPub, 'pubChannels' => $aChannels);
    }

    /**
     * Create new event with or without serie.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @Route("create/", name="pumukit_new_admin_live_event_create")
     */
    public function createEventAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');

        $factoryService = $this->get('pumukitschema.factory');

        $series = $request->request->get('seriesSuggest') ? $request->request->get('seriesSuggest') : false;

        if (!$series) {
            $series = $factoryService->createSeries($this->getUser());
            $dm->persist($series);
        } else {
            $series = $dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array('_id' => new \MongoId($series)));
        }

        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());
        $multimediaObject->setIsLive(true);

        /* Create default event */
        $event = new EmbeddedEvent();
        $event->setDate(new \DateTime());
        $event->setName($translator->trans('New'), $request->getLocale());
        $event->setCreateSerial(true);
        $dm->persist($event);

        $multimediaObject->setEmbeddedEvent($event);
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirectToRoute('pumukit_new_admin_live_event_index');
    }

    /**
     * List events.
     *
     * @Route("list/event", name="pumukit_new_admin_live_event_list")
     * @Template("PumukitNewAdminBundle:LiveEvent:list.html.twig")
     */
    public function listEventAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $criteria['islive'] = true;
        if ($data = $request->query->get('criteria')) {
            if (!empty($data['name'])) {
                $criteria['embeddedEvent.name.'.$request->getLocale()] = new \MongoRegex('/'.$data['name'].'/i');
            }
            if ($data['date']['from']) {
                $date = strtotime($data['date']['from']);
                $criteria['embeddedEvent.date'] = array('$gte' => new \MongoDate($date));
            }
            if ($data['date']['to']) {
                $date = strtotime($data['date']['to']);
                $criteria['embeddedEvent.date'] = array('$lte' => new \MongoDate($date));
            }
            $this->container->get('session')->set('admin/live/event/criteria', $request->query->get('criteria'));
        }

        $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);

        return array('multimediaObjects' => $multimediaObjects);
    }

    /**
     * Event options .
     *
     * @param $type
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     *
     * @Route("list/options/{type}/{id}", name="pumukit_new_admin_live_event_options")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updatemenu.html.twig")
     */
    public function menuOptionsAction($type, MultimediaObject $multimediaObject)
    {
        try {
            switch ($type) {
                case 'clone':
                    $this->cloneEvent($multimediaObject);
                    break;
                case 'delete':
                    $this->deleteEvent($multimediaObject);
                    $this->container->get('session')->set('eventID', null);
                    break;
                case 'deleteAll':
                    $this->deleteEventAndSeries($multimediaObject);
                    $this->container->get('session')->set('eventID', null);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('status' => $e->getMessage()), 409);
        }

        return new JsonResponse(array());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("delete/selected/", name="pumukit_new_admin_live_event_delete_selected")
     */
    public function deleteSelectedEventsAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $data = $request->request->get('events_checkbox');
        foreach ($data as $multimediaObjectId) {
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array('_id' => new \MongoId($multimediaObjectId)));
            $this->deleteEvent($multimediaObject);
        }

        return new JsonResponse(array());
    }

    /**
     * clone Event and series.
     *
     * @param MultimediaObject $multimediaObject
     */
    private function cloneEvent(MultimediaObject $multimediaObject)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $factoryService = $this->container->get('pumukitschema.factory');

        $cloneMultimediaObject = $factoryService->cloneMultimediaObject($multimediaObject);
        $cloneMultimediaObject->setIsLive(true);

        // Set embeddedEvent
        $dm->persist($cloneMultimediaObject);

        $series = $multimediaObject->getSeries();
        $cloneSeries = clone $series;
        $dm->persist($cloneSeries);

        $cloneMultimediaObject->setSeries($cloneSeries);

        $event = new EmbeddedEvent();
        $event->setDate(new \DateTime());
        $event->setI18nName($multimediaObject->getEmbeddedEvent()->getI18nName());
        $event->setI18nDescription($multimediaObject->getEmbeddedEvent()->getI18nDescription());
        $event->setPlace($multimediaObject->getEmbeddedEvent()->getPlace());
        $event->setDuration($multimediaObject->getEmbeddedEvent()->getDuration());
        $event->setDisplay($multimediaObject->getEmbeddedEvent()->isDisplay());
        $event->setLive($multimediaObject->getEmbeddedEvent()->getLive());
        $event->setUrl($multimediaObject->getEmbeddedEvent()->getUrl());
        $event->setCreateSerial(false);
        $dm->persist($event);

        $cloneMultimediaObject->setEmbeddedEvent($event);
        $dm->persist($cloneMultimediaObject);
        $dm->flush();
    }

    /**
     * Delete event and multimediaObject.
     *
     * @param MultimediaObject $multimediaObject
     */
    private function deleteEvent(MultimediaObject $multimediaObject)
    {
        $factoryService = $this->container->get('pumukitschema.factory');
        $factoryService->deleteMultimediaObject($multimediaObject);
    }

    /**
     * Delete event, multimediaObject and series if serie have just one multimediaObject.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     */
    private function deleteEventAndSeries(MultimediaObject $multimediaObject)
    {
        $factoryService = $this->container->get('pumukitschema.factory');
        $translator = $this->container->get('translator');

        $series = $multimediaObject->getSeries();
        $count = count($series->getMultimediaObjects());
        if (1 === $count) {
            $factoryService->deleteSeries($series);
        } else {
            return new JsonResponse(array('status' => $translator->trans('Series have some multimediaObjects')));
        }
    }

    /**
     * Edit action, opens well with event data.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     *
     * @Route("edit/{id}", name="pumukit_new_admin_live_event_edit")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:edit.html.twig")
     */
    public function editEventAction(MultimediaObject $multimediaObject)
    {
        $this->container->get('session')->set('eventID', $multimediaObject->getId());

        return array('multimediaObject' => $multimediaObject);
    }

    /**
     * Form session to create or edit.
     *
     * @Route("event/{id}", name="pumukit_new_admin_live_event_eventtab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updateevent.html.twig")
     *
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array|jsonResponse
     */
    public function eventAction(Request $request, MultimediaObject $multimediaObject)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new EventsType($translator, $locale), $multimediaObject->getEmbeddedEvent());

        /* Get author and producer */
        $people = false;
        foreach ($multimediaObject->getRoles() as $role) {
            if ($role->getCod() == 'Author') {
                $people['author'] = $role->getPeople()[0]->getName();
            } elseif ($role->getCod() == 'Producer') {
                $people['producer'] = $role->getPeople()[0]->getName();
            }
        }

        $form->handleRequest($request);
        if ($request->getMethod() === 'POST') {
            try {
                $data = $request->request->get('pumukitnewadmin_live_event');

                $event = $multimediaObject->getEmbeddedEvent();
                foreach ($data['i18n_name'] as $language => $value) {
                    $event->setName($value, $language);
                }
                foreach ($data['i18n_description'] as $language => $value) {
                    $event->setDescription($value, $language);
                }
                $event->setPlace($data['place']);
                $event->setDate(new \DateTime($data['date']));
                $event->setDuration($data['duration']);
                $display = isset($data['display']) ? true : false;
                $event->setDisplay($display);
                $externalURL = isset($data['externalURL']) ? $data['externalURL'] : '';
                $event->setUrl($externalURL);

                $live = $dm->getRepository('PumukitLiveBundle:Live')->findOneBy(array('_id' => new \MongoId($data['live'])));
                $event->setLive($live);

                if (isset($data['contact'])) {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setEmail($data['contact']);
                    } else {
                        $embeddedSocial = new EmbeddedSocial();
                        $embeddedSocial->setEmail($data['contact']);
                        $dm->persist($embeddedSocial);
                        $multimediaObject->setEmbeddedSocial($embeddedSocial);
                    }
                } else {
                    if ($multimediaObject->getEmbeddedSocial()) {
                        $multimediaObject->getEmbeddedSocial()->setEmail('');
                    }
                }

                $author = (isset($data['author'])) ? $data['author'] : false;
                if (!empty($author)) {
                    $this->addPeopleData('Author', $author, $multimediaObject, $dm);
                }

                $producer = (isset($data['producer'])) ? $data['producer'] : false;
                if (!empty($producer)) {
                    $this->addPeopleData('Producer', $producer, $multimediaObject, $dm);
                }

                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return new JsonResponse(array('event' => $multimediaObject->getEmbeddedEvent()));
        }

        return array('form' => $form->createView(), 'multimediaObject' => $multimediaObject, 'people' => $people);
    }

    private function addPeopleData($roleCod, $name, $multimediaObject, $dm)
    {
        $role = $dm->getRepository('PumukitSchemaBundle:Role')->findOneBy(array('cod' => $roleCod));

        $personExists = false;
        $roleAndPeople = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findPeopleWithRoleCode($roleCod);
        if ($roleAndPeople) {
            foreach ($roleAndPeople as $person) {
                $personName = $dm->getRepository('PumukitSchemaBundle:Person')->findOneBy(array('_id' => new \MongoId($person)));
                if ($personName == $name) {
                    $personExists = true;
                }
            }
        }

        if (!$personExists) {
            $person = new Person();
            $person->setName($name);
            $dm->persist($person);
            $multimediaObject->addPersonWithRole($person, $role);
        }

        return $multimediaObject;
    }

    /**
     * @Route("series/{id}", name="pumukit_new_admin_live_event_seriestab")
     * @ParamConverter("series", class="PumukitSchemaBundle:Series", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Series:updatemeta.html.twig")
     *
     * @param Request $request
     * @param Series  $series
     *
     * @return array
     */
    public function seriesAction(Request $request, Series $series)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');

        $form = $this->createForm(new SeriesType($translator, $locale, $disablePudenew), $series);

        $exclude_fields = array();
        $show_later_fields = array('pumukitnewadmin_series_i18n_header', 'pumukitnewadmin_series_i18n_footer', 'pumukitnewadmin_series_i18n_line2', 'pumukitnewadmin_series_template');
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');
        if (!$showSeriesTypeTab) {
            $exclude_fields[] = 'pumukitnewadmin_series_series_type';
        }

        return array('form' => $form->createView(), 'series' => $series, 'exclude_fields' => $exclude_fields, 'show_later_fields' => $show_later_fields);
    }

    /**
     * Form session to create or edit.
     *
     * @Route("session/{id}", name="pumukit_new_admin_live_event_sessiontab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updatesession.html.twig")
     *
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array|jsonResponse
     */
    public function sessionAction(Request $request, MultimediaObject $multimediaObject)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new EmbeddedEventSessionType($translator, $locale));

        $form->handleRequest($request);
        if ($request->getMethod() === 'POST') {
            try {
                $data = $form->getData();
                $start = new \DateTime($data->getStart());
                $end = new \DateTime($data->getDuration());
                $duration = $end->getTimestamp() - $start->getTimestamp();
                $notes = $data->getNotes();

                if (isset($request->request->get('pumukitnewadmin_event_session')['id'])) {
                    foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $embeddedEventSession) {
                        if ($embeddedEventSession->getId() == $request->request->get('pumukitnewadmin_event_session')['id']) {
                            $embeddedEventSession->setStart($start);
                            $embeddedEventSession->setDuration($duration);
                            $embeddedEventSession->setNotes($notes);
                        }
                    }
                } else {
                    $embeddedEventSession = new EmbeddedEventSession();

                    $embeddedEventSession->setStart($start);
                    $embeddedEventSession->setDuration($duration);
                    $embeddedEventSession->setNotes($notes);
                    $dm->persist($embeddedEventSession);

                    $multimediaObject->getEmbeddedEvent()->addEmbeddedEventSession($embeddedEventSession);
                }

                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return new JsonResponse(array('sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()));
        }

        return array('multimediaObject' => $multimediaObject, 'form' => $form->createView());
    }

    /**
     * @Route("list/session/{id}", name="pumukit_new_admin_live_event_session_list")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param $id
     *
     * @return array
     */
    public function sessionListAction($id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(new \MongoId($id));

        return array('multimediaObject' => $multimediaObject);
    }

    /**
     * @Route("delete/session/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_session_delete")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param $multimediaObject
     * @param $session_id
     *
     * @return JsonResponse
     */
    public function sessionDeleteAction($multimediaObject, $session_id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(new \MongoId($multimediaObject));
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $multimediaObject->getEmbeddedEvent()->removeEmbeddedEventSession($session);
            }
        }

        $dm->flush();

        return new JsonResponse(array('sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()));
    }

    /**
     * @Route("modal/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_session_modal")
     * @Template("PumukitNewAdminBundle:LiveEvent:updatesessionmodal.html.twig")
     *
     * @param $request
     * @param $multimediaObject
     * @param $session_id
     *
     * @return array
     */
    public function modalSessionAction(Request $request, $multimediaObject, $session_id = false)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new EmbeddedEventSessionType($translator, $locale));

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(new \MongoId($multimediaObject));

        if (!$session_id) {
            return array('form' => $form->createView(), 'multimediaObject' => $multimediaObject);
        }

        $sessionData = '';
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $sessionData = $session;
            }
        }

        if (!empty($sessionData)) {
            $start = $sessionData->getStart();
            $form->get('start')->setData($start);
            $duration = clone $sessionData->getStart();
            $duration->add(new \DateInterval('PT'.$sessionData->getDuration().'S'));
            $form->get('duration')->setData($duration);
            $form->get('notes')->setData($sessionData->getNotes());
        }

        return array('form' => $form->createView(), 'multimediaObject' => $multimediaObject, 'session_id' => $session_id);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("series/suggest/", name="pumukit_new_admin_live_event_series_suggest")
     */
    public function seriesSuggestAction(Request $request)
    {
        $value = $request->query->get('term');

        $aggregate = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection('PumukitSchemaBundle:Series');
        $pipeline = array(array('$match' => array('title.'.$request->getLocale() => new \MongoRegex('/'.$value.'/i'))), array('$group' => array('_id' => array('id' => '$_id', 'title' => '$title'))), array('$limit' => 100));

        $series = $aggregate->aggregate($pipeline)->toArray();

        $result = array();
        foreach ($series as $key => $dataSeries) {
            $result[] = array('id' => (string) $dataSeries['_id']['id'], 'title' => $dataSeries['_id']['title'][$request->getLocale()], 'label' => $dataSeries['_id']['title'][$request->getLocale()], 'value' => $dataSeries['_id']['id'].' - '.$dataSeries['_id']['title'][$request->getLocale()]);
        }

        return new JsonResponse($result);
    }
}
