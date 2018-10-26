<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class EmbedRelationsTest extends WebTestCase
{
    private $dm;
    private $repoMmobjs;
    private $repoTags;
    private $qb;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repoMmobjs = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoTags = $this->dm
            ->getRepository('PumukitSchemaBundle:Tag');

        //DELETE DATABASE
        // pimo has to be deleted before mmobj
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repoMmobjs = null;
        $this->repoTags = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repoMmobjs->findAll()));
        $this->assertEquals(0, count($this->repoTags->findAll()));
    }

    public function testCreateRootTag()
    {
        $tag = new Tag();
        $tag->setCod('ROOT');

        $this->dm->persist($tag);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        $this->assertEquals(1, count($this->repoTags->findAll()));
    }

    public function testGetChildren()
    {
        $this->createTestTree();

        $tag = $this->repoTags->findOneByCod('ROOT');
        $tree = $this->repoTags->getTree($tag);
        $this->assertEquals(6, count($tree));
        $children = $this->repoTags->getChildren($tag);
        $this->assertEquals(5, count($children));
        $this->assertEquals(5, $this->repoTags->childCount($tag));
        $directChildren = $this->repoTags->getChildren($tag, true);
        $this->assertEquals(2, count($directChildren));

        $tag = $this->repoTags->findOneByCod('B');
        $tree = $this->repoTags->getTree($tag);
        $this->assertEquals(4, count($tree));
        $children = $this->repoTags->getChildren($tag);
        $this->assertEquals(3, count($children));
        $this->assertEquals(3, $this->repoTags->childCount($tag));
        $directChildren = $this->repoTags->getChildren($tag, true);
        $this->assertEquals(2, count($directChildren));
    }

    public function testGetRootNodes()
    {
        $this->createTestTree();

        $tree = $this->repoTags->getRootNodes();
        $this->assertEquals(1, count($tree));
    }

    public function testTagEmptyInMultimediaObject()
    {
        $this->createTestMultimediaObject();

        $this->assertEquals(0, count($this->repoMmobjs->findOneByDuration(300)->getTags()));
    }

    public function testAddTagToMultimediaObject()
    {
        $this->createTestTree();
        $this->createTestMultimediaObject();
        $this->addTagToMultimediaObject();

        $this->assertEquals(1, count($this->repoMmobjs->findOneByDuration(300)->getTags()));
        $this->assertEquals('B2A', $this->repoTags->findOneByCod('B2A')->getCod());
    }

    public function testAddAndRemoveTagToMultimediaObject()
    {
        $this->createTestTree();
        $this->createTestMultimediaObject();
        $this->addTagToMultimediaObject();
        $this->removeTagFromMultimediaObject();

        $this->assertEquals(0, count($this->repoMmobjs->findOneByDuration(300)->getTags()));
        $this->assertEquals('B2A', $this->repoTags->findOneByCod('B2A')->getCod());
    }

    private function createTestTree()
    {
        $tag = new Tag();
        $tag->setCod('ROOT');

        $this->dm->persist($tag);
        $this->dm->flush();

        $tagA = new Tag();
        $tagA->setCod('A');
        $tagA->setParent($tag);
        $this->dm->persist($tagA);

        $tagB = new Tag();
        $tagB->setCod('B');
        $tagB->setParent($tag);
        $this->dm->persist($tagB);

        $tagB1 = new Tag();
        $tagB1->setCod('B1');
        $tagB1->setParent($tagB);
        $this->dm->persist($tagB1);

        $tagB2 = new Tag();
        $tagB2->setCod('B2');
        $tagB2->setParent($tagB);
        $this->dm->persist($tagB2);

        $tagB2A = new Tag();
        $tagB2A->setCod('B2A');
        $tagB2A->setParent($tagB2);
        $this->dm->persist($tagB2A);

        $this->dm->flush();
    }

    private function createTestMultimediaObject()
    {
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = 'titulo cualquiera';
        $subtitle = 'Subtitle paragraph';
        $description = 'Description text';
        $duration = 300;

        $mmobj = new MultimediaObject();
        $mmobj->setStatus($status);
        $mmobj->setRecordDate($record_date);
        $mmobj->setPublicDate($public_date);
        $mmobj->setTitle($title);
        $mmobj->setSubtitle($subtitle);
        $mmobj->setDescription($description);
        $mmobj->setDuration($duration);

        $this->dm->persist($mmobj);
        $this->dm->flush();
    }

    private function addTagToMultimediaObject()
    {
        $tagB2A = $this->repoTags->findOneByCod('B2A');
        $mmobj = $this->repoMmobjs->findOneByDuration(300);

        $mmobj->addTag($tagB2A);
        $this->dm->persist($mmobj);
        $this->dm->flush();
    }

    private function removeTagFromMultimediaObject()
    {
        $tagB2A = $this->repoTags->findOneByCod('B2A');
        $mmobj = $this->repoMmobjs->findOneByDuration(300);

        $hasRemoved = $mmobj->removeTag($tagB2A);

        $this->dm->persist($mmobj);
        $this->dm->flush();
    }
}
