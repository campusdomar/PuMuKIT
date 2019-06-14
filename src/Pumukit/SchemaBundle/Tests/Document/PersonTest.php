<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Person;

/**
 * @internal
 * @coversNothing
 */
final class PersonTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $email = 'email@email.com';
        $name = 'name';
        $web = 'web';
        $phone = 'phone';
        $honorific = 'Mr';
        $firm = 'firm';
        $post = 'post';
        $bio = 'Biography of this person';
        $locale = 'es';

        $person = new Person();

        $person->setLocale($locale);
        $person->setEmail($email);
        $person->setName($name);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        static::assertSame($email, $person->getEmail());
        static::assertSame($name, $person->getName());
        static::assertSame($web, $person->getWeb());
        static::assertSame($phone, $person->getPhone());
        static::assertSame($honorific, $person->getHonorific());
        static::assertSame($firm, $person->getFirm());
        static::assertSame($post, $person->getPost());
        static::assertSame($bio, $person->getBio());
        static::assertSame($locale, $person->getLocale());

        static::assertSame($honorific.' '.$name, $person->getHName());
        static::assertSame($post.' '.$firm.' '.$bio, $person->getOther());
        static::assertSame($post.', '.$firm.', '.$bio, $person->getInfo());

        $bio = '';
        $person->setBio($bio);
        static::assertSame($post.', '.$firm, $person->getInfo());

        $honorificEs = 'Don';
        $firmEs = 'Firma de esta persona';
        $postEs = 'Post de esta persona';
        $bioEs = 'Biografía de esta persona';

        $i18nHonorific = ['en' => $honorific, 'es' => $honorificEs];
        $i18nFirm = ['en' => $firm, 'es' => $firmEs];
        $i18nPost = ['en' => $post, 'es' => $postEs];
        $i18nBio = ['en' => $bio, 'es' => $bioEs];

        $person->setI18nHonorific($i18nHonorific);
        $person->setI18nFirm($i18nFirm);
        $person->setI18nPost($i18nPost);
        $person->setI18nBio($i18nBio);

        static::assertSame($i18nHonorific, $person->getI18nHonorific());
        static::assertSame($i18nFirm, $person->getI18nFirm());
        static::assertSame($i18nPost, $person->getI18nPost());
        static::assertSame($i18nBio, $person->getI18nBio());

        $honorific = null;
        $firm = null;
        $post = null;
        $bio = null;

        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        static::assertSame($honorific, $person->getHonorific());
        static::assertSame($firm, $person->getFirm());
        static::assertSame($post, $person->getPost());
        static::assertSame($bio, $person->getBio());
    }

    public function testCloneResource()
    {
        $person = new Person();

        static::assertSame($person, $person->cloneResource());
    }
}
