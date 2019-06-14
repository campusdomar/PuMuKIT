<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\UserEvent;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
final class UserEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $dm;
    private $userDispatcher;
    private $dispatcher;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb.odm.document_manager')
        ;
        $this->dispatcher = new EventDispatcher();

        $this->dm->getDocumentCollection(User::class)->remove([]);
        $this->dm->flush();

        MockUpUserListener::$called = false;
        MockUpUserListener::$name = self::EMPTY_NAME;

        $this->userDispatcher = new UserEventDispatcherService($this->dispatcher);
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->dispatcher = null;
        $this->userDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_CREATE, function ($event, $name) {
            static::assertTrue($event instanceof UserEvent);
            static::assertSame(SchemaEvents::USER_CREATE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        static::assertFalse(MockUpUserListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchCreate($user);

        static::assertTrue(MockUpUserListener::$called);
        static::assertSame($name, MockUpUserListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_UPDATE, function ($event, $name) {
            static::assertTrue($event instanceof UserEvent);
            static::assertSame(SchemaEvents::USER_UPDATE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        static::assertFalse(MockUpUserListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $updateUsername = 'New_name';
        $user->setUsername($updateUsername);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchUpdate($user);

        static::assertTrue(MockUpUserListener::$called);
        static::assertSame($updateUsername, MockUpUserListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_DELETE, function ($event, $name) {
            static::assertTrue($event instanceof UserEvent);
            static::assertSame(SchemaEvents::USER_DELETE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        static::assertFalse(MockUpUserListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchDelete($user);

        static::assertTrue(MockUpUserListener::$called);
        static::assertSame($name, MockUpUserListener::$name);
    }
}

class MockUpUserListener
{
    public static $called = false;
    public static $name = UserEventDispatcherServiceTest::EMPTY_NAME;
}
