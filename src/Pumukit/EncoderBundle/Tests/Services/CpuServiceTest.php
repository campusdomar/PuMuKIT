<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\CpuService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class CpuServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $cpuService;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository(Job::class);

        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->flush();

        $this->cpuService = new CpuService($this->getDemoCpus(), $this->dm);
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->cpuService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetFreeCpu()
    {
        $cpus = $this->getDemoCpus();

        static::assertSame('CPU_REMOTE', $this->cpuService->getFreeCpu('video_h264'));

        $job = new Job();
        $job->setCpu('CPU_REMOTE');
        $job->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job);
        $this->dm->flush();

        static::assertSame('CPU_LOCAL', $this->cpuService->getFreeCpu('video_h264'));

        $job2 = new Job();
        $job2->setCpu('CPU_LOCAL');
        $job2->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job2);
        $this->dm->flush();

        static::assertSame('CPU_CLOUD', $this->cpuService->getFreeCpu('video_h264'));

        $job3 = new Job();
        $job3->setCpu('CPU_CLOUD');
        $job3->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job3);
        $this->dm->flush();

        static::assertSame('CPU_REMOTE', $this->cpuService->getFreeCpu('video_h264'));

        $job4 = new Job();
        $job4->setCpu('CPU_REMOTE');
        $job4->setStatus(Job::STATUS_EXECUTING);
        $this->dm->persist($job4);
        $this->dm->flush();

        static::assertNull($this->cpuService->getFreeCpu('video_h264'));
        static::assertSame('CPU_WEBM', $this->cpuService->getFreeCpu('master_webm'));
        static::assertSame('CPU_WEBM', $this->cpuService->getFreeCpu('video_webm'));
        static::assertSame('CPU_WEBM', $this->cpuService->getFreeCpu());
    }

    public function testGetCpus()
    {
        $cpus = $this->getDemoCpus();

        static::assertSame(4, \count($this->cpuService->getCpus()));
        static::assertSame(\count($cpus), \count($this->cpuService->getCpus()));
    }

    public function testGetCpuByName()
    {
        $cpus = $this->getDemoCpus();

        static::assertSame($cpus['CPU_LOCAL'], $this->cpuService->getCpuByName('CPU_LOCAL'));
        static::assertSame($cpus['CPU_REMOTE'], $this->cpuService->getCpuByName('CPU_REMOTE'));
        static::assertSame($cpus['CPU_CLOUD'], $this->cpuService->getCpuByName('CPU_CLOUD'));
        static::assertNull($this->cpuService->getCpuByName('CPU_local')); //Case sensitive
        static::assertNull($this->cpuService->getCpuByName('CPU_LO'));
    }

    private function getDemoCpus()
    {
        return [
            'CPU_WEBM' => [
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco4',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
                'profiles' => ['master_webm', 'video_webm'],
            ],
            'CPU_LOCAL' => [
                'host' => '127.0.0.1',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco1',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_REMOTE' => [
                'host' => '192.168.5.123',
                'max' => 2,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
            'CPU_CLOUD' => [
                'host' => '192.168.5.124',
                'max' => 1,
                'number' => 1,
                'type' => CpuService::TYPE_LINUX,
                'user' => 'transco2',
                'password' => 'PUMUKIT',
                'description' => 'Pumukit transcoder',
            ],
        ];
    }
}
