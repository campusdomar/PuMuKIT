<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
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
 * @Route("liveevent/")
 */
class EventsController extends Controller
{
    private $regex = '/^[0-9a-z]{24}$/';

    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("index/", name="pumukit_new_admin_live_event_index")
     * @Template("PumukitNewAdminBundle:LiveEvent:index.html.twig")
     */
    public function indexEventAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($request->query->get('page')) {
            $this->get('session')->set('admin/live/event/page', $request->query->get('page'));
        }

        $aRoles = $dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        $aPubChannel = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PUBCHANNELS'));
        $aChannels = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(
            array('parent.$id' => new \MongoId($aPubChannel->getId()))
        );

        $statusPub = array(
            MultimediaObject::STATUS_PUBLISHED => 'Published',
            MultimediaObject::STATUS_BLOQ => 'Blocked',
            MultimediaObject::STATUS_HIDE => 'Hidden',
        );

        $object = array();

        return array(
            'object' => $object,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
            'roles' => $aRoles,
            'statusPub' => $statusPub,
            'pubChannels' => $aChannels,
        );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @throws \Exception
     * @Route("create/", name="pumukit_new_admin_live_event_create")
     */
    public function createEventAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $languages = $this->container->getParameter('pumukit2.locales');

        $factoryService = $this->get('pumukitschema.factory');

        $series = $request->request->get('seriesSuggest') ? $request->request->get('seriesSuggest') : false;

        $createSeries = false;
        if (!$series) {
            $series = $factoryService->createSeries($this->getUser());
            $dm->persist($series);
            $createSeries = true;
        } else {
            $series = $dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(
                array('_id' => new \MongoId($series))
            );
        }

        $multimediaObject = $factoryService->createMultimediaObject($series, true, $this->getUser());
        $multimediaObject->setIsLive(true);

        $mmoPicService = $this->get('pumukitschema.mmspic');

        if (!$createSeries) {
            $seriesPics = $series->getPics();
            if (count($seriesPics) > 0) {
                $eventPicSeriesDefault = $series->getPic();
                $mmoPicService->addPicUrl($multimediaObject, $eventPicSeriesDefault->getUrl(), false);
            } else {
                $eventPicSeriesDefault = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_serie_pic');
                $mmoPicService->addPicUrl($multimediaObject, $eventPicSeriesDefault, false);
            }
        }

        /* Create default event */
        $event = new EmbeddedEvent();
        $event->setDate(new \DateTime());

        foreach ($languages as $language) {
            $event->setName($translator->trans('New'), $language);
            $event->setDescription('', $language);
        }

        $event->setCreateSerial(true);
        $dm->persist($event);

        $multimediaObject->setEmbeddedEvent($event);
        $dm->persist($multimediaObject);
        $dm->flush();

        $session = $this->get('session');
        $session->set('admin/live/event/id', $multimediaObject->getId());
        $this->get('session')->set('admin/live/event/page', 1);

        return $this->redirectToRoute('pumukit_new_admin_live_event_index');
    }

    /**
     * List events.
     *
     * @param Request $request
     * @param null    $type
     *
     * @return array
     *
     * @Route("list/event/{type}", name="pumukit_new_admin_live_event_list")
     * @Template("PumukitNewAdminBundle:LiveEvent:list.html.twig")
     */
    public function listEventAction(Request $request, $type = null)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $session = $this->get('session');
        $eventPicDefault = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');
        $page = ($this->get('session')->get('admin/live/event/page')) ?: ($request->query->get('page') ?: 1);

        $criteria['islive'] = true;
        if ($type) {
            $date = new \MongoDate();
            if ('now' === $type) {
                $criteria['embeddedEvent.embeddedEventSession'] = array('$elemMatch' => array(
                    'start' => array('$lte' => $date),
                    'ends' => array('$gte' => $date),
                    ));
            } elseif ('today' === $type) {
                $dateStart = new \DateTime(date('Y-m-d'));
                $dateEnds = new \DateTime(date('Y-m-d 23:59:59'));
                $dateStart = new \MongoDate($dateStart->getTimestamp());
                $dateEnds = new \MongoDate($dateEnds->getTimestamp());
                $criteria['embeddedEvent.embeddedEventSession'] = array('$elemMatch' => array(
                    'start' => array('$gte' => $dateStart),
                    'ends' => array('$lte' => $dateEnds),
                ));
            } else {
                $criteria['embeddedEvent.embeddedEventSession.start'] = array('$gt' => $date);
            }
        } elseif ($request->query->has('criteria')) {
            $data = $request->query->get('criteria');
            $session->set('admin/live/event/dataForm', $data);
            if (!empty($data['name'])) {
                if (preg_match($this->regex, $data['name'])) {
                    $criteria['_id'] = new \MongoId($data['name']);
                } else {
                    $criteria['embeddedEvent.name.'.$request->getLocale()] = new \MongoRegex('/'.$data['name'].'/i');
                }
            }
            if ($data['date']['from'] and $data['date']['to']) {
                $start = strtotime($data['date']['from']);
                $ends = strtotime($data['date']['to'].'23:59:59');

                $criteria['embeddedEvent.embeddedEventSession'] = array('$elemMatch' => array(
                    'start' => array(
                        '$gte' => new \MongoDate($start),
                    ),
                    'ends' => array(
                        '$lte' => new \MongoDate($ends),
                    ), ));
            } else {
                if ($data['date']['from']) {
                    $date = strtotime($data['date']['from']);
                    $criteria['embeddedEvent.embeddedEventSession.start'] = array('$gte' => new \MongoDate($date));
                }
                if ($data['date']['to']) {
                    $date = strtotime($data['date']['to']);
                    $criteria['embeddedEvent.embeddedEventSession.ends'] = array('$lte' => new \MongoDate($date));
                }
            }
        } elseif ($session->has('admin/live/event/criteria')) {
            $criteria = $session->get('admin/live/event/criteria');
        }

        $session->set('admin/live/event/criteria', $criteria);
        $sortField = $session->get('admin/live/event/sort/field', '_id');
        $sortType = $session->get('admin/live/event/sort/type', 'desc');
        $session->set('admin/live/event/sort/field', $sortField);
        $session->set('admin/live/event/sort/type', $sortType);
        if ('embeddedEvent.embeddedEventSession.start' === $sortField) {
            $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);
            $multimediaObjects = $this->reorderMultimediaObjectsByNextNearSession($multimediaObjects, $sortType);
        } else {
            $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(
                $criteria,
                array($sortField => $sortType)
            );
        }

        $adapter = new ArrayAdapter($multimediaObjects);
        $mms = new Pagerfanta($adapter);

        $mms->setMaxPerPage(10)->setNormalizeOutOfRangePages(true);
        if (($mms->getNbPages() < $mms->getCurrentPage()) or ($mms->getNbPages() < $session->get('admin/live/event/page'))) {
            $mms->setCurrentPage(1);
        } else {
            $mms->setCurrentPage($page);
        }

        if ($mms->getNbResults() > 0) {
            $resetCache = true;
            foreach ($mms->getCurrentPageResults() as $result) {
                if ($session->get('admin/live/event/id') == $result->getId()) {
                    $resetCache = false;
                    break;
                }
            }
            if ($resetCache) {
                foreach ($mms->getCurrentPageResults() as $result) {
                    $session->set('admin/live/event/id', $result->getId());
                    break;
                }
            }
        } else {
            $session->remove('admin/live/event/id');
        }

        return array('multimediaObjects' => $mms, 'default_event_pic' => $eventPicDefault);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("add/sorting/", name="pumukit_new_admin_live_event_set_sorting")
     */
    public function addSessionSorting(Request $request)
    {
        $session = $this->get('session');

        if ($request->request->get('field')) {
            $field = $request->request->get('field');
            if ('embeddedEvent.name' === $request->request->get('field')) {
                $field = 'embeddedEvent.name.'.$request->getLocale();
            }
            if ($session->has('admin/live/event/sort/field') and $session->get('admin/live/event/sort/field') === $field) {
                $session->set('admin/live/event/sort/type', (('desc' == $session->get('admin/live/event/sort/type')) ? 'asc' : 'desc'));
            } else {
                $session->set('admin/live/event/sort/type', 'desc');
            }

            $session->set('admin/live/event/sort/field', $field);

            return new JsonResponse(array('success'));
        }

        return new JsonResponse(array('error'));
    }

    /**
     * @return JsonResponse
     *
     * @Route("remove/session/", name="pumukit_newadmin_live_events_reset_session")
     */
    public function removeCriteriaSessionAction()
    {
        $session = $this->get('session');
        $session->remove('admin/live/event/sort/field');
        $session->remove('admin/live/event/sort/type');
        $session->remove('admin/live/event/criteria');
        $session->remove('admin/live/event/dataForm');
        $session->remove('admin/live/event/id');
        $session->remove('admin/live/event/page');

        return new JsonResponse(array('succcess'));
    }

    /**
     * Event options .
     *
     * @param                  $type
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     * @Route("list/options/{type}/{id}", name="pumukit_new_admin_live_event_options")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
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
                    $this->container->get('session')->set('admin/live/event/id', null);
                    break;
                case 'deleteAll':
                    $this->deleteEventAndSeries($multimediaObject);
                    $this->container->get('session')->set('admin/live/event/id', null);
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
     * @Route("delete/selected/", name="pumukit_new_admin_live_event_delete_selected")
     */
    public function deleteSelectedEventsAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $data = $request->request->get('events_checkbox');
        foreach ($data as $multimediaObjectId) {
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
                array('_id' => new \MongoId($multimediaObjectId))
            );
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
     * @Route("edit/{id}", name="pumukit_new_admin_live_event_edit")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:edit.html.twig")
     */
    public function editEventAction(MultimediaObject $multimediaObject)
    {
        $this->container->get('session')->set('admin/live/event/id', $multimediaObject->getId());

        return array('multimediaObject' => $multimediaObject);
    }

    /**
     * Form session to create or edit.
     *
     * @Route("event/{id}", name="pumukit_new_admin_live_event_eventtab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:updateevent.html.twig")
     *
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array|jsonResponse
     *
     * @throws \Exception
     */
    public function eventAction(Request $request, MultimediaObject $multimediaObject)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new EventsType($translator, $locale), $multimediaObject->getEmbeddedEvent());

        $people = array();
        $people['author'] = $multimediaObject->getEmbeddedEvent()->getAuthor();
        $people['producer'] = $multimediaObject->getEmbeddedEvent()->getProducer();

        $form->handleRequest($request);
        if ('POST' === $request->getMethod()) {
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

                if (isset($data['live'])) {
                    $live = $dm->getRepository('PumukitLiveBundle:Live')->findOneBy(
                        array('_id' => new \MongoId($data['live']))
                    );
                    $event->setLive($live);
                }
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

                if (isset($data['author'])) {
                    $multimediaObject->getEmbeddedEvent()->setAuthor($data['author']);
                }

                if (isset($data['producer'])) {
                    $multimediaObject->getEmbeddedEvent()->setProducer($data['producer']);
                }

                if (isset($data['twitter_hashtag']) && $multimediaObject->getEmbeddedSocial()) {
                    $multimediaObject->getEmbeddedSocial()->setTwitterHashtag($data['twitter_hashtag']);
                }
                if (isset($data['twitter_widget_id']) && $multimediaObject->getEmbeddedSocial()) {
                    $multimediaObject->getEmbeddedSocial()->setTwitter($data['twitter_widget_id']);
                }

                $eventsService = $this->container->get('pumukitschema.eventsession');
                $color = $eventsService->validateHtmlColor($data['poster_text_color']);
                $multimediaObject->setProperty('postertextcolor', $color);

                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return new JsonResponse(array('event' => $multimediaObject->getEmbeddedEvent()));
        }

        return array('form' => $form->createView(), 'multimediaObject' => $multimediaObject, 'people' => $people);
    }

    /**
     * @param $roleCod
     * @param $name
     * @param $multimediaObject
     * @param $dm
     *
     * @return mixed
     */
    private function addPeopleData($roleCod, $name, $multimediaObject, $dm)
    {
        $people = $multimediaObject->getPeopleByRoleCod($roleCod);
        $role = $dm->getRepository('PumukitSchemaBundle:Role')->findOneBy(array('cod' => $roleCod));

        if (!$people) {
            $person = new Person();
            $person->setName($name);
            $dm->persist($person);
            $multimediaObject->addPersonWithRole($person, $role);
        } else {
            $personService = $this->get('pumukitschema.person');

            $embeddedPerson = $people[0];
            $person = $personService->findPersonById($embeddedPerson->getId());
            if ($person) {
                $person->setName($name);
                $embeddedPerson->setName($name);
                $personService->updatePerson($person);
            } else {
                throw $this->createNotFoundException('The person does not exist');
            }
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
        $show_later_fields = array(
            'pumukitnewadmin_series_i18n_header',
            'pumukitnewadmin_series_i18n_footer',
            'pumukitnewadmin_series_i18n_line2',
            'pumukitnewadmin_series_template',
        );
        $showSeriesTypeTab = $this->container->hasParameter(
                'pumukit2.use_series_channels'
            ) && $this->container->getParameter('pumukit2.use_series_channels');
        if (!$showSeriesTypeTab) {
            $exclude_fields[] = 'pumukitnewadmin_series_series_type';
        }

        return array(
            'form' => $form->createView(),
            'series' => $series,
            'exclude_fields' => $exclude_fields,
            'show_later_fields' => $show_later_fields,
        );
    }

    /**
     * Form session to create or edit.
     *
     * @Route("session/{id}", name="pumukit_new_admin_live_event_sessiontab")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
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
        if ('POST' === $request->getMethod()) {
            try {
                $data = $form->getData();
                $start = new \DateTime($data->getStart());
                $end = new \DateTime($data->getDuration());
                $duration = $end->getTimestamp() - $start->getTimestamp();
                $notes = $data->getNotes();

                $data = $request->request->get('pumukitnewadmin_event_session');
                if (isset($data['id'])) {
                    foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession(
                    ) as $embeddedEventSession) {
                        if ($embeddedEventSession->getId() == $data['id']) {
                            $embeddedEventSession->setStart($start);
                            $embeddedEventSession->setEnds($end);
                            $embeddedEventSession->setDuration($duration);
                            $embeddedEventSession->setNotes($notes);
                        }
                    }
                } else {
                    $embeddedEventSession = new EmbeddedEventSession();

                    $embeddedEventSession->setStart($start);
                    $embeddedEventSession->setEnds($end);
                    $embeddedEventSession->setDuration($duration);
                    $embeddedEventSession->setNotes($notes);
                    $dm->persist($embeddedEventSession);

                    $multimediaObject->getEmbeddedEvent()->addEmbeddedEventSession($embeddedEventSession);
                }

                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return new JsonResponse(
                array('sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession())
            );
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

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(
            new \MongoId($multimediaObject)
        );
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $multimediaObject->getEmbeddedEvent()->removeEmbeddedEventSession($session);
            }
        }

        $dm->flush();

        return new JsonResponse(array('sessions' => $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession()));
    }

    /**
     * @Route("clone/session/{multimediaObject}/{session_id}", name="pumukit_new_admin_live_event_clone_session")
     * @Template("PumukitNewAdminBundle:LiveEvent:sessionlist.html.twig")
     *
     * @param $multimediaObject
     * @param $session_id
     *
     * @return JsonResponse
     */
    public function sessionCloneAction($multimediaObject, $session_id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(
            new \MongoId($multimediaObject)
        );
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($session->getId() == $session_id) {
                $newSession = new EmbeddedEventSession();
                $newSession->setDuration($session->getDuration());
                $newSession->setNotes($session->getNotes());
                $date = clone $session->getStart();
                $dateEnds = clone $session->getEnds();
                $date->add(new \DateInterval('P1D'));
                $dateEnds->add(new \DateInterval('P1D'));
                $newSession->setStart($date);
                $newSession->setEnds($dateEnds);
                $dm->persist($newSession);
                $multimediaObject->getEmbeddedEvent()->addEmbeddedEventSession($newSession);
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

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(
            new \MongoId($multimediaObject)
        );

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

        return array(
            'form' => $form->createView(),
            'multimediaObject' => $multimediaObject,
            'session_id' => $session_id,
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @Route("series/suggest/", name="pumukit_new_admin_live_event_series_suggest")
     */
    public function seriesSuggestAction(Request $request)
    {
        $value = $request->query->get('term');

        $aggregate = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection('PumukitSchemaBundle:Series');
        $pipeline = array(
            array('$match' => array('title.'.$request->getLocale() => new \MongoRegex('/'.$value.'/i'))),
            array('$group' => array('_id' => array('id' => '$_id', 'title' => '$title'))),
            array('$limit' => 100),
        );

        $series = $aggregate->aggregate($pipeline)->toArray();

        $result = array();
        foreach ($series as $key => $dataSeries) {
            $result[] = array(
                'id' => (string) $dataSeries['_id']['id'],
                'title' => $dataSeries['_id']['title'][$request->getLocale()],
                'label' => $dataSeries['_id']['title'][$request->getLocale()],
                'value' => $dataSeries['_id']['id'].' - '.$dataSeries['_id']['title'][$request->getLocale()],
            );
        }

        return new JsonResponse($result);
    }

    /**
     * @return array
     *
     * @Route("change/series/{multimediaObject}", name="pumukitnewadmin_live_event_change_series")
     * @Template("PumukitNewAdminBundle:LiveEvent:changeSeries.html.twig")
     */
    public function seriesChangeModalAction($multimediaObject = null)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        if (isset($multimediaObject)) {
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array('_id' => new \MongoId($multimediaObject)));

            return array('multimediaObject' => $multimediaObject);
        }

        return array();
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     *
     * @Route("edit/series/{multimediaObject}", name="pumukitnewadmin_live_event_edit_series")
     */
    public function seriesChangeAction(Request $request, MultimediaObject $multimediaObject)
    {
        $series = $request->request->get('seriesSuggest');
        if ($series) {
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $series = $dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array('_id' => new \MongoId($series)));
            if ($series) {
                $multimediaObject->setSeries($series);
                $dm->flush();

                return new JsonResponse(array('success'));
            }

            return new JsonResponse(array('error'));
        }

        return new JsonResponse(array('error'));
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     * @Route("show/{id}", name="pumukit_new_admin_live_event_show")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:LiveEvent:show.html.twig")
     */
    public function showAction(MultimediaObject $multimediaObject)
    {
        return array('multimediaObject' => $multimediaObject);
    }

    /**
     * @param $multimediaObjects
     * @param $sortType
     *
     * @return array
     */
    private function reorderMultimediaObjectsByNextNearSession($multimediaObjects, $sortType)
    {
        $date = new \DateTime();

        usort($multimediaObjects, function ($a, $b) use ($sortType, $date) {
            $validSessionA = null;
            foreach ($a->getEmbeddedEvent()->getEmbeddedEventSession() as $sessionA) {
                if ($sessionA->getStart() > $date) {
                    $validSessionA = $sessionA->getStart()->getTimestamp();
                    break;
                }
            }
            $validSessionB = null;
            foreach ($b->getEmbeddedEvent()->getEmbeddedEventSession() as $sessionB) {
                if ($sessionB->getStart() > $date) {
                    $validSessionB = $sessionB->getStart()->getTimestamp();
                    break;
                }
            }

            if (!$validSessionA && !$validSessionB) {
                return 0;
            }

            if (!$validSessionA && $validSessionB) {
                return 1;
            }

            if ($validSessionA && !$validSessionB) {
                return -1;
            }

            if ($validSessionA && $validSessionB) {
                if ('desc' == $sortType) {
                    return ($validSessionA < $validSessionB) ? 1 : -1;
                } else {
                    return ($validSessionA < $validSessionB) ? -1 : 1;
                }
            }
        });

        return $multimediaObjects;
    }
}
