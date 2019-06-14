<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Role;

/**
 * @internal
 * @coversNothing
 */
final class RoleTest extends TestCase
{
    public function testDefaults()
    {
        $role = new Role();

        static::assertSame('0', $role->getCod());
        static::assertTrue($role->getDisplay());
        static::assertSame(0, $role->getNumberPeopleInMultimediaObject());
        static::assertSame($role, $role->cloneResource());
    }

    public function testGetterAndSetter()
    {
        $role = new Role();

        $locale = 'en';
        $cod = 'rol1'; //String - max length = 5
        $xml = 'string <xml>';
        $display = true;
        $name1 = 'Presenter';
        $name2 = null;
        $text1 = 'Presenter Role 1';
        $text2 = null;

        $role->setLocale($locale);
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name1);
        $role->setText($text1);

        static::assertSame($locale, $role->getLocale());
        static::assertSame($cod, $role->getCod());
        static::assertSame($xml, $role->getXml());
        static::assertSame($display, $role->getDisplay());
        static::assertSame($name1, $role->getName());
        static::assertSame($text1, $role->getText());

        $role->setName($name2);
        $role->setText($text2);

        static::assertSame($name2, $role->getName());
        static::assertSame($text2, $role->getText());

        $nameEs = 'Presentador';
        $textEs = 'Rol de presentador 1';

        $i18nName = ['en' => $name1, 'es' => $nameEs];
        $i18nText = ['en' => $text1, 'es' => $textEs];

        $role->setI18nName($i18nName);
        $role->setI18nText($i18nText);

        static::assertSame($i18nName, $role->getI18nName());
        static::assertSame($i18nText, $role->getI18nText());
    }

    public function testNumberPeopleInMultimediaObject()
    {
        $role = new Role();

        static::assertSame(0, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        static::assertSame(1, $role->getNumberPeopleInMultimediaObject());

        $role->increaseNumberPeopleInMultimediaObject();
        $role->increaseNumberPeopleInMultimediaObject();
        static::assertSame(3, $role->getNumberPeopleInMultimediaObject());

        $role->decreaseNumberPeopleInMultimediaObject();
        static::assertSame(2, $role->getNumberPeopleInMultimediaObject());

        $role->setNumberPeopleInMultimediaObject(3);
        static::assertSame(3, $role->getNumberPeopleInMultimediaObject());
    }
}
