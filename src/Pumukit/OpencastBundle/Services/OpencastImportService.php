<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;

class OpencastImportService
{
    private $opencastClient;
    private $dm;
    private $factoryService;
    private $trackService;
    private $tagService;
    private $mmsService;
    private $opencastService;
    private $inspectionService;
    private $otherLocales;
    private $defaultTagImported;
    private $seriesImportService;
    private $customLanguages;

    /**
     * OpencastImportService constructor.
     *
     * @param DocumentManager            $documentManager
     * @param FactoryService             $factoryService
     * @param LoggerInterface            $logger
     * @param TranslatorInterface        $translator
     * @param TrackService               $trackService
     * @param TagService                 $tagService
     * @param MultimediaObjectService    $mmsService
     * @param ClientService              $opencastClient
     * @param OpencastService            $opencastService
     * @param InspectionServiceInterface $inspectionService
     * @param array                      $otherLocales
     * @param $defaultTagImported
     * @param SeriesImportService $seriesImportService
     * @param array               $customLanguages
     */
    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, LoggerInterface $logger, TranslatorInterface $translator, TrackService $trackService, TagService $tagService, MultimediaObjectService $mmsService, ClientService $opencastClient, OpencastService $opencastService, InspectionServiceInterface $inspectionService, array $otherLocales, $defaultTagImported, SeriesImportService $seriesImportService, array $customLanguages)
    {
        $this->opencastClient = $opencastClient;
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->trackService = $trackService;
        $this->tagService = $tagService;
        $this->mmsService = $mmsService;
        $this->opencastService = $opencastService;
        $this->inspectionService = $inspectionService;
        $this->otherLocales = $otherLocales;
        $this->defaultTagImported = $defaultTagImported;
        $this->seriesImportService = $seriesImportService;
        $this->customLanguages = $customLanguages;
    }

    /**
     * Import recording.
     *
     * Given a media package id
     * create a multimedia object
     * with the media package metadata
     *
     * @param string    $opencastId
     * @param bool      $invert
     * @param User|null $loggedInUser
     */
    public function importRecording($opencastId, $invert = false, User $loggedInUser = null)
    {
        $mediaPackage = $this->opencastClient->getMediaPackage($opencastId);
        $this->importRecordingFromMediaPackage($mediaPackage, $invert, $loggedInUser);
    }

    /**
     * Import recording given a mediaPackage.
     *
     * Given a media package
     * create a multimedia object
     * with the media package metadata
     *
     * @param array     $mediaPackage
     * @param bool      $invert
     * @param User|null $loggedInUser
     */
    public function importRecordingFromMediaPackage($mediaPackage, $invert = false, User $loggedInUser = null)
    {
        $multimediaObject = null;
        $multimediaObjectRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mediaPackageId = $this->getMediaPackageField($mediaPackage, 'id');
        if ($mediaPackageId) {
            $multimediaObject = $multimediaObjectRepo->findOneBy(array('properties.opencast' => $mediaPackageId));
        }

        if (null !== $multimediaObject) {
            $this->syncTracks($multimediaObject, $mediaPackage);
            $this->syncPics($multimediaObject, $mediaPackage);
            $multimediaObject = $this->mmsService->updateMultimediaObject($multimediaObject);

            return;
        }

        //Check if mp has Galicaster properties and look for an mmobj with the given id.
        $galicasterPropertiesUrl = null;
        foreach ($mediaPackage['attachments']['attachment'] as $attachment) {
            if ('galicaster-properties' != $attachment['id']) {
                continue;
            }
            $galicasterPropertiesUrl = $attachment['url'];
            break;
        }

        $galicasterProperties = array();
        if ($galicasterPropertiesUrl) {
            $galicasterProperties = $this->opencastClient->getGalicasterPropertiesFromUrl($galicasterPropertiesUrl);
        } else {
            $this->logger->warning(sprintf('No \'galicaster-properties\' id exist on attachments list from mediapackage.'));
            //NOTE: This will only work correctly if the mp was only ingested once. We need to figure out and pass it
            //the correct 'mediapackage version', but the endpoint with that info does not work currently.
            $galicasterProperties = $this->opencastClient->getGalicasterProperties($mediaPackageId);
        }

        if (isset($galicasterProperties['galicaster']['properties']['pmk_mmobj'])) {
            $multimediaObjectId = $galicasterProperties['galicaster']['properties']['pmk_mmobj'];
            $multimediaObject = $multimediaObjectRepo->find($multimediaObjectId);
        }

        //We try to initialize the tracks before anything to prevent importing if any tracks have wrong data
        $media = $this->getMediaPackageField($mediaPackage, 'media');
        $opencastTracks = $this->getMediaPackageField($media, 'track');
        $language = $this->getMediaPackageLanguage($mediaPackage);

        if (!isset($opencastTracks[0])) {
            // NOTE: Single track
            $opencastTracks = array($opencastTracks);
        }
        $tracks = array();
        foreach ($opencastTracks as $opencastTrack) {
            $tracks[] = $this->createTrackFromOpencastTrack($opencastTrack, $language);
        }

        // - If the id does not exist, create a new mmobj
        if (null === $multimediaObject) {
            $series = $this->seriesImportService->importSeries($mediaPackage, $loggedInUser);

            $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $loggedInUser);
            $multimediaObject->setSeries($series);

            $title = $this->getMediaPackageField($mediaPackage, 'title');

            if ($title) {
                $multimediaObject->setTitle($title);
            }

            foreach ($this->otherLocales as $locale) {
                $multimediaObject->setTitle($title, $locale);
            }

            // -- If it exist, but already has tracks, clone the mmobj, but clear tracks/attachments NOTE: What about tags?
        } elseif (count($multimediaObject->getTracks()) > 0) {
            $newMultimediaObject = $this->factoryService->cloneMultimediaObject($multimediaObject, $multimediaObject->getSeries(), false);
            // TODO: Translate?
            $commentsText = $this->translator->trans(
                'From Opencast. Used "%title%" (%id%) as template.',
                array('%title%' => $multimediaObject->getTitle(), '%id%' => $multimediaObject->getId())
            );
            $multimediaObject = $newMultimediaObject;
            $multimediaObject->setComments($commentsText);
            foreach ($multimediaObject->getTracks() as $track) {
                $multimediaObject->removeTrack($track);
            }
            foreach ($multimediaObject->getPics() as $pic) {
                $multimediaObject->removePic($pic);
            }
        }

        $multimediaObject->setProperty('opencastlanguage', $language);
        foreach ($tracks as $track) {
            $this->trackService->addTrackToMultimediaObject($multimediaObject, $track, false);
        }

        if (isset($galicasterProperties['galicaster'])) {
            $multimediaObject->setProperty('galicaster', $galicasterProperties['galicaster']);
        }

        // -- Then, add opencast object to mmobj
        $properties = $this->getMediaPackageField($mediaPackage, 'id');
        if ($properties) {
            $multimediaObject->setProperty('opencast', $properties);
            $multimediaObject->setProperty('opencasturl', $this->opencastClient->getPlayerUrl().'?mode=embed&id='.$properties);
        }

        // NOTE: Should this be added to the already created mmobj? I think not.
        if (boolval($invert)) {
            $multimediaObject->setProperty('opencastinvert', true);
            $multimediaObject->setProperty('paellalayout', 'professor_slide');
        } else {
            $multimediaObject->setProperty('opencastinvert', false);
            $multimediaObject->setProperty('paellalayout', 'slide_professor');
        }

        $recDate = $this->getMediaPackageField($mediaPackage, 'start');
        if ($recDate) {
            $multimediaObject->setRecordDate($recDate);
        }

        $attachments = $this->getMediaPackageField($mediaPackage, 'attachments');
        $attachment = $this->getMediaPackageField($attachments, 'attachment');
        if (isset($attachment[0])) {
            $limit = count($attachment);
            for ($j = 0; $j < $limit; ++$j) {
                $multimediaObject = $this->createPicFromAttachment($attachment, $multimediaObject, $j);
            }
        } else {
            $multimediaObject = $this->createPicFromAttachment($attachment, $multimediaObject);
        }

        $tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $opencastTag = $tagRepo->findOneByCod($this->defaultTagImported);
        if ($opencastTag) {
            $tagService = $this->tagService;
            $tagAdded = $tagService->addTagToMultimediaObject($multimediaObject, $opencastTag->getId());
        }

        $multimediaObject = $this->mmsService->updateMultimediaObject($multimediaObject);

        if ($track) {
            $opencastUrls = $this->getOpencastUrls($mediaPackageId);
            $this->opencastService->genAutoSbs($multimediaObject, $opencastUrls);
        }
    }

    public function getOpencastUrls($opencastId = '')
    {
        $opencastUrls = array();
        if (null !== $opencastId) {
            try {
                $archiveMediaPackage = $this->opencastClient->getMasterMediaPackage($opencastId);
            } catch (\Exception $e) {
                // TODO - Trace error
                return $opencastUrls;
            }
            $media = $this->getMediaPackageField($archiveMediaPackage, 'media');
            $tracks = $this->getMediaPackageField($media, 'track');
            if (isset($tracks[0])) {
                // NOTE: Multiple tracks
                $limit = count($tracks);
                for ($i = 0; $i < $limit; ++$i) {
                    $track = $tracks[$i];
                    $opencastUrls = $this->addOpencastUrl($opencastUrls, $track);
                }
            } else {
                // NOTE: Single track
                $track = $tracks;
                $opencastUrls = $this->addOpencastUrl($opencastUrls, $track);
            }
        }

        return $opencastUrls;
    }

    private function addOpencastUrl($opencastUrls = array(), $track = array())
    {
        $type = $this->getMediaPackageField($track, 'type');
        $url = $this->getMediaPackageField($track, 'url');
        if ($type && $url) {
            $opencastUrls[$type] = $url;
        }

        return $opencastUrls;
    }

    public function getMediaPackageField($mediaFields = array(), $field = '')
    {
        if ($mediaFields && $field) {
            if (isset($mediaFields[$field])) {
                return $mediaFields[$field];
            }
        }

        return null;
    }

    public function createTrackFromMediaPackage($mediaPackage, MultimediaObject $multimediaObject, $index = null, $trackTags = array('display'), $defaultLanguage = null)
    {
        $media = $this->getMediaPackageField($mediaPackage, 'media');
        $tracks = $this->getMediaPackageField($media, 'track');
        if ($tracks) {
            if (null === $index) {
                $opencastTrack = $tracks;
            } else {
                $opencastTrack = $tracks[$index];
            }
        } else {
            throw new \Exception(sprintf("No media track info in MP '%s'", $multimediaObject->getProperty('opencast')));
        }
        $language = $this->getMediaPackageLanguage($mediaPackage, $defaultLanguage);

        $track = $this->createTrackFromOpencastTrack($opencastTrack, $language, $trackTags);
        $multimediaObject->setDuration($track->getDuration());
        $this->trackService->addTrackToMultimediaObject($multimediaObject, $track, false);

        return $track;
    }

    public function createTrackFromOpencastTrack($opencastTrack, $language, $trackTags = array('display'))
    {
        $track = new Track();
        $track->setLanguage($language);

        $tagsArray = $this->getMediaPackageField($opencastTrack, 'tags');
        $tags = $this->getMediaPackageField($tagsArray, 'tag');
        if (!isset($tags[0])) {
            // NOTE: Single tag
            $tags = array($tags);
        }

        $limit = count($tags);
        for ($i = 0; $i < $limit; ++$i) {
            $track = $this->addTagToTrack($tags, $track, $i);
        }

        $url = $this->getMediaPackageField($opencastTrack, 'url');
        if ($url) {
            $track->setUrl($url);
            $track->setPath($this->opencastService->getPath($url));
        }

        $mime = $this->getMediaPackageField($opencastTrack, 'mimetype');
        if ($mime) {
            $track->setMimeType($mime);
        }

        $duration = $this->getMediaPackageField($opencastTrack, 'duration');
        if ($duration) {
            $track->setDuration($duration / 1000);
        }

        $audio = $this->getMediaPackageField($opencastTrack, 'audio');
        $encoder = $this->getMediaPackageField($audio, 'encoder');
        $acodec = $this->getMediaPackageField($encoder, 'type');
        if ($acodec) {
            $track->setAcodec($acodec);
        }

        $video = $this->getMediaPackageField($opencastTrack, 'video');
        $encoder = $this->getMediaPackageField($video, 'encoder');
        $vcodec = $this->getMediaPackageField($encoder, 'type');
        if ($vcodec) {
            $track->setVcodec($vcodec);
        }

        $framerate = $this->getMediaPackageField($video, 'framerate');
        if ($framerate) {
            $track->setFramerate($framerate);
        }

        if (!$track->getVcodec() && $track->getAcodec()) {
            $track->setOnlyAudio(true);
        } else {
            $track->setOnlyAudio(false);
        }

        $track->addTag('opencast');
        foreach ($trackTags as $trackTag) {
            $track->addTag($trackTag);
        }

        $type = $this->getMediaPackageField($opencastTrack, 'type');
        if ($type) {
            $track->addTag($opencastTrack['type']);
        }

        if ($track->getPath()) {
            $this->inspectionService->autocompleteTrack($track);
        }

        return $track;
    }

    private function createPicFromAttachment($attachment, MultimediaObject $multimediaObject, $index = null)
    {
        if ($attachment) {
            if (null === $index) {
                $itemAttachment = $attachment;
            } else {
                $itemAttachment = $attachment[$index];
            }
            $type = $this->getMediaPackageField($itemAttachment, 'type');
            if ('presenter/search+preview' == $type) {
                $tags = $this->getMediaPackageField($itemAttachment, 'tags');
                $type = $this->getMediaPackageField($itemAttachment, 'type');
                $url = $this->getMediaPackageField($itemAttachment, 'url');
                if ($tags || $url) {
                    $pic = new Pic();
                    $pic->addTag('opencast');
                    $pic->addTag($type);
                    if ($tags) {
                        foreach ($tags as $tag) {
                            $pic->addTag($tag);
                        }
                    }
                    if ($url) {
                        $pic->setUrl($url);
                    }
                    $multimediaObject->addPic($pic);
                }
            }
        }

        return $multimediaObject;
    }

    private function addTagToTrack($tags, Track $track, $index = null)
    {
        if ($tags) {
            if (null === $index) {
                $tag = $tags;
            } else {
                $tag = $tags[$index];
            }
            if (!$track->containsTag($tag)) {
                $track->addTag($tag);
            }
        }

        return $track;
    }

    private function getMediaPackageLanguage($mediaPackage, $defaultLanguage = null)
    {
        $language = $this->getMediaPackageField($mediaPackage, 'language');
        if ($language) {
            $parsedLocale = \Locale::parseLocale($language);
            if (!$this->customLanguages || in_array($parsedLocale['language'], $this->customLanguages)) {
                return $parsedLocale['language'];
            }
        }

        return (null !== $defaultLanguage) ? $defaultLanguage : \Locale::getDefault();
    }

    public function importTracksFromMediaPackage($mediaPackage, MultimediaObject $multimediaObject, $trackTags)
    {
        $media = $this->getMediaPackageField($mediaPackage, 'media');
        $tracks = $this->getMediaPackageField($media, 'track');
        if (isset($tracks[0])) {
            $limit = count($tracks);
            for ($i = 0; $i < $limit; ++$i) {
                if (false === stripos($tracks[$i]['url'], 'rtmp:')) {
                    $this->createTrackFromMediaPackage($mediaPackage, $multimediaObject, $i, $trackTags);
                }
            }
        } else {
            if (false === stripos($tracks['url'], 'rtmp:')) {
                $this->createTrackFromMediaPackage($mediaPackage, $multimediaObject, null, $trackTags);
            }
        }
    }

    public function syncTracks(MultimediaObject $multimediaObject, $mediaPackage = null)
    {
        $mediaPackageId = $multimediaObject->getProperty('opencast');
        if (!$mediaPackageId) {
            return;
        }

        if (!$mediaPackage) {
            $mediaPackage = $this->opencastClient->getMediaPackage($mediaPackageId);
        }

        if (!$mediaPackage) {
            throw new \Exception('Opencast communication error');
        }

        $media = $this->getMediaPackageField($mediaPackage, 'media');
        $tracks = $this->getMediaPackageField($media, 'track');
        if (isset($tracks[0])) {
            // NOTE: Multiple tracks
            $limit = count($tracks);
            for ($i = 0; $i < $limit; ++$i) {
                $track = $tracks[$i];
                $type = $this->getMediaPackageField($track, 'type');
                $url = $this->getMediaPackageField($track, 'url');
                if ($type && $url) {
                    $this->syncTrack($multimediaObject, $type, $url);
                }
            }
        } else {
            // NOTE: Single track
            $type = $this->getMediaPackageField($tracks, 'type');
            $url = $this->getMediaPackageField($tracks, 'url');
            if ($type && $url) {
                $this->syncTrack($multimediaObject, $type, $url);
            }
        }
    }

    private function syncTrack(MultimediaObject $multimediaObject, $type, $url)
    {
        $track = $multimediaObject->getTrackWithAllTags(array('opencast', $type));
        if (!$track) {
            return false;
        }

        $track->setUrl($url);
        $track->setPath($this->opencastService->getPath($url));

        return true;
    }

    public function syncPics(MultimediaObject $multimediaObject, $mediaPackage = null)
    {
        $mediaPackageId = $multimediaObject->getProperty('opencast');
        if (!$mediaPackageId) {
            return;
        }

        if (!$mediaPackage) {
            $mediaPackage = $this->opencastClient->getMediaPackage($mediaPackageId);
        }

        if (!$mediaPackage) {
            throw new \Exception('Opencast communication error');
        }

        $attachments = $this->getMediaPackageField($mediaPackage, 'attachments');
        $attachment = $this->getMediaPackageField($attachments, 'attachment');
        if (isset($attachment[0])) {
            $limit = count($attachment);
            for ($i = 0; $i < $limit; ++$i) {
                $pic = $attachment[$i];
                $type = $this->getMediaPackageField($pic, 'type');
                $url = $this->getMediaPackageField($pic, 'url');
                if ($type && $url) {
                    $this->syncPic($multimediaObject, $type, $url);
                }
            }
        } else {
            $type = $this->getMediaPackageField($attachment, 'type');
            $url = $this->getMediaPackageField($attachment, 'url');
            if ($type && $url) {
                $this->syncPic($multimediaObject, $type, $url);
            }
        }
    }

    private function syncPic(MultimediaObject $multimediaObject, $type, $url)
    {
        $pic = $multimediaObject->getPicWithAllTags(array('opencast', $type));
        if (!$pic) {
            return false;
        }

        $pic->setUrl($url);

        return true;
    }
}
