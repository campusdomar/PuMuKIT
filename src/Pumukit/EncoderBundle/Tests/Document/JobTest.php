<?php

namespace Pumukit\EncoderBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\EncoderBundle\Document\Job;

/**
 * @internal
 * @coversNothing
 */
final class JobTest extends TestCase
{
    public function testDefaults()
    {
        $job = new Job();

        static::assertSame(Job::STATUS_WAITING, $job->getStatus());
        static::assertSame(['en' => ''], $job->getI18nName());
        static::assertSame(0, $job->getDuration());
        static::assertSame('0', $job->getSize());
        static::assertSame('en', $job->getLocale());
    }

    public function testGetterAndSetter()
    {
        $job = new Job();

        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $language_id = 'es';
        $profile = 1;
        $cpu = 'local';
        $url = 'video/'.$mm_id.'/video1.avi';
        $status = Job::STATUS_WAITING;
        $priority = 1;
        $name = 'video1';
        $description = 'description1';
        $timeini = new \DateTime('now');
        $timestart = new \DateTime('now');
        $timeend = new \DateTime('now');
        $pid = 3;
        $path_ini = 'path/ini';
        $path_end = 'path/end';
        $ext_ini = 'ext/ini';
        $ext_end = 'ext/end';
        $duration = 40;
        $size = '12000';
        $email = 'test@mail.com';
        $initVars = ['ocurls' => ['presenter/master' => 'http://presentatermaster.com', 'presentation/master' => 'http://presentationmaster']];
        $locale = 'en';

        $job->setLocale('en');
        $job->setMmId($mm_id);
        $job->setLanguageId($language_id);
        $job->setProfile($profile);
        $job->setCpu($cpu);
        $job->setUrl($url);
        $job->setStatus($status);
        $job->setPriority($priority);
        $job->setName($name);
        $job->setDescription($description);
        $job->setTimeini($timeini);
        $job->setTimestart($timestart);
        $job->setTimeend($timeend);
        $job->setPid($pid);
        $job->setPathIni($path_ini);
        $job->setPathEnd($path_end);
        $job->setExtIni($ext_ini);
        $job->setExtEnd($ext_end);
        $job->setDuration($duration);
        $job->setSize($size);
        $job->setEmail($email);
        $job->setInitVars($initVars);

        static::assertSame($mm_id, $job->getMmId());
        static::assertSame($language_id, $job->getLanguageId());
        static::assertSame($profile, $job->getProfile());
        static::assertSame($cpu, $job->getCpu());
        static::assertSame($url, $job->getUrl());
        static::assertSame($status, $job->getStatus());
        static::assertSame($priority, $job->getPriority());
        static::assertSame($name, $job->getName());
        static::assertSame($description, $job->getDescription());
        static::assertSame($timeini, $job->getTimeini());
        static::assertSame($timestart, $job->getTimestart());
        static::assertSame($timeend, $job->getTimeend());
        static::assertSame($pid, $job->getPid());
        static::assertSame($path_ini, $job->getPathIni());
        static::assertSame($path_end, $job->getPathEnd());
        static::assertSame($ext_ini, $job->getExtIni());
        static::assertSame($ext_end, $job->getExtEnd());
        static::assertSame($duration, $job->getDuration());
        static::assertSame($size, $job->getSize());
        static::assertSame($email, $job->getEmail());
        static::assertSame($initVars, $job->getInitVars());
        static::assertSame($locale, $job->getLocale());

        $descriptionI18n = ['en' => 'description', 'es' => 'descripción'];
        $nameI18n = ['en' => 'name', 'es' => 'nombre'];

        $job->setI18nDescription($descriptionI18n);
        $job->setI18nName($nameI18n);

        static::assertSame($descriptionI18n, $job->getI18nDescription());
        static::assertSame($nameI18n, $job->getI18nName());

        static::assertSame('Waiting', $job->getStatusText());
    }
}
