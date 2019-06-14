<?php

namespace Pumukit\LiveBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\LiveBundle\Document\Event;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Pic;

/**
 * @internal
 * @coversNothing
 */
final class EventTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $live = new Live();
        $name = 'event name';
        $place = 'event place';
        $date = new \DateTime();
        $duration = '60';
        $display = 0;
        $create_serial = 0;
        $locale = 'en';
        $schedule = ['date' => $date, 'duration' => $duration];

        $pic = new Pic();
        $imagePath = '/path/to/image.jpg';
        $pic->setPath($imagePath);

        $event = new Event();

        $event->setLive($live);
        $event->setName($name);
        $event->setPlace($place);
        $event->setDate($date);
        $event->setDuration($duration);
        $event->setDisplay($display);
        $event->setCreateSerial($create_serial);
        $event->setPic($pic);
        $event->setLocale($locale);
        $event->setSchedule($schedule);

        static::assertSame($live, $event->getLive());
        static::assertSame($name, $event->getName());
        static::assertSame($place, $event->getPlace());
        static::assertSame($date, $event->getDate());
        static::assertSame($duration, $event->getDuration());
        static::assertSame($display, $event->getDisplay());
        static::assertSame($create_serial, $event->getCreateSerial());
        static::assertSame($locale, $event->getLocale());
        static::assertSame($pic, $event->getPic());
        static::assertSame($schedule, $event->getSchedule());
    }
}
