<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;

/**
 * @internal
 * @coversNothing
 */
final class SeriesTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $series_type = new SeriesType();
        $announce = true;
        $publicDate = new \DateTime('now');
        $title = 'title';
        $subtitle = 'subtitle';
        $description = 'description';
        $header = 'header';
        $footer = 'footer';
        $keyword = 'keyword';
        $line2 = 'line2';
        $locale = 'en';
        $properties = ['property1', 'property2'];

        $series = new Series();

        $series->setSeriesType($series_type);
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setHeader($header);
        $series->setFooter($footer);
        $series->setKeyword($keyword);
        $series->setLine2($line2);
        $series->setLocale($locale);
        $series->setProperties($properties);

        static::assertSame($series_type, $series->getSeriesType());
        static::assertSame($announce, $series->getAnnounce());
        static::assertSame($publicDate, $series->getPublicDate());
        static::assertSame($title, $series->getTitle());
        static::assertSame($subtitle, $series->getSubtitle());
        static::assertSame($description, $series->getDescription());
        static::assertSame($header, $series->getHeader());
        static::assertSame($footer, $series->getFooter());
        static::assertSame($keyword, $series->getKeyword());
        static::assertSame($line2, $series->getLine2());
        static::assertSame($locale, $series->getLocale());
        static::assertSame($properties, $series->getProperties());

        $titleEs = 'título';
        $subtitleEs = 'subtítulo';
        $descriptionEs = 'descripción';
        $headerEs = 'cabecera';
        $footerEs = 'pie';
        $keywordEs = 'palabra clave';
        $line2Es = 'línea 2';
        $localeEs = 'es';

        $titleI18n = [$locale => $title, $localeEs => $titleEs];
        $subtitleI18n = [$locale => $subtitle, $localeEs => $subtitleEs];
        $descriptionI18n = [$locale => $description, $localeEs => $descriptionEs];
        $headerI18n = [$locale => $header, $localeEs => $headerEs];
        $footerI18n = [$locale => $footer, $localeEs => $footerEs];
        $keywordI18n = [$locale => $keyword, $localeEs => $keywordEs];
        $line2I18n = [$locale => $line2, $localeEs => $line2Es];

        $series->setI18nTitle($titleI18n);
        $series->setI18nSubtitle($subtitleI18n);
        $series->setI18nDescription($descriptionI18n);
        $series->setI18nHeader($headerI18n);
        $series->setI18nFooter($footerI18n);
        $series->setI18nKeyword($keywordI18n);
        $series->setI18nLine2($line2I18n);

        static::assertSame($titleI18n, $series->getI18nTitle());
        static::assertSame($subtitleI18n, $series->getI18nSubtitle());
        static::assertSame($descriptionI18n, $series->getI18nDescription());
        static::assertSame($headerI18n, $series->getI18nHeader());
        static::assertSame($footerI18n, $series->getI18nFooter());
        static::assertSame($keywordI18n, $series->getI18nKeyword());
        static::assertSame($line2I18n, $series->getI18nLine2());

        $title = null;
        $subtitle = null;
        $description = null;
        $header = null;
        $footer = null;
        $keyword = null;
        $line2 = null;

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setHeader($header);
        $series->setFooter($footer);
        $series->setKeyword($keyword);
        $series->setLine2($line2);

        static::assertNull($series->getTitle());
        static::assertNull($series->getSubtitle());
        static::assertNull($series->getDescription());
        static::assertNull($series->getHeader());
        static::assertNull($series->getFooter());
        static::assertNull($series->getKeyword());
        static::assertNull($series->getLine2());
    }

    public function testToString()
    {
        $series = new Series();
        static::assertSame($series->getTitle(), $series->__toString());
    }

    public function testPicsInSeries()
    {
        $url = realpath(__DIR__.'/../Resources').\DIRECTORY_SEPARATOR.'logo.png';
        $pic = new Pic();
        $pic->setUrl($url);

        $series = new Series();

        static::assertSame(0, \count($series->getPics()));

        $series->addPic($pic);

        static::assertSame(1, \count($series->getPics()));
        static::assertTrue($series->containsPic($pic));

        $series->removePic($pic);

        static::assertSame(0, \count($series->getPics()));
        static::assertFalse($series->containsPic($pic));

        $picWithoutUrl = new Pic();

        $series->addPic($picWithoutUrl);
        $series->addPic($pic);

        static::assertSame(2, \count($series->getPics()));
        static::assertSame($url, $series->getFirstUrlPic());
    }

    public function testIsCollection()
    {
        $series = new Series();
        static::assertTrue($series->isCollection());
    }
}
