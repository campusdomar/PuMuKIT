<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class UserRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $groupRepo;
    private $factoryService;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(User::class);
        $this->groupRepo = $this->dm->getRepository(Group::class);
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        //DELETE DATABASE
        $this->dm->getDocumentCollection(Person::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(PermissionProfile::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(User::class)
            ->remove([])
        ;
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
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        static::assertSame(0, \count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $user = new User();

        $user->setEmail('test@mail.com');
        $user->setUsername('test');

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertSame(1, \count($this->repo->findAll()));
    }

    public function testPerson()
    {
        $person = new Person();
        $user = new User();

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $person->setUser($user);
        $user->setPerson($person);

        $this->dm->persist($person);
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        static::assertSame($person, $user->getPerson());
    }

    public function testPermissionProfile()
    {
        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');
        $user = new User();

        $this->dm->persist($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setPermissionProfile($permissionProfile);

        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->repo->find($user->getId());

        static::assertSame($permissionProfile, $user->getPermissionProfile());
    }

    public function testUserGroups()
    {
        static::assertSame(0, \count($this->groupRepo->findAll()));

        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        static::assertSame(1, \count($this->groupRepo->findAll()));

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        static::assertSame(2, \count($this->groupRepo->findAll()));

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');
        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($user->containsGroup($group1));
        static::assertFalse($user->containsGroup($group2));
        static::assertSame(1, $user->getGroups()->count());

        $user->addGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertTrue($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertSame(2, $user->getGroups()->count());

        $user->removeGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertFalse($user->containsGroup($group1));
        static::assertTrue($user->containsGroup($group2));
        static::assertSame(1, $user->getGroups()->count());

        static::assertSame(2, \count($this->groupRepo->findAll()));
    }

    public function testGetGroupsIds()
    {
        $key1 = 'Group1';
        $name1 = 'Group 1';
        $group1 = $this->createGroup($key1, $name1);

        $key2 = 'Group2';
        $name2 = 'Group 2';
        $group2 = $this->createGroup($key2, $name2);

        $user = new User();
        $user->setEmail('testgroup@mail.com');
        $user->setUsername('testgroup');

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertSame(0, \count($user->getGroupsIds()));

        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertSame(1, \count($user->getGroupsIds()));

        $user->addGroup($group2);

        $this->dm->persist($user);
        $this->dm->flush();

        static::assertSame(2, \count($user->getGroupsIds()));
    }

    private function createGroup($key = 'Group1', $name = 'Group 1')
    {
        $group = new Group();

        $group->setKey($key);
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        return $group;
    }
}
