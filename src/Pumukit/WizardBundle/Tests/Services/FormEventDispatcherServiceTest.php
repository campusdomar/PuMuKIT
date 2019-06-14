<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Event\WizardEvents;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
final class FormEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $formDispatcher;
    private $dm;
    private $dispatcher;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb.odm.document_manager')
        ;
        $this->dispatcher = new EventDispatcher();
        MockUpFormListener::$called = false;
        MockUpFormListener::$title = self::EMPTY_TITLE;
        $this->formDispatcher = new FormEventDispatcherService($this->dispatcher);
    }

    protected function tearDown()
    {
        $this->dispatcher = null;
        $this->formDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchSubmit()
    {
        $this->dispatcher->addListener(WizardEvents::FORM_SUBMIT, function ($event, $title) {
            static::assertTrue($event instanceof FormEvent);
            static::assertSame(WizardEvents::FORM_SUBMIT, $title);
            $form = $event->getForm();
            MockUpFormListener::$called = true;
            MockUpFormListener::$title = $form['title'];
            $user = $event->getUser();
            static::assertTrue($user instanceof User);
            $multimediaObject = $event->getMultimediaObject();
            static::assertTrue($multimediaObject instanceof MultimediaObject);
        });
        static::assertFalse(MockUpFormListener::$called);
        static::assertSame(self::EMPTY_TITLE, MockUpFormListener::$title);
        $title = 'test title';
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);
        $form = ['title' => $title];
        $user = new User();
        $this->formDispatcher->dispatchSubmit($user, $multimediaObject, $form);
        static::assertTrue(MockUpFormListener::$called);
        static::assertSame($title, MockUpFormListener::$title);
    }
}

class MockUpFormListener
{
    public static $called = false;
    public static $title = FormEventDispatcherServiceTest::EMPTY_TITLE;
}
