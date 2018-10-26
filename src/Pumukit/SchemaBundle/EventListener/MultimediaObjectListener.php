<?php

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Doctrine\ODM\MongoDB\DocumentManager;

class MultimediaObjectListener
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    private function getTracksType($tracks)
    {
        if (0 === count($tracks)) {
            return MultimediaObject::TYPE_UNKNOWN;
        }

        foreach ($tracks as $track) {
            if (!$track->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }

    /**
     * @param $event
     */
    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();
        $this->updateType($multimediaObject);
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    /**
     * @param $multimediaObject
     */
    public function updateType($multimediaObject)
    {
        if ($multimediaObject->getProperty('opencast')) {
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        } elseif ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif ($displayTracks = $multimediaObject->getTracksWithTag('display')) {
            $multimediaObject->setType($this->getTracksType($displayTracks));
        } elseif ($masterTracks = $multimediaObject->getTracksWithTag('master')) {
            $multimediaObject->setType($this->getTracksType($masterTracks));
        } elseif ($otherTracks = $multimediaObject->getTracks()) {
            $multimediaObject->setType($this->getTracksType($otherTracks));
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }
    }

    /**
     * @param $multimediaObject
     */
    public function updateTextIndex($multimediaObject)
    {
        $textIndex = array();
        $secondaryTextIndex = array();
        $title = $multimediaObject->getI18nTitle();
        foreach (array_keys($title) as $lang) {
            $text = '';
            $secondaryText = '';
            $mongoLang = TextIndexUtils::getCloseLanguage($lang);

            $text .= $multimediaObject->getTitle($lang);
            $text .= ' | '.$multimediaObject->getKeyword($lang);
            $text .= ' | '.$multimediaObject->getSeriesTitle($lang);
            $secondaryText .= $multimediaObject->getDescription($lang);

            $persons = $multimediaObject->getPeopleByRole();
            foreach ($persons as $key => $person) {
                $secondaryText .= ' | '.$person->getName();
            }

            $textIndex[] = array('indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($text));
            $secondaryTextIndex[] = array('indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($secondaryText));
        }
        $multimediaObject->setTextIndex($textIndex);
        $multimediaObject->setSecondaryTextIndex($secondaryTextIndex);
    }
}
