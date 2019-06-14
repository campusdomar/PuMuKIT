<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class GroupRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Group::class);

        //DELETE DATABASE
        $this->dm->getDocumentCollection(Group::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        static::assertSame(0, \count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $group = new Group();

        $group->setKey('GroupA');
        $group->setName('Group A');

        $this->dm->persist($group);
        $this->dm->flush();

        static::assertSame(1, \count($this->repo->findAll()));
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

        $ids = [new \MongoId($group1->getId()), new \MongoId($group3->getId())];
        $groups = $this->repo->findByIdNotIn($ids)->toArray();
        static::assertFalse(\in_array($group1, $groups, true));
        static::assertTrue(\in_array($group2, $groups, true));
        static::assertFalse(\in_array($group3, $groups, true));
    }

    public function testFindByIdNotInOf()
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

        $group4 = new Group();
        $group4->setKey('Group4');
        $group4->setName('Group 4');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($group4);
        $this->dm->flush();

        $ids = [new \MongoId($group1->getId()), new \MongoId($group3->getId())];
        $total = [new \MongoId($group1->getId()), new \MongoId($group3->getId()), new \MongoId($group4->getId())];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        static::assertFalse(\in_array($group1, $groups, true));
        static::assertFalse(\in_array($group2, $groups, true));
        static::assertFalse(\in_array($group3, $groups, true));
        static::assertTrue(\in_array($group4, $groups, true));

        $ids = [];
        $total = [new \MongoId($group1->getId()), new \MongoId($group3->getId()), new \MongoId($group4->getId())];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        static::assertTrue(\in_array($group1, $groups, true));
        static::assertFalse(\in_array($group2, $groups, true));
        static::assertTrue(\in_array($group3, $groups, true));
        static::assertTrue(\in_array($group4, $groups, true));

        $ids = [new \MongoId($group1->getId()), new \MongoId($group3->getId())];
        $total = [];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        static::assertFalse(\in_array($group1, $groups, true));
        static::assertFalse(\in_array($group2, $groups, true));
        static::assertFalse(\in_array($group3, $groups, true));
        static::assertFalse(\in_array($group4, $groups, true));

        $ids = [];
        $total = [];
        $groups = $this->repo->findByIdNotInOf($ids, $total)->toArray();
        static::assertFalse(\in_array($group1, $groups, true));
        static::assertFalse(\in_array($group2, $groups, true));
        static::assertFalse(\in_array($group3, $groups, true));
        static::assertFalse(\in_array($group4, $groups, true));
    }
}
