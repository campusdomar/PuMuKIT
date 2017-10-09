<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pumukit\NewAdminBundle\Form\Type\UNESCOBasicType;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/unesco")
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UNESCOController extends Controller implements NewAdminController
{
    public static $unescoTags = array(
        'Health Sciences' => array(
            'U310000',
            'U240000',
            'U320000',
            'U610000',
        ),
        'Technology' => array(
            'U330000',
        ),
        'Sciences' => array(
            'U210000',
            'U250000',
            'U220000',
            'U120000',
            'U230000',
        ),
        'Legal' => array(
            'U530000',
            'U560000',
            'U590000',
            'U520000',
            'U580000',
            'U630000',
        ),
        'Humanities' => array(
            'U510000',
            'U620000',
            'U710000',
            'U720000',
            'U540000',
            'U550000',
            'U570000',
            'U110000',
        ),
    );

    /**
     * @param Request $request
     *
     * @return array
     * @Route("/", name="pumukitnewadmin_unesco_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $page = $request->query->get('page');
        $paginate = $request->query->get('paginate');
        if (isset($page)) {
            $session->set('admin/unesco/page', $page);
        }
        if (isset($paginate)) {
            $session->set('admin/unesco/paginate', $paginate);
        }

        return array();
    }

    /**
     * @Route("/tags", name="pumukitnewadmin_unesco_menu_tags")
     * @Template()
     */
    public function menuTagsAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');

        $tagUNESCO = array();
        foreach (static::$unescoTags as $key => $tag) {
            foreach ($tag as $cod) {
                $tagUNESCO[$translator->trans($key)][] = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(
                    array('cod' => $cod)
                );
            }
        }

        $countMultimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->count();

        $unescoTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'UNESCO'));

        $countMultimediaObjectsWithoutTag = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findWithoutTag(
            $unescoTag
        );
        $defaultTagOptions = array(
            array('key' => 2, 'title' => $translator->trans('All'), 'count' => $countMultimediaObjects),
            array(
                'key' => 1,
                'title' => $translator->trans('Without category'),
                'count' => count($countMultimediaObjectsWithoutTag),
            ),
        );

        return array('tags' => $tagUNESCO, 'defaultTagOptions' => $defaultTagOptions);
    }

    /**
     * @Route("/list/{tag}", name="pumukitnewadmin_unesco_list")
     * @Template("PumukitNewAdminBundle:UNESCO:list.html.twig")
     *
     * @param string $tag
     *
     * @return array
     */
    public function listAction($tag = null)
    {
        $session = $this->get('session');
        $page = $session->get('admin/unesco/page', 1);
        $maxPerPage = $session->get('admin/unesco/paginate', 10);

        if (isset($tag) or $session->has('admin/unesco/tag')) {
            $tag = (isset($tag) ? $tag : $session->get('admin/unesco/tag'));
        }
        if ($session->has('UNESCO/criteria')) {
            $multimediaObjects = $this->searchMultimediaObjects($session->get('UNESCO/criteria'), $tag);
        } elseif ($tag) {
            $multimediaObjects = $this->searchMultimediaObjects($session->get('UNESCO/criteria'), $tag);
        } else {
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder(
            );
        }

        $adapter = new DoctrineODMMongoDBAdapter($multimediaObjects);
        $adapter = new Pagerfanta($adapter);

        $adapter->setMaxPerPage($maxPerPage)->setNormalizeOutOfRangePages(true);

        if ($adapter->getNbPages() < $page) {
            $page = $adapter->getNbPages();
            $session->set('admin/unesco/page', $page);
        }

        $adapter->setCurrentPage($page);

        return array(
            'mms' => $adapter,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        );
    }

    /**
     * @Route("/remove/session/{all}", name="pumukitnewadmin_unesco_removesession")
     *
     * @param bool $all
     *
     * @return JsonResponse
     */
    public function resetSessionAction($all = true)
    {
        $session = $this->get('session');
        if ($all) {
            $session->remove('UNESCO/criteria');
            $session->remove('UNESCO/form');
            $session->remove('UNESCO/formbasic');
        }

        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');

        return new JsonResponse(array('success'));
    }

    /**
     * @Route("/add/criteria", name="pumukitnewadmin_unesco_addcriteria")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addCriteriaSession(Request $request)
    {
        $session = $this->get('session');
        $criteria = $request->request->get('criteria');

        $formBasic = false;
        $newCriteria = array();
        $tag = array();
        foreach ($criteria as $key => $value) {
            if (('id' === $key) and !empty($value)) {
                $newCriteria['_id'] = new \MongoId($value);
                $formBasic = true;
            } elseif (('seriesID' === $key) and !empty($value)) {
                $newCriteria['series'] = new \MongoId($value);
                $formBasic = true;
            } elseif ('type' === $key and !empty($value)) {
                $newCriteria['type'] = $value;
                $formBasic = true;
            } elseif ('duration' === $key and !empty($value)) {
                $newCriteria['track.duration'] = $value;
                $formBasic = true;
            } elseif ('year' === $key and !empty($value)) {
                $newCriteria['year'] = $value;
                $formBasic = true;
            } elseif ('text' === $key and !empty($value)) {
                $newCriteria['$text'] = new \MongoRegex('/.*'.$value.'.*/i');
                $formBasic = true;
            } elseif ('broadcast' === $key and !empty($value)) {
                if ('all' != $value) {
                    $newCriteria['embeddedBroadcast.type'] = $value;
                }
            } elseif ('statusPub' === $key) {
                if ('all' != $value) {
                    $newCriteria['status'] = intval($value);
                }
            } elseif ('announce' === $key and !empty($value)) {
                $tag[] = 'PUDENEW';
            } elseif ('puderadio' === $key and !empty($value)) {
                $tag[] = 'PUDERADIO';
            } elseif ('pudetv' === $key and !empty($value)) {
                $tag[] = 'PUDETV';
            } elseif ('genre' === $key and !empty($value)) {
                $tag[] = $value;
            } elseif ('roles' === $key) {
                foreach ($value as $key2 => $field) {
                    if (!empty($field)) {
                        $newCriteria['roles'][$key2] = new \MongoRegex('/.*'.$field.'.*/i');
                    }
                }
            } elseif (in_array(
                $key,
                array('initPublicDate', 'finishPublicDate', 'initRecordDate', 'finishRecordDate')
            )) {
                if ('initPublicDate' === $key and !empty($value)) {
                    $newCriteria['public_date_init'] = $value;
                } elseif ('finishPublicDate' === $key and !empty($value)) {
                    $newCriteria['public_date_finish'] = $value;
                } elseif ('initRecordDate' === $key and !empty($value)) {
                    $newCriteria['record_date_init'] = $value;
                } elseif ('finishRecordDate' === $key and !empty($value)) {
                    $newCriteria['record_date_finish'] = $value;
                }
            } elseif (!empty($value)) {
                $newCriteria[$key.'.'.$request->getLocale()] = new \MongoRegex('/.*'.$value.'.*/i');
            }
        }

        if (!empty($tag)) {
            array_shift($tag);
            if (!empty($tag)) {
                $newCriteria['tags.cod'] = array('$all' => $tag);
            }
        }

        $session->set('UNESCO/form', $criteria);
        $session->set('UNESCO/criteria', $newCriteria);
        $session->set('UNESCO/formbasic', $formBasic);

        return new JsonResponse(array('success'));
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return array|Response
     *
     * @throws \Exception
     * @Route("edit/{id}", name="pumukit_new_admin_unesco_edit")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:UNESCO:edit.html.twig")
     */
    public function editUNESCOAction(Request $request, MultimediaObject $multimediaObject)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $factoryService->getParentTags();

        //$multimediaObject = $this->findOr404($request);
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectMetaType($translator, $locale), $multimediaObject);
        $options = array('not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS));
        $formPub = $this->createForm(new MultimediaObjectPubType($translator, $locale), $multimediaObject, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->container->getParameter('show_latest_with_pudenew')) {
            $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:Tag')->findOneByCod(
                    'PUDENEW'
                )->setDisplay(false);
        }
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->get('pumukitencoder.job')->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getProfiles(null, true, false);

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        $isPublished = null;
        $playableResource = null;

        $activeEditor = $this->checkHasEditor();
        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $allBundles = $this->container->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $allGroups = $this->getAllGroups();

        return array(
            'mm' => $multimediaObject,
            'form_meta' => $formMeta->createView(),
            'form_pub' => $formPub->createView(),
            //'series' => $series,
            'roles' => $roles,
            'personal_scope_role' => $personalScopeRole,
            'personal_scope_role_code' => $personalScopeRoleCode,
            'pub_channels' => $pubChannelsTags,
            'pub_decisions' => $pubDecisionsTags,
            'parent_tags' => $parentTags,
            'jobs' => $jobs,
            'not_master_profiles' => $notMasterProfiles,
            'template' => $template,
            'active_editor' => $activeEditor,
            'opencast_exists' => $opencastExists,
            'not_change_pub_channel' => $notChangePubChannel,
            'groups' => $allGroups,
        );
    }

    /**
     * @param string $id
     *
     * @return array
     * @Route("/advance/search/show/{id}", name="pumukitnewadmin_unesco_show")
     * @Template()
     */
    public function showAction($id = null)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $roles = $this->get('pumukitschema.person')->getRoles();
        $activeEditor = $this->checkHasEditor();

        $unescoTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('UNESCO');

        if (isset($id)) {
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
                array('_id' => new \MongoId($id))
            );
            $this->get('session')->set('admin/unesco/id', $multimediaObject->getId());
        } else {
            $multimediaObject = null;
        }

        return array(
            'unescoTag' => $unescoTag,
            'mm' => $multimediaObject,
            'roles' => $roles,
            'active_editor' => $activeEditor,
        );
    }

    /**
     * @param Request $request
     * @Route("/advance/search/form", name="pumukitnewadmin_unesco_advance_search_form")
     * @Template("PumukitNewAdminBundle:UNESCO:search_view.html.twig")
     *
     * @return array
     */
    public function advancedSearchFormAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        //$form = $this->createForm(new UNESCOBasicType($translator, $locale));

        $roles = $dm->getRepository('PumukitSchemaBundle:Role')->findAll();

        $pudeRadio = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('PUDERADIO');
        $pudeTV = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('PUDETV');

        $statusPub = array(
            MultimediaObject::STATUS_PUBLISHED => $translator->trans('Published'),
            MultimediaObject::STATUS_BLOQ => $translator->trans('Blocked'),
            MultimediaObject::STATUS_HIDE => $translator->trans('Hidden'),
        );

        $broadcasts = array(
            EmbeddedBroadcast::TYPE_PUBLIC => $translator->trans('Public'),
            EmbeddedBroadcast::TYPE_LOGIN => $translator->trans('Login'),
            EmbeddedBroadcast::TYPE_PASSWORD => $translator->trans('Password'),
            EmbeddedBroadcast::TYPE_GROUPS => $translator->trans('Groups'),
        );

        $type = array(
            MultimediaObject::TYPE_VIDEO => $translator->trans('Video'),
            MultimediaObject::TYPE_AUDIO => $translator->trans('Audio'),
        );

        $genreParent = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('GENRE');
        if ($genreParent) {
            $genres = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($genreParent->getId())));
            $aGenre = array();
            foreach ($genres as $genre) {
                $aGenre[$genre->getCod()] = $genre->getTitle($locale);
            }
        } else {
            $aGenre = array();
        }

        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');

        return array(
            //'form' => $form->createView(),
            'disable_pudenew' => $disablePudenew,
            'genre' => $aGenre,
            'roles' => $roles,
            'statusPub' => $statusPub,
            'broadcasts' => $broadcasts,
            'years' => $this->getMmobjsYears(),
            'type' => $type,
            'puderadio' => $pudeRadio,
            'pudetv' => $pudeTV,
        );
    }

    /**
     * @param $tagCod
     * @param $multimediaObjectId
     *
     * @return JsonResponse
     * @Route("/delete/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_delete_tag")
     */
    public function deleteTagDnD($tagCod, $multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $tagService = $this->container->get('pumukitschema.tag');

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(
            new \MongoId($multimediaObjectId)
        );

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagCod);

        $tagService->removeTagFromMultimediaObject($multimediaObject, $tag->getId());

        return new JsonResponse(array('success'));
    }

    /**
     * @param $tagCod
     * @param $multimediaObjectId
     *
     * @return JsonResponse
     * @Route("/add/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_add_tag")
     */
    public function addTagDnD($tagCod, $multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $tagService = $this->container->get('pumukitschema.tag');

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneById(
            new \MongoId($multimediaObjectId)
        );

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagCod);

        $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        return new JsonResponse(array('success'));
    }

    /**
     * @param Request $request
     * @param string  $option
     *
     * @return JsonResponse
     * @Route("/option/selected/{option}", name="pumukitnewadmin_unesco_options_list")
     */
    public function optionsMultimediaObjects(Request $request, $option)
    {
        $session = $this->get('session');
        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');

        $data = $request->request->get('data');
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        switch ($option) {
            case 'delete_selected':
                $factoryService = $this->get('pumukitschema.factory');
                foreach ($data as $multimediaObjectId) {
                    $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array('_id' => new \MongoId($multimediaObjectId)));
                    $factoryService->deleteMultimediaObject($multimediaObject);
                }
                break;
            case 'invert_announce_selected':
                $tagService = $this->container->get('pumukitschema.tag');
                $pudeNew = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PUDENEW'));
                foreach ($data as $multimediaObjectId) {
                    $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array('_id' => new \MongoId($multimediaObjectId)));
                    if ($multimediaObject->containsTag($pudeNew)) {
                        $tagService->removeTagFromMultimediaObject($multimediaObject, $pudeNew->getId());
                    } else {
                        $tagService->addTagToMultimediaObject($multimediaObject, $pudeNew->getId());
                    }
                }
                break;
            default:
                break;
        }

        return new JsonResponse(array('success'));
    }

    /**
     * @param $criteria
     * @param $tag
     *
     * @return mixed
     */
    private function searchMultimediaObjects($criteria, $tag)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $session = $this->get('session');
        $session->set('admin/unesco/tag', $tag);

        $tagCondition = $tag;
        if (isset($tag) and !in_array($tag, array('1', '2'))) {
            $tagCondition = (strtoupper(substr($tag, 0, 1)));
        }

        switch ($tagCondition) {
            case '1':
                $unescoTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'UNESCO'));
                $query = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder(
                )->field('tags._id')->notEqual(new \MongoId($unescoTag->getId()));
                break;
            case 'U':
                $unescoTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => $tag));
                $query = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder(
                )->field('tags._id')->equals(new \MongoId($unescoTag->getId()));
                break;
            case '2':
            default:
                $query = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder();
                break;
        }

        if (isset($criteria) and !empty($criteria)) {
            $query = $this->addCriteria($query, $criteria);
        }

        return $query;
    }

    /**
     * @param $query
     * @param $criteria
     *
     * @return mixed
     */
    private function addCriteria($query, $criteria)
    {
        foreach ($criteria as $key => $field) {
            if ('roles' === $key and count($field) >= 1) {
                foreach ($field as $key2 => $value) {
                    $query->field('people')->elemMatch($query->expr()->field('cod')->equals($key2)->field('people.name')->equals($value));
                }
            } elseif ('public_date_init' === $key and !empty($field)) {
                $public_date_init = $field;
            } elseif ('public_date_finish' === $key and !empty($field)) {
                $public_date_finish = $field;
            } elseif ('record_date_init' === $key and !empty($field)) {
                $record_date_init = $field;
            } elseif ('record_date_finish' === $key and !empty($field)) {
                $record_date_finish = $field;
            } elseif ('$text' === $key and !empty($field)) {
                $query->text($field);
            } elseif ('type' === $key and !empty($field)) {
                if ('all' != $field) {
                    $query->field('type')->equals($field);
                }
            } elseif ('track.duration' == $key and !empty($field)) {
                $query = $this->findDuration($query, $key, $field);
            } elseif ('year' == $key and !empty($field)) {
                $query = $this->findDuration($query, 'year', $field);
            } else {
                $query->field($key)->equals($field);
            }
        }

        if (isset($public_date_init) and isset($public_date_finish)) {
            $query->field('public_date')->range(
                new \MongoDate(strtotime($public_date_init)),
                new \MongoDate(strtotime($public_date_finish))
            );
        } elseif (isset($public_date_init) and !empty($public_date_init)) {
            $date = date($public_date_init.'T23:59:59');
            $query->field('public_date')->range(
                new \MongoDate(strtotime($public_date_init)),
                new \MongoDate(strtotime($date))
            );
        } elseif (isset($public_date_finish) and !empty($public_date_finish)) {
            $date = date($public_date_finish.'T23:59:59');
            $query->field('public_date')->range(
                new \MongoDate(strtotime($public_date_finish)),
                new \MongoDate(strtotime($date))
            );
        }

        if (isset($record_date_init) and isset($record_date_finish)) {
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_init)),
                new \MongoDate(strtotime($record_date_finish))
            );
        } elseif (isset($record_date_init)) {
            $date = date($record_date_init.'T23:59:59');
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_init)),
                new \MongoDate(strtotime($date))
            );
        } elseif (isset($record_date_finish)) {
            $date = date($record_date_finish.'T23:59:59');
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_finish)),
                new \MongoDate(strtotime($date))
            );
        }

        return $query;
    }

    /**
     * @return array
     */
    private function getMmobjsYears()
    {
        $mmObjColl = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection(
            'PumukitSchemaBundle:MultimediaObject'
        );
        $pipeline = array(
            array('$match' => array('status' => MultimediaObject::STATUS_PUBLISHED)),
            array('$group' => array('_id' => array('$year' => '$record_date'))),
            array('$sort' => array('_id' => 1)),
        );
        $yearResults = $mmObjColl->aggregate($pipeline);
        $years = array();
        foreach ($yearResults as $year) {
            $years[] = $year['_id'];
        }

        return $years;
    }

    /**
     * @param $query
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    private function findDuration($query, $key, $field)
    {
        if ('track.duration' === $key) {
            if ($field == '-5') {
                $query->field($key)->lte(300);
            }
            if ($field == '-10') {
                $query->field($key)->lte(600);
            }
            if ($field == '-30') {
                $query->field($key)->lte(1800);
            }
            if ($field == '-60') {
                $query->field($key)->lte(3600);
            }
            if ($field == '+60') {
                $query->field($key)->gt(3600);
            }
        } elseif ('year' === $key) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', $field));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', ($field) + 1));
            $query->field('record_date')->gte($start);
            $query->field('record_date')->lt($end);
        }

        return $query;
    }

    private function checkHasEditor()
    {
        $router = $this->get('router');
        $routes = $router->getRouteCollection()->all();
        $activeEditor = array_key_exists('pumukit_videoeditor_index', $routes);

        return $activeEditor;
    }

    private function getAllGroups()
    {
        $groupService = $this->get('pumukitschema.group');
        $userService = $this->get('pumukitschema.user');
        $loggedInUser = $this->getUser();
        if ($loggedInUser->isSuperAdmin() || $userService->hasGlobalScope($loggedInUser)) {
            $allGroups = $groupService->findAll();
        } else {
            $allGroups = $loggedInUser->getGroups();
        }

        return $allGroups;
    }
}
