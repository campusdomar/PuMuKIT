<?php

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RouterInterface;

class PlayerService
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * PlayerService constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return mixed
     */
    public function getPublicControllerPlayer(MultimediaObject $multimediaObject)
    {
        $url = $this->router->generate('pumukit_videoplayer_index', ['id' => $multimediaObject->getId()]);
        $url = $this->cleanUrl($url);
        $endpoint = $this->router->match($url);

        return $endpoint['_controller'];
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return mixed
     */
    public function getMagicControllerPlayer(MultimediaObject $multimediaObject)
    {
        $url = $this->router->generate('pumukit_videoplayer_magicindex', ['secret' => $multimediaObject->getSecret()]);
        $url = $this->cleanUrl($url);
        $endpoint = $this->router->match($url);

        return $endpoint['_controller'];
    }

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

    /**
     * @param string $url
     *
     * @return mixed
     */
    private function cleanUrl($url)
    {
        return str_replace('app_dev.php/', '', $url);
    }
}
