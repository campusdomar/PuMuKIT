<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Event\GroupEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\GroupEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
final class GroupEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $dm;
    private $dispatcher;
    private $groupDispatcher;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb.odm.document_manager')
        ;
        $this->dispatcher = new EventDispatcher();

        $this->dm->getDocumentCollection(Group::class)->remove([]);
        $this->dm->flush();

        MockUpGroupListener::$called = false;
        MockUpGroupListener::$name = self::EMPTY_NAME;

        $this->groupDispatcher = new GroupEventDispatcherService($this->dispatcher);
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->dispatcher = null;
        $this->groupDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_CREATE, function ($event, $name) {
            static::assertTrue($event instanceof GroupEvent);
            static::assertSame(SchemaEvents::GROUP_CREATE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        static::assertFalse(MockUpGroupListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchCreate($group);

        static::assertTrue(MockUpGroupListener::$called);
        static::assertSame($name, MockUpGroupListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_UPDATE, function ($event, $name) {
            static::assertTrue($event instanceof GroupEvent);
            static::assertSame(SchemaEvents::GROUP_UPDATE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        static::assertFalse(MockUpGroupListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $updateName = 'New_name';
        $group->setName($updateName);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchUpdate($group);

        static::assertTrue(MockUpGroupListener::$called);
        static::assertSame($updateName, MockUpGroupListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_DELETE, function ($event, $name) {
            static::assertTrue($event instanceof GroupEvent);
            static::assertSame(SchemaEvents::GROUP_DELETE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        static::assertFalse(MockUpGroupListener::$called);
        static::assertSame(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchDelete($group);

        static::assertTrue(MockUpGroupListener::$called);
        static::assertSame($name, MockUpGroupListener::$name);
    }
}

class MockUpGroupListener
{
    public static $called = false;
    public static $name = GroupEventDispatcherServiceTest::EMPTY_NAME;
}
