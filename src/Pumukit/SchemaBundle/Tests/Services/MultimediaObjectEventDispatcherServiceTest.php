<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class MultimediaObjectEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $multimediaObjectDispatcher;
    private $dispatcher;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()
            ->get('event_dispatcher')
        ;

        MockUpMultimediaObjectListener::$called = false;
        MockUpMultimediaObjectListener::$title = self::EMPTY_TITLE;

        $this->multimediaObjectDispatcher = new MultimediaObjectEventDispatcherService($this->dispatcher);
    }

    protected function tearDown()
    {
        $this->dispatcher = null;
        $this->multimediaObjectDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_CREATE, function ($event, $name) {
            static::assertTrue($event instanceof MultimediaObjectEvent);
            static::assertSame(SchemaEvents::MULTIMEDIAOBJECT_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        static::assertFalse(MockUpMultimediaObjectListener::$called);
        static::assertSame(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $this->multimediaObjectDispatcher->dispatchCreate($multimediaObject);

        static::assertTrue(MockUpMultimediaObjectListener::$called);
        static::assertSame($title, MockUpMultimediaObjectListener::$title);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, function ($event, $name) {
            static::assertTrue($event instanceof MultimediaObjectEvent);
            static::assertSame(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        static::assertFalse(MockUpMultimediaObjectListener::$called);
        static::assertSame(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $updateTitle = 'New title';
        $multimediaObject->setTitle($updateTitle);

        $this->multimediaObjectDispatcher->dispatchUpdate($multimediaObject);

        static::assertTrue(MockUpMultimediaObjectListener::$called);
        static::assertSame($updateTitle, MockUpMultimediaObjectListener::$title);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_DELETE, function ($event, $name) {
            static::assertTrue($event instanceof MultimediaObjectEvent);
            static::assertSame(SchemaEvents::MULTIMEDIAOBJECT_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        static::assertFalse(MockUpMultimediaObjectListener::$called);
        static::assertSame(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $this->multimediaObjectDispatcher->dispatchDelete($multimediaObject);

        static::assertTrue(MockUpMultimediaObjectListener::$called);
        static::assertSame($title, MockUpMultimediaObjectListener::$title);
    }
}

class MockUpMultimediaObjectListener
{
    public static $called = false;
    public static $title = MultimediaObjectEventDispatcherServiceTest::EMPTY_TITLE;
}
