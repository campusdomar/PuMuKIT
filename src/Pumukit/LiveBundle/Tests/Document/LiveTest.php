<?php

namespace Pumukit\LiveBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\LiveBundle\Document\Live;

/**
 * @internal
 * @coversNothing
 */
final class LiveTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $url = 'http://www.pumukit.com/liveo1';
        $passwd = 'password';
        $live_type = Live::LIVE_TYPE_FMS;
        $width = 640;
        $height = 480;
        $qualities = 'high';
        $ip_source = '127.0.0.1';
        $source_name = 'localhost';
        $index_play = 1;
        $broadcasting = 1;
        $debug = 1;
        $locale = 'en';
        $name = 'liveo 1';
        $description = 'liveo channel';
        $resolution = ['width' => $width, 'height' => $height];

        $liveo = new Live();

        $liveo->setUrl($url);
        $liveo->setPasswd($passwd);
        $liveo->setLiveType($live_type);
        $liveo->setWidth($width);
        $liveo->setHeight($height);
        $liveo->setQualities($qualities);
        $liveo->setIpSource($ip_source);
        $liveo->setSourceName($source_name);
        $liveo->setIndexPlay($index_play);
        $liveo->setBroadcasting($broadcasting);
        $liveo->setDebug($debug);
        $liveo->setLocale($locale);
        $liveo->setName($name, $locale);
        $liveo->setDescription($description, $locale);
        $liveo->setResolution($resolution);

        static::assertSame($url, $liveo->getUrl());
        static::assertSame($passwd, $liveo->getPasswd());
        static::assertSame($live_type, $liveo->getLiveType());
        static::assertSame($width, $liveo->getWidth());
        static::assertSame($height, $liveo->getHeight());
        static::assertSame($qualities, $liveo->getQualities());
        static::assertSame($ip_source, $liveo->getIpSource());
        static::assertSame($source_name, $liveo->getSourceName());
        static::assertSame($index_play, $liveo->getIndexPlay());
        static::assertSame($broadcasting, $liveo->getBroadcasting());
        static::assertSame($debug, $liveo->getDebug());
        static::assertSame($locale, $liveo->getLocale());
        static::assertSame($name, $liveo->getName($liveo->getLocale()));
        static::assertSame($name, $liveo->getName());
        static::assertSame($description, $liveo->getDescription($liveo->getLocale()));
        static::assertSame($description, $liveo->getDescription());
        static::assertSame($resolution, $liveo->getResolution());

        $liveo->setDescription($description);
        static::assertSame($description, $liveo->getDescription($liveo->getLocale()));

        $nameEs = 'directo 1';
        $i18nName = ['en' => $name, 'es' => $nameEs];
        $liveo->setI18nName($i18nName);
        static::assertSame($i18nName, $liveo->getI18nName());

        $descriptionEs = 'canal de directos';
        $i18nDescription = ['en' => $description, 'es' => $descriptionEs];
        $liveo->setI18nDescription($i18nDescription);
        static::assertSame($i18nDescription, $liveo->getI18nDescription());

        $name = null;
        $liveo->setName($name, $locale);
        static::assertNull($liveo->getName($liveo->getLocale()));

        $description = null;
        $liveo->setDescription($description, $locale);
        static::assertNull($liveo->getDescription($liveo->getLocale()));
    }

    public function testCloneResource()
    {
        $live = new Live();

        static::assertSame($live, $live->cloneResource());
    }

    public function testToString()
    {
        $live = new Live();

        static::assertSame($live->getName(), $live->__toString());
    }

    public function testIsValidLiveType()
    {
        $live = new Live();

        $live_type = Live::LIVE_TYPE_FMS;
        $live->setLiveType($live_type);

        static::assertTrue($live->isValidLiveType());
    }
}
