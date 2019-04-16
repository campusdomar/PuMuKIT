<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectService
{
    private $dm;

    /**
     * MultimediaObjectSyncService constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    protected function getMultimediaObjectTypes()
    {
        return MultimediaObject::$typeTexts;
    }
}
