<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class MultimediaObjectPropertyJobServiceTest extends WebTestCase
{
    private $dm;
    private $service;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->service = static::$kernel->getContainer()->get('pumukitencoder.mmpropertyjob');

        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testService()
    {
        $mm = new MultimediaObject();
        $mm->setProperty('test', 'test');
        $job = new Job();
        $otherJob = new Job();

        $this->dm->persist($mm);
        $this->dm->persist($job);
        $this->dm->persist($otherJob);
        $this->dm->flush();
        $mmId = $mm->getId();

        static::assertNull($mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertNull($mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertSame([$job->getId()], $mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertNull($mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->addJob($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertSame([$job->getId(), $otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertNull($mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->executeJob($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertSame([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertSame([$job->getId()], $mm->getProperty('executing_jobs'));
        static::assertNull($mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $job);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertSame([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertSame([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->finishJob($mm, $otherJob); //Invalid step. No properties change.

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertSame([$otherJob->getId()], $mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertSame([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->executeJob($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertNull($mm->getProperty('pending_jobs'));
        static::assertSame([$otherJob->getId()], $mm->getProperty('executing_jobs'));
        static::assertSame([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertNull($mm->getProperty('error_jobs'));

        $this->service->errorJob($mm, $otherJob);

        $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
        $mm = $this->dm->getRepository(MultimediaObject::class)->find($mmId);

        static::assertNull($mm->getProperty('pending_jobs'));
        static::assertNull($mm->getProperty('executing_jobs'));
        static::assertSame([$job->getId()], $mm->getProperty('finished_jobs'));
        static::assertSame([$otherJob->getId()], $mm->getProperty('error_jobs'));
    }
}
