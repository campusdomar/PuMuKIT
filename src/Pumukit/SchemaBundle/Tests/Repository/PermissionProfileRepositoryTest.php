<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\PermissionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class PermissionProfileRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $permissionService;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(PermissionProfile::class);

        $this->dm->getDocumentCollection(PermissionProfile::class)
            ->remove([])
        ;
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testEmpty()
    {
        static::assertEmpty($this->repo->findAll());
    }

    public function testRepository()
    {
        static::assertCount(0, $this->repo->findAll());

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findAll());

        static::assertSame($permissionProfile, $this->repo->find($permissionProfile->getId()));
    }

    public function testChangeDefault()
    {
        static::assertCount(0, $this->repo->findByDefault(true));
        static::assertCount(0, $this->repo->findByDefault(false));

        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setSystem(true);
        $permissionProfile1->setDefault(true);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setSystem(true);
        $permissionProfile2->setDefault(false);

        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setSystem(true);
        $permissionProfile3->setDefault(false);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        static::assertCount(1, $this->repo->findByDefault(true));
        static::assertCount(2, $this->repo->findByDefault(false));
        static::assertSame($permissionProfile1, $this->repo->findOneByDefault(true));

        $permissionProfile4 = new PermissionProfile();
        $permissionProfile4->setName('test4');
        $permissionProfile4->setSystem(false);
        $permissionProfile4->setDefault(true);

        $this->dm->persist($permissionProfile4);
        $this->dm->flush();

        $this->repo->changeDefault($permissionProfile4);

        static::assertCount(1, $this->repo->findByDefault(true));
        static::assertCount(3, $this->repo->findByDefault(false));
        static::assertSame($permissionProfile4, $this->repo->findOneByDefault(true));
    }

    public function testFindDefaultCandidate()
    {
        $externalPermissions = [
            [
                'role' => 'ROLE_ONE',
                'description' => 'Access One',
            ],
        ];
        $permissionService = new PermissionService($this->dm, $externalPermissions);
        $totalPermissions = \count($permissionService->getAllPermissions());

        static::assertNull($this->repo->findDefaultCandidate($totalPermissions));

        $permissions1 = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_LIVE_CHANNELS];
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setPermissions($permissions1);

        $permissions2 = [];
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setPermissions($permissions2);

        $permissions3 = [Permission::ACCESS_DASHBOARD];
        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setPermissions($permissions3);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        static::assertEmpty($this->repo->findByDefault(true));
        static::assertNotEmpty($this->repo->findByDefault(false));

        static::assertSame($permissionProfile2, $this->repo->findDefaultCandidate($totalPermissions));
    }

    public function testRank()
    {
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setSystem(true);
        $permissionProfile1->setDefault(true);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setSystem(true);
        $permissionProfile2->setDefault(false);

        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setSystem(true);
        $permissionProfile3->setDefault(false);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        static::assertSame(0, $permissionProfile1->getRank());
        static::assertSame(1, $permissionProfile2->getRank());
        static::assertSame(2, $permissionProfile3->getRank());
    }
}
