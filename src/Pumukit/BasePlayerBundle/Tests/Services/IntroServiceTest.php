<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\IntroService;

/**
 * @internal
 * @coversNothing
 */
final class IntroServiceTest extends TestCase
{
    public static $testIntro = 'https://videos.net/video.mp4';
    public static $testCustomIntro = 'https://videos.net/video_objmm.mp4';

    public function testWithoutIntro()
    {
        $service = new IntroService(null);
        static::assertFalse($service->getIntro());
        static::assertFalse($service->getIntro(null));
        static::assertFalse($service->getIntro(false));
        static::assertFalse($service->getIntro(true));
        static::assertFalse($service->getIntro('false'));
        static::assertFalse($service->getIntro('true'));
        static::assertFalse($service->getIntro('https://videos.net/stock-footage-suzie-the-bomb-cat-on-the-prowl.mp4'));
        static::assertFalse($service->getIntroForMultimediaObject());
        static::assertFalse($service->getIntroForMultimediaObject(null, null));
        static::assertFalse($service->getIntroForMultimediaObject(null, true));
        static::assertFalse($service->getIntroForMultimediaObject(null, false));
        static::assertFalse($service->getIntroForMultimediaObject(false, null));
        static::assertFalse($service->getIntroForMultimediaObject(false, true));
        static::assertFalse($service->getIntroForMultimediaObject(false, false));
        static::assertSame(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, null));
        static::assertSame(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, true));
        static::assertFalse($service->getIntroForMultimediaObject(static::$testCustomIntro, false));
    }

    public function testWithIntro()
    {
        $service = new IntroService(static::$testIntro);
        static::assertSame(static::$testIntro, $service->getIntro());
        static::assertSame(static::$testIntro, $service->getIntro(null));
        static::assertSame(static::$testIntro, $service->getIntro(true));
        static::assertSame(static::$testIntro, $service->getIntro('true'));
        static::assertFalse($service->getIntro(false));
        static::assertFalse($service->getIntro('false'));
        static::assertFalse($service->getIntro('https://videos.net/stock-footage-suzie-the-bomb-cat-on-the-prowl.mp4'));
        static::assertSame(static::$testIntro, $service->getIntroForMultimediaObject());
        static::assertSame(static::$testIntro, $service->getIntroForMultimediaObject(null, null));
        static::assertSame(static::$testIntro, $service->getIntroForMultimediaObject(null, true));
        static::assertFalse($service->getIntroForMultimediaObject(null, false));
        static::assertFalse($service->getIntroForMultimediaObject(false, null));
        static::assertFalse($service->getIntroForMultimediaObject(false, true));
        static::assertFalse($service->getIntroForMultimediaObject(false, false));
        static::assertSame(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, null));
        static::assertSame(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, true));
        static::assertFalse($service->getIntroForMultimediaObject(static::$testCustomIntro, false));
    }
}
