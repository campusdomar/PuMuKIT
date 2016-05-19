<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\EncoderBundle\Document\Job;

class FactoryService
{
    const DEFAULT_SERIES_TITLE = 'New';
    const DEFAULT_MULTIMEDIAOBJECT_TITLE = 'New';

    private $dm;
    private $tagService;
    private $personService;
    private $userService;
    private $mmsDispatcher;
    private $seriesDispatcher;
    private $translator;
    private $locales;
    private $defaultCopyright;
    private $addUserAsPerson;


    public function __construct(DocumentManager $documentManager, TagService $tagService, PersonService $personService, UserService $userService, MultimediaObjectEventDispatcherService $mmsDispatcher, SeriesEventDispatcherService $seriesDispatcher, TranslatorInterface $translator, $addUserAsPerson=true, array $locales = array(), $defaultCopyright = "")
    {
        $this->dm = $documentManager;
        $this->tagService = $tagService;
        $this->personService = $personService;
        $this->userService = $userService;
        $this->mmsDispatcher = $mmsDispatcher;
        $this->seriesDispatcher = $seriesDispatcher;
        $this->translator = $translator;
        $this->locales = $locales;
        $this->defaultCopyright = $defaultCopyright;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    /**
     * Get locales
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Create a new series with default values
     *
     * @param  User   $loggedInUser
     * @return Series
     */
    public function createSeries(User $loggedInUser = null)
    {
        $series = new Series();

        $series->setPublicDate(new \DateTime("now"));
        $series->setCopyright($this->defaultCopyright);
        foreach ($this->locales as $locale) {
            $title = $this->translator->trans(self::DEFAULT_SERIES_TITLE, array(), null, $locale);
            $series->setTitle($title, $locale);
        }

        $mm = $this->createMultimediaObjectPrototype($series, $loggedInUser);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchCreate($series);

        return $series;
    }

    /**
     * Create a new Multimedia Object Template
     *
     * @param  Series           $series
     * @param  User             $loggedInUser
     * @return MultimediaObject
     */
    private function createMultimediaObjectPrototype(Series $series, User $loggedInUser = null)
    {
        $mm = new MultimediaObject();
        $mm->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $broadcast = $this->getDefaultBroadcast();
        if ($broadcast) {
            $mm->setBroadcast($broadcast);
            $this->dm->persist($broadcast);
        }
        $mm->setPublicDate(new \DateTime("now"));
        $mm->setRecordDate($mm->getPublicDate());
        $mm->setCopyright($this->defaultCopyright);        
        foreach ($this->locales as $locale) {
            $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
            $mm->setTitle($title, $locale);
        }

        $mm->setSeries($series);
        $mm = $this->addLoggedInUserAsPerson($mm, $loggedInUser);

        return $mm;
    }

    /**
     * Create a new Multimedia Object from Template
     *
     * @param  Series           $series
     * @param  boolean          $flush
     * @param  User             $loggedInUser
     * @return MultimediaObject
     */
    public function createMultimediaObject(Series $series, $flush = true, User $loggedInUser = null)
    {
        $prototype = $this->getMultimediaObjectPrototype($series);

        if (null !== $prototype) {
            $mm = $this->createMultimediaObjectFromPrototype($prototype);
        } else {
            $mm = new MultimediaObject();
            foreach ($this->locales as $locale) {
                $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, array(), null, $locale);
                $mm->setTitle($title, $locale);
            }
            $broadcast = $this->getDefaultBroadcast();
            if ($broadcast) {
                $mm->setBroadcast($broadcast);
                $this->dm->persist($broadcast);
            }
        }
        $mm->setPublicDate(new \DateTime("now"));
        $mm->setRecordDate($mm->getPublicDate());
        $mm->setStatus(MultimediaObject::STATUS_BLOQ);

        $mm->setSeries($series);
        $series->addMultimediaObject($mm);
        $mm = $this->addLoggedInUserAsPerson($mm, $loggedInUser);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        if($flush) {
            $this->dm->flush();
        }

        $this->seriesDispatcher->dispatchUpdate($series);

        return $mm;
    }

    /**
     * Gets default broadcast or public one
     *
     * @return Broadcast
     */
    public function getDefaultBroadcast()
    {
        $repoBroadcast = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');

        $broadcast = $repoBroadcast->findDefaultSel();

        if (null == $broadcast) {
            $broadcast = $repoBroadcast->findPublicBroadcast();
        }

        return $broadcast;
    }

    /**
     * Get series by id
     *
     * @param string $id
     * @param string $sessionId
     * @return Series
     */
    public function findSeriesById($id, $sessionId=null)
    {
        $repo = $this->dm->getRepository('PumukitSchemaBundle:Series');

        if (null !== $id) {
            $series = $repo->find($id);
        } elseif (null !== $sessionId) {
            $series = $repo->find($sessionId);
        } else {
          return null;
        }
        
        return $series;
    }

    /**
     * Get multimediaObject by id
     *
     * @param string $id
     * @return Multimedia Object
     */
    public function findMultimediaObjectById($id)
    {
        $repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        return $repo->find($id);
    }

    /**
     * Get parent tags
     */
    public function getParentTags()
    {
        $repo = $this->dm->getRepository('PumukitSchemaBundle:Tag');

        return $repo->findOneByCod('ROOT')->getChildren();
    }

    /**
     * Get multimedia object template
     *
     * @param Series $series
     * @return MultimediaObject
     */
    public function getMultimediaObjectPrototype(Series $series=null)
    {
        return $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject')
          ->findPrototype($series);
    }

    /**
     * Get tags by cod
     *
     * @param string $cod
     * @param boolean $getChildren
     * @return ArrayCollection $tags
     */
    public function getTagsByCod($cod, $getChildren)
    {
        $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');

        $tags = $repository->findOneByCod($cod);

        if ($tags && $getChildren) {
            return $tags->getChildren();
        }

        return $tags;
    }

    /**
     * Delete Series
     *
     * @param Series $series
     */
    public function deleteSeries(Series $series)
    {
        $repoMmobjs = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
         
        $multimediaObjects = $repoMmobjs->findBySeries($series);
        foreach($multimediaObjects as $mm){
            $series->removeMultimediaObject($mm);
            $this->dm->remove($mm);
            $this->mmsDispatcher->dispatchDelete($mm);
        }
         
        $this->dm->remove($series);

        $this->dm->flush();

        $this->seriesDispatcher->dispatchDelete($series);
    }

    /**
     * Delete MultimediaObject
     *
     * @param MultimediaObject $multimediaObject
     */
    public function deleteMultimediaObject(MultimediaObject $multimediaObject)
    {
        $repoMmobjs = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        if (null != $series = $multimediaObject->getSeries()) {
            $series->removeMultimediaObject($multimediaObject);
            $this->dm->persist($series);
            $this->seriesDispatcher->dispatchUpdate($series);
        }
        $annotRepo = $this->dm->getRepository('PumukitSchemaBundle:Annotation');
        $annotations = $annotRepo->findBy(array('multimediaObject' => new \MongoId($multimediaObject->getId())));
        foreach( $annotations as $annot) {
            $this->dm->remove($annot);
        }
        $this->dm->remove($multimediaObject);
        $this->dm->flush();

        $this->mmsDispatcher->dispatchDelete($multimediaObject);
    }

    /**
     * Delete resource
     *
     * @param Object $resource
     */
    public function deleteResource($resource)
    {
        if ($resource instanceof User) {
            $this->userService->delete($resource);
        } else {
            $this->dm->remove($resource);
            $this->dm->flush();
        }
    }

    /**
     * Create multimedia object from prototype
     *
     * @param  MultimediaObject $prototype
     * @return MultimediaObject
     */
    private function createMultimediaObjectFromPrototype(MultimediaObject $prototype)
    {
        $new = new MultimediaObject();

        $new->setI18nTitle($prototype->getI18nTitle());
        $new->setI18nSubtitle($prototype->getI18nSubtitle());
        $new->setI18nDescription($prototype->getI18nDescription());
        $new->setI18nLine2($prototype->getI18nLine2());
        $new->setI18nKeyword($prototype->getI18nKeyword());
        $new->setCopyright($prototype->getCopyright());
        $new->setLicense($prototype->getLicense());

        if ($broadcast = $prototype->getBroadcast()) {
            $new->setBroadcast($broadcast);
            $this->dm->persist($broadcast);
        }

        foreach ($prototype->getTags() as $tag) {
          $tagAdded = $this->tagService->addTagToMultimediaObject($new, $tag->getId(), false);
        }

        foreach ($prototype->getRoles() as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                $new->addPersonWithRole($embeddedPerson, $embeddedRole);
            }
        }

        foreach ($prototype->getGroups() as $group) {
            $new->addGroup($group);
        }

        return $new;
    }


    /**
     * Clone a multimedia object.
     *
     * @param  MultimediaObject $src
     * @return MultimediaObject
     */
    public function cloneMultimediaObject(MultimediaObject $src)
    {
        $new = new MultimediaObject();

        $i18nTitles = array();
        foreach($src->getI18nTitle() as $key => $val) {
            $string = $this->translator->trans('cloned', array(), null, $key);
            $i18nTitles[$key] = $val . ' (' . $string. ')';
        }
        $new->setI18nTitle($i18nTitles);
        $new->setI18nSubtitle($src->getI18nSubtitle());
        $new->setI18nDescription($src->getI18nDescription());
        $new->setI18nLine2($src->getI18nLine2());
        $new->setI18nKeyword($src->getI18nKeyword());
        $new->setCopyright($src->getCopyright());
        $new->setLicense($src->getLicense());
        // NOTE: #7408 Specify which properties are clonable
        $new->setProperty("subseries", $src->getProperty("subseries"));
        $new->setProperty("subseriestitle", $src->getProperty("subseriestitle"));

        $new->setProperty("clonedfrom", $src->getId());

        foreach ($src->getTags() as $tag) {
          $tagAdded = $this->tagService->addTagToMultimediaObject($new, $tag->getId(), false);
        }

        foreach ($src->getRoles() as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                $new->addPersonWithRole($embeddedPerson, $embeddedRole);
            }
        }

        foreach ($src->getGroups() as $group) {
            $new->addGroup($group);
        }

        $new->setSeries($src->getSeries());
        $this->dm->persist($new);
        foreach ($src->getPics() as $thumb) {
            $clonedThumb = clone $thumb;
            $this->dm->persist($clonedThumb);
            $new->addPic($clonedThumb);
        }
        foreach ($src->getTracks() as $track) {
            $clonedTrack = clone $track;
            $this->dm->persist($clonedTrack);
            $new->addTrack($clonedTrack);
        }
        foreach ($src->getMaterials() as $material) {
            $clonedMaterial = clone $material;
            $this->dm->persist($clonedMaterial);
            $new->addMaterial($clonedMaterial);
        }
        foreach ($src->getLinks() as $link) {
            $clonedLink = clone $link;
            $this->dm->persist($clonedLink);
            $new->addLink($clonedLink);
        }
        $annotRepo = $this->dm->getRepository('PumukitSchemaBundle:Annotation');
        $annotations = $annotRepo->findBy(array('multimediaObject' => new \MongoId($src->getId())));
        foreach($annotations as $annot) {
            $clonedAnnot = clone $annot;
            $clonedAnnot->setMultimediaObject($new->getId());
            $this->dm->persist($clonedAnnot);
            $this->dm->flush();//necessary?
        }

        if ($broadcast = $src->getBroadcast()) {
            $new->setBroadcast($broadcast);
            $this->dm->persist($broadcast);
        }

        $new->setPublicDate($src->getPublicDate());
        $new->setRecordDate($src->getRecordDate());
        $new->setStatus(MultimediaObject::STATUS_BLOQ);

        $this->dm->persist($new);
        $this->dm->flush();

        return $new;
    }

    private function addLoggedInUserAsPerson(MultimediaObject $multimediaObject, User $loggedInUser = null)
    {
        if ($this->addUserAsPerson && (null != $person = $this->personService->getPersonFromLoggedInUser($loggedInUser))) {
            if (null != $role = $this->personService->getPersonalScopeRole()) {
                $multimediaObject = $this->personService->createRelationPerson($person, $role, $multimediaObject);
            }
        }

        return $multimediaObject;
    }
}
