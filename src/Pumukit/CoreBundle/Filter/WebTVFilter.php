<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class WebTVFilter extends BsonFilter
{
    /**
     * @param ClassMetadata $targetDocument
     *
     * @return array|void
     */
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
            return $this->getMediaCriteria();
        }
        if ("Pumukit\SchemaBundle\Document\Series" === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }

        return;
    }

    /**
     * @return array
     */
    protected function getMediaCriteria()
    {
        $criteria = [
            'ready' => true,
        ];

        return $criteria;
    }

    /**
     * @return array
     */
    protected function getSeriesCriteria()
    {
        $criteria = [
            'hide' => false,
        ];

        return $criteria;
    }
}
