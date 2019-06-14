<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Role;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class RoleRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(Role::class)
        ;

        //DELETE DATABASE
        $this->dm->getDocumentCollection(Role::class)->remove([]);
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

    public function testRepository()
    {
        $cod = 123;
        $xml = '<xml contenido del xml/>';
        $display = true;
        $name = 'rolename1';
        $text = 'Ahora prueba para ver si coge bien la base de datos';

        $role = new Role();
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setDisplay($display);
        $role->setName($name);
        $role->setText($text);

        $this->dm->persist($role);
        $this->dm->flush();

        // This should pass to check the unrequired fields
        static::assertSame(1, \count($this->repo->findAll()));
    }

    public function testRank()
    {
        $role1 = $this->getRole();

        $this->dm->persist($role1);
        $this->dm->flush();

        static::assertSame(0, $role1->getRank());

        $role2 = $this->getRole();

        $this->dm->persist($role2);
        $this->dm->flush();

        static::assertSame(1, $role2->getRank());

        $role3 = $this->getRole();
        $role4 = $this->getRole();

        $this->dm->persist($role3);
        $this->dm->persist($role4);
        $this->dm->flush();

        static::assertSame(2, $role3->getRank());
        static::assertSame(3, $role4->getRank());

        $roleFirst = $this->getRole();
        $roleFirst->setRank(0);

        $this->dm->persist($roleFirst);
        $this->dm->flush();

        static::assertSame(0, $roleFirst->getRank());
        static::assertSame(1, $role1->getRank());
        static::assertSame(4, $role4->getRank());

        $roleLast = $this->getRole();
        $roleLast->setRank(-1);

        $this->dm->persist($roleLast);
        $this->dm->flush();

        static::assertSame(0, $roleFirst->getRank());
        static::assertSame(1, $role1->getRank());
        static::assertSame(4, $role4->getRank());
        static::assertSame(5, $roleLast->getRank());

        $role1->setRank(-1);

        $this->dm->persist($role1);
        $this->dm->flush();

        static::assertSame(0, $roleFirst->getRank());
        static::assertSame(3, $role4->getRank());
        static::assertSame(4, $roleLast->getRank());
        static::assertSame(5, $role1->getRank());
    }

    private function getRole()
    {
        $rand = rand();

        $cod = $rand;
        $xml = "<xml contenido del xml {$rand} />";
        $name = "rolename{$rand}";
        $text = "text is {$rand}";

        $role = new Role();
        $role->setCod($cod);
        $role->setXml($xml);
        $role->setName($name);
        $role->setText($text);

        return $role;
    }
}
