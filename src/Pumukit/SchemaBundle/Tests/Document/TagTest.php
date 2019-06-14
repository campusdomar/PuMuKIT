<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class TagTest extends WebTestCase
{
    private $dm;
    private $tagRepo;
    private $tagService;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->tagRepo = $this->dm
            ->getRepository(Tag::class)
        ;

        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');

        $this->dm->getDocumentCollection(Tag::class)
            ->remove([])
        ;
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->tagRepo = null;
        $this->tagService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetterAndSetter()
    {
        $title = 'title';
        $description = 'description';
        $slug = 'slug';
        $cod = 23;
        $metatag = true;
        $created = new \DateTime('now');
        $updated = new \DateTime('now');
        $display = true;
        $youtubeProperty = 'w7dD-JJJytM&list=PLmXxqSJJq-yUfrjvKe5c5LX_1x7nGVF6c';
        $properties = ['youtube' => $youtubeProperty];

        $tag = new Tag();

        $tag->setTitle($title);
        $tag->setDescription($description);
        $tag->setSlug($slug);
        $tag->setCod($cod);
        $tag->setMetatag($metatag);
        $tag->setCreated($created);
        $tag->setUpdated($updated);
        $tag->setDisplay($display);
        $tag->setProperties($properties);

        $tag_parent = new Tag();
        $tag->setParent($tag_parent);

        static::assertSame($title, $tag->getTitle());
        static::assertSame($description, $tag->getDescription());
        static::assertSame($slug, $tag->getSlug());
        static::assertSame($cod, $tag->getCod());
        static::assertSame($metatag, $tag->getMetatag());
        static::assertSame($created, $tag->getCreated());
        static::assertSame($updated, $tag->getUpdated());
        static::assertSame($tag_parent, $tag->getParent());
        static::assertSame($display, $tag->getDisplay());
        static::assertSame($properties, $tag->getProperties());
        static::assertNull($tag->getLockTime());

        static::assertSame('', $tag->getTitle('fr'));
        static::assertSame('', $tag->getDescription('fr'));

        $titleEs = 'título';
        $titleArray = ['en' => $title, 'es' => $titleEs];
        $descriptionEs = 'descripción';
        $descriptionArray = ['en' => $description, 'es' => $descriptionEs];

        $tag->setI18nTitle($titleArray);
        $tag->setI18nDescription($descriptionArray);

        static::assertSame($titleArray, $tag->getI18nTitle());
        static::assertSame($descriptionArray, $tag->getI18nDescription());

        static::assertSame($tag->getTitle(), $tag->__toString());

        $testProperty = 'test property';
        $tag->setProperty('test', $testProperty);
        static::assertSame($youtubeProperty, $tag->getProperty('youtube'));
        static::assertSame($testProperty, $tag->getProperty('test'));

        $testProperty = null;
        $tag->setProperty('test', $testProperty);
        static::assertSame($testProperty, $tag->getProperty('test'));
    }

    public function testNumberMultimediaObjects()
    {
        $tag = new Tag();
        static::assertSame(0, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        static::assertSame(1, $tag->getNumberMultimediaObjects());

        $tag->increaseNumberMultimediaObjects();
        static::assertSame(2, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        static::assertSame(1, $tag->getNumberMultimediaObjects());

        $tag->decreaseNumberMultimediaObjects();
        static::assertSame(0, $tag->getNumberMultimediaObjects());

        $count = 5;
        $tag->setNumberMultimediaObjects($count);
        static::assertSame(5, $tag->getNumberMultimediaObjects());
    }

    public function testChildren()
    {
        $tag_parent = new Tag();
        $tag_child = new Tag();
        $tag_parent->setCod('Parent');
        $tag_child->setCod('ParentChild');
        $tag_grandchild = new Tag();
        $tag_grandchild->setCod('GrandChild');
        $this->dm->persist($tag_parent);
        $this->dm->persist($tag_child);
        $this->dm->persist($tag_grandchild);
        $this->dm->flush();

        static::assertNull($tag_parent->getParent());
        static::assertFalse($tag_parent->isChildOf($tag_child));
        static::assertFalse($tag_child->isChildOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOf($tag_child));
        static::assertFalse($tag_child->isDescendantOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOfByCod($tag_child->getCod()));
        static::assertFalse($tag_child->isDescendantOfByCod($tag_child->getCod()));

        $tag_child->setParent($tag_parent);
        $tag_grandchild->setParent($tag_child);
        $this->dm->persist($tag_child);
        $this->dm->persist($tag_parent);
        $this->dm->flush();

        static::assertSame('Parent|ParentChild|GrandChild|', $tag_grandchild->getPath());
        static::assertSame($tag_parent, $tag_child->getParent());
        static::assertTrue($tag_child->isChildOf($tag_parent));
        static::assertTrue($tag_grandchild->isDescendantOf($tag_parent));
        static::assertTrue($tag_child->isDescendantOfByCod($tag_parent->getCod()));
        static::assertTrue($tag_grandchild->isDescendantOfByCod($tag_parent->getCod()));

        static::assertFalse($tag_grandchild->isChildOf($tag_parent));
        static::assertFalse($tag_parent->isChildOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOf($tag_child));
        static::assertFalse($tag_parent->isDescendantOfByCod($tag_child->getCod()));
    }
}
