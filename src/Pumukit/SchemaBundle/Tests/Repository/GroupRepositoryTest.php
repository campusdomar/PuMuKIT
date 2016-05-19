<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Group;

class GroupRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Group');

        //DELETE DATABASE
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')
            ->remove(array());
        $this->dm->flush();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $group = new Group();

        $group->setKey('GroupA');
        $group->setName('Group A');

        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
    }

    public function testFindByIdNotIn()
    {
        $group1 = new Group();
        $group1->setKey('Group1');
        $group1->setName('Group 1');

        $group2 = new Group();
        $group2->setKey('Group2');
        $group2->setName('Group 2');

        $group3 = new Group();
        $group3->setKey('Group3');
        $group3->setName('Group 3');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->flush();

        $ids = array(new \MongoId($group1->getId()), new \MongoId($group3->getId()));
        $groups = $this->repo->findByIdNotIn($ids)->toArray();
        $this->assertFalse(in_array($group1, $groups));
        $this->assertTrue(in_array($group2, $groups));
        $this->assertFalse(in_array($group3, $groups));
    }
}