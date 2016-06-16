<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\Playlist
 *
 * @MongoDB\EmbeddedDocument
 */
class Playlist
{
    /**
     * @var ArrayCollection $multimedia_objects
     *
     * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", simple=true, strategy="set")
     * @Serializer\Exclude
     */
    private $multimedia_objects;

    public function __construct()
    {
        $this->multimedia_objects = new ArrayCollection();
    }

    /**
     * Contains multimedia_object
     *
     * @param MultimediaObject $multimedia_object
     *
     * @return boolean
     */
    public function containsMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->contains($multimedia_object);
    }

    /**
     * Add multimedia object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function addMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->add($multimedia_object);
    }

    /**
     * Remove multimedia object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function removeMultimediaObject(MultimediaObject $multimedia_object)
    {
        $this->multimedia_objects->removeElement($multimedia_object);
    }

    /**
     * Remove multimedia object by its position in the playlist.
     *
     * @param integer $pos Position (starting from 0) of the mmobj in the playlist.
     */
    public function removeMultimediaObjectByPos($pos)
    {
        $this->multimedia_objects->remove($pos);
    }

    /**
     * Get multimedia_objects
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjects()
    {
        return $this->multimedia_objects;
    }

    /**
     * Move multimedia_objects
     *
     * @return ArrayCollection
     */
    public function moveMultimediaObject($posStart, $posEnd)
    {
        $maxPos = $this->multimedia_objects->count();
        if($posStart - $posEnd == 0
           || $posStart < 0 || $posStart > $maxPos) {
            return false; //If start is out of range or start/end is the same, do nothing.
        }
        $posEnd = $posEnd % $maxPos; //Out of bounds.
        if($posEnd < 0) {
            $posEnd = $maxPos + $posEnd;
        }
        $tempObject = $this->multimedia_objects->get($posStart);
        if($posStart - $posEnd > 0) {
            for($i = $posStart; $i > $posEnd; $i--) {
                $prevObject = $this->multimedia_objects->get($i-1);
                $this->multimedia_objects->set($i, $prevObject);
            }
        }
        else {
            for($i = $posStart; $i < $posEnd; $i++) {
                $nextObject = $this->multimedia_objects->get($i+1);
                $this->multimedia_objects->set($i, $nextObject);
            }
        }
        $this->multimedia_objects->set($posEnd, $tempObject);
    }
}
