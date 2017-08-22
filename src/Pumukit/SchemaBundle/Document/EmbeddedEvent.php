<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Pumukit\LiveBundle\Document\Live as DocumentLive;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\SchemaBundle\Document\EmbeddedEvent.
 *
 * @MongoDB\EmbeddedDocument
 */
class EmbeddedEvent
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $name;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $description;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $place;

    /**
     * @var \Datetime
     *
     * @MongoDB\Date
     */
    private $date;

    /**
     * @var int
     *
     * @MongoDB\Int
     */
    private $duration = 0;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $display = true;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $create_serial = true;

    /**
     * @var ArrayCollection
     *
     * @MongoDB\EmbedMany(targetDocument="EmbeddedEventSession")
     */
    private $embeddedEventSession;

    /**
     * @var Live
     *
     * @MongoDB\ReferenceOne(targetDocument="Pumukit\LiveBundle\Document\Live")
     */
    private $live;

    /**
     * @var string
     *
     * @MongoDB\String
     * @Assert\NotBlank()
     * @Assert\Url(protocols= {"rtmpt", "rtmp", "http", "mms", "rtp", "https"})
     */
    private $url;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->embeddedEventSession = new ArrayCollection();
        $this->name = array('en' => '');
        $this->description = array('en' => '');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string      $name
     * @param string|null $locale
     */
    public function setName($name, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    /**
     * Get name.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get I18n name.
     *
     * @return array
     */
    public function getI18nName()
    {
        return $this->name;
    }

    /**
     * @param null $locale
     *
     * @return mixed|string
     */
    public function getDescription($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * @param $description
     * @param null $locale
     */
    public function setDescription($description, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }

        $this->description[$locale] = $description;
    }

    /**
     * Set I18n name.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get I18n description.
     *
     * @return array
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param string $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    /**
     * @return \Datetime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \Datetime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return bool
     */
    public function isDisplay()
    {
        return $this->display;
    }

    /**
     * @param bool $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * @return bool
     */
    public function isCreateSerial()
    {
        return $this->create_serial;
    }

    /**
     * @param bool $create_serial
     */
    public function setCreateSerial($create_serial)
    {
        $this->create_serial = $create_serial;
    }

    /**
     * @return ArrayCollection
     */
    public function getEmbeddedEventSession()
    {
        $embeddedEventSession = $this->embeddedEventSession->toArray();
        usort($embeddedEventSession, function ($a, $b) {
            return $a->getStart() > $b->getStart();
        });

        return $embeddedEventSession;
    }

    /**
     * @param ArrayCollection $embeddedEventSession
     */
    public function setEmbeddedEventSession($embeddedEventSession)
    {
        $this->embeddedEventSession = $embeddedEventSession;
    }

    /**
     * @param $embeddedEventSession
     *
     * @return mixed
     */
    public function addEmbeddedEventSession($embeddedEventSession)
    {
        return $this->embeddedEventSession->add($embeddedEventSession);
    }

    /**
     * @param $embeddedEventSession
     *
     * @return bool
     */
    public function removeEmbeddedEventSession($embeddedEventSession)
    {
        foreach ($this->embeddedEventSession as $session) {
            if ($session->getId() == $embeddedEventSession->getId()) {
                $removed = $this->embeddedEventSession->removeElement($embeddedEventSession);
                $this->embeddedEventSession = new ArrayCollection(array_values($this->embeddedEventSession->toArray()));

                return $removed;
            }
        }

        return false;
    }

    /**
     * @return DocumentLive
     */
    public function getLive()
    {
        return $this->live;
    }

    /**
     * @param DocumentLive $live
     */
    public function setLive($live)
    {
        $this->live = $live;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}
