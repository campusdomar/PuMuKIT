<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Broadcast;
// @deprecated in version 2.3
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
final class BroadcastTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $locale = 'en';
        $broadcastTypeId = Broadcast::BROADCAST_TYPE_PRI;
        $name = 'Private';
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = 'Private broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        static::assertSame($locale, $broadcast->getLocale());
        static::assertSame($name, $broadcast->getName());
        static::assertSame($broadcastTypeId, $broadcast->getBroadcastTypeId());
        static::assertSame($passwd, $broadcast->getPasswd());
        static::assertSame($defaultSel, $broadcast->getDefaultSel());
        static::assertSame($descriptionEn, $broadcast->getDescription());
        static::assertSame($descriptionEn, $broadcast->getDescription($locale));

        $descriptionEs = 'Difusión privada';
        $i18nDescription = ['en' => $descriptionEn, 'es' => $descriptionEs];

        $broadcast->setI18nDescription($i18nDescription);

        static::assertSame($i18nDescription, $broadcast->getI18nDescription());

        static::assertSame('', $broadcast->getDescription('fr'));
        static::assertNull($broadcast->getId());
    }

    public function testCloneResource()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        static::assertSame($broadcast, $broadcast->cloneResource());
    }

    public function testToString()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);

        static::assertSame($broadcast->getName(), $broadcast->__toString());
    }

    public function testNumberMultimediaObjects()
    {
        $privateBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI);
        $publicBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);
        $corporativeBroadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_COR);

        $mm1 = new MultimediaObject();
        $mm2 = new MultimediaObject();
        $mm3 = new MultimediaObject();
        $mm4 = new MultimediaObject();
        $mm5 = new MultimediaObject();

        $mm1->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $mm2->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mm3->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm4->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm5->setStatus(MultimediaObject::STATUS_HIDDEN);

        $mm1->setBroadcast($privateBroadcast);
        $mm2->setBroadcast($privateBroadcast);
        $mm3->setBroadcast($publicBroadcast);
        $mm4->setBroadcast($corporativeBroadcast);
        $mm5->setBroadcast($privateBroadcast);

        static::assertSame(2, $privateBroadcast->getNumberMultimediaObjects());
        static::assertSame(1, $publicBroadcast->getNumberMultimediaObjects());
        static::assertSame(1, $corporativeBroadcast->getNumberMultimediaObjects());

        $publicBroadcast->setNumberMultimediaObjects(3);
        static::assertSame(3, $publicBroadcast->getNumberMultimediaObjects());

        $privateBroadcast->setNumberMultimediaObjects(3);
        $privateBroadcast->decreaseNumberMultimediaObjects();
        static::assertSame(2, $privateBroadcast->getNumberMultimediaObjects());
    }

    private function createBroadcast($broadcastTypeId)
    {
        $locale = 'en';
        $name = ucfirst($broadcastTypeId);
        $passwd = 'password';
        $defaultSel = true;
        $descriptionEn = ucfirst($broadcastTypeId).' broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($descriptionEn, $locale);

        return $broadcast;
    }
}
