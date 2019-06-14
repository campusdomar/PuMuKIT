<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;

/**
 * @internal
 * @coversNothing
 */
final class EmbeddedEventTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $name = 'Embedded Event 1';
        $description = 'Description of the event';
        $author = 'Author of the event';
        $producer = 'Producer of the event';
        $place = 'Place of the event';
        $date = new \DateTime('2018-02-01 09:00:00');
        $duration = 90;
        $display = true;
        $create_serial = false;
        $embeddedEventSession = new ArrayCollection();
        $live = new Live();
        $url = 'https://test.com';
        $alreadyHeldMessage = ['en' => 'The event has been already held.'];
        $notYetHeldMessage = ['en' => 'The event has not yet been alread held.'];
        $locale = 'en';

        $embeddedEvent = new EmbeddedEvent();
        $embeddedEvent->setName($name);
        $embeddedEvent->setDescription($description);
        $embeddedEvent->setAuthor($author);
        $embeddedEvent->setProducer($producer);
        $embeddedEvent->setPlace($place);
        $embeddedEvent->setDate($date);
        $embeddedEvent->setDuration($duration);
        $embeddedEvent->setDisplay($display);
        $embeddedEvent->setCreateSerial($create_serial);
        $embeddedEvent->setEmbeddedEventSession($embeddedEventSession);
        $embeddedEvent->setLive($live);
        $embeddedEvent->setUrl($url);
        $embeddedEvent->setAlreadyHeldMessage($alreadyHeldMessage);
        $embeddedEvent->setNotYetHeldMessage($notYetHeldMessage);
        $embeddedEvent->setLocale($locale);

        static::assertSame($name, $embeddedEvent->getName());
        static::assertSame($description, $embeddedEvent->getDescription());
        static::assertSame($author, $embeddedEvent->getAuthor());
        static::assertSame($producer, $embeddedEvent->getProducer());
        static::assertSame($place, $embeddedEvent->getPlace());
        static::assertSame($date, $embeddedEvent->getDate());
        static::assertSame($duration, $embeddedEvent->getDuration());
        static::assertSame($display, $embeddedEvent->isDisplay());
        static::assertSame($create_serial, $embeddedEvent->isCreateSerial());
        static::assertSame($embeddedEventSession->toArray(), $embeddedEvent->getEmbeddedEventSession());
        static::assertSame($live, $embeddedEvent->getLive());
        static::assertSame($url, $embeddedEvent->getUrl());
        static::assertSame($alreadyHeldMessage, $embeddedEvent->getAlreadyHeldMessage());
        static::assertSame($notYetHeldMessage, $embeddedEvent->getNotYetHeldMessage());
        static::assertSame($locale, $embeddedEvent->getLocale());
    }
}
