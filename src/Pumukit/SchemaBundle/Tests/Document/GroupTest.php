<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Group;

/**
 * @internal
 * @coversNothing
 */
final class GroupTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = 'Group A';
        $comments = 'Group created to test setter and getter';
        $origin = Group::ORIGIN_LOCAL;
        $updatedAt = new \DateTime();

        $group->setKey($key);
        $group->setName($name);
        $group->setComments($comments);
        $group->setOrigin($origin);
        $group->setUpdatedAt($updatedAt);

        static::assertSame($key, $group->getKey());
        static::assertSame($key, (string) $group);
        static::assertSame($name, $group->getName());
        static::assertSame($comments, $group->getComments());
        static::assertSame($origin, $group->getOrigin());
        static::assertSame($updatedAt, $group->getUpdatedAt());
    }

    public function testGroupInterface()
    {
        $group = new Group();

        $key = 'GROUPA';
        $name = 'Group A';

        $group->setKey($key);
        $group->setName($name);

        static::assertSame($group, $group->addRole('role_test'));
        static::assertFalse($group->hasRole('role_test'));
        static::assertSame([], $group->getRoles());
        static::assertSame($group, $group->removeRole('role_test'));
        static::assertSame($group, $group->setRoles([]));
    }
}
