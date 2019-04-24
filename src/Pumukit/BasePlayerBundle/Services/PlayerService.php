<?php

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class PlayerService
{
    /**
     * @param MultimediaObject $multimediaObject
     * @param bool             $isMagicUrl
     *
     * @return bool
     */
    public function canBeReproduced(MultimediaObject $multimediaObject, $isMagicUrl = false)
    {
        if (MultimediaObject::TYPE_EXTERNAL === $multimediaObject->getType()) {
            return $this->canBeReproducedExternal($multimediaObject, $isMagicUrl);
        }

        $status = $multimediaObject->getStatus();

        $containsWebTV = $multimediaObject->containsTagWithCod('PUCHWEBTV');
        if ($isMagicUrl && in_array($status, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN]) && $containsWebTV) {
            if ($multimediaObject->getDisplayTrack()) {
                return true;
            }
        }
        if (!$isMagicUrl && in_array($status, [MultimediaObject::STATUS_PUBLISHED]) && $containsWebTV) {
            if ($multimediaObject->getDisplayTrack()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param bool             $isMagicUrl
     *
     * @return bool
     */
    public function canBeReproducedExternal(MultimediaObject $multimediaObject, $isMagicUrl = false)
    {
        $status = $multimediaObject->getStatus();
        $containsWebTV = $multimediaObject->containsTagWithCod('PUCHWEBTV');
        if ($isMagicUrl && in_array($status, [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN]) && $containsWebTV) {
            if ($multimediaObject->getProperty('externalplayer')) {
                return true;
            }
        }
        if (!$isMagicUrl && in_array($status, [MultimediaObject::STATUS_PUBLISHED]) && $containsWebTV) {
            if ($multimediaObject->getProperty('externalplayer')) {
                return true;
            }
        }

        return false;
    }
}
