<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 * @coversNothing
 */
final class UserTest extends TestCase
{
    public function testSetterAndGetter()
    {
        $username = 'username';
        $fullname = 'fullname';
        $origin1 = User::ORIGIN_LOCAL;

        $user = new User();

        $user->setUsername($username);
        $user->setFullname($fullname);
        $user->setOrigin($origin1);
        static::assertSame($fullname, $user->getFullname());
        static::assertSame($origin1, $user->getOrigin());
        static::assertTrue($user->isLocal());

        $origin2 = 'ldap';
        $user->setOrigin($origin2);
        static::assertSame($origin2, $user->getOrigin());
        static::assertFalse($user->isLocal());
    }
}
