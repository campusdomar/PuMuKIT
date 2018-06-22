<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Services\PicCompressorService;
use Pumukit\InspectionBundle\Utils\TestCommand;

class PicCompressorServiceTest extends WebTestCase
{
    private $picCompressor;
    private $resourcesDir;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $this->resourcesDir = realpath(__DIR__.'/../Resources');
        $jpgCommand = 'jpegoptim -S "{{size}}" "{{input}}"';
        $pngCommand = 'optipng "{{input}}"';
        $limitSize = 10;
        $compressSize = 10;
        $this->picCompressor = new PicCompressorService($jpgCommand, $pngCommand, $limitSize, $compressSize);
    }

    public function tearDown()
    {
        $this->resourcesDir = null;
        $this->picCompressor = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testCompressPicJPG()
    {
        if (false == TestCommand::commandExists('jpegoptim')) {
            $this->markTestSkipped('PicCompressor test marks as skipped (No jpegoptim).');
        }
        $jpgPicToCompress = $this->resourcesDir.'/pictocompress.jpg';
        $jpgPicToCompressOriginal = $this->resourcesDir.'/pictocompress.jpg.original';
        $originalFileSize = filesize($jpgPicToCompress);
        $copied = copy($jpgPicToCompress, $jpgPicToCompressOriginal);
        $output = $this->picCompressor->compressPic($jpgPicToCompress);
        clearstatcache();
        $this->assertGreaterThan(filesize($jpgPicToCompress), $originalFileSize);
        $moved = rename($jpgPicToCompressOriginal, $jpgPicToCompress);
    }

    public function testCompressPicPNG()
    {
        if (false == TestCommand::commandExists('optipng')) {
            $this->markTestSkipped('PicCompressor test marks as skipped (No optipng).');
        }
        $pngPicToCompress = $this->resourcesDir.'/pictocompress.png';
        $pngPicToCompressOriginal = $this->resourcesDir.'/pictocompress.png.original';
        $originalFileSize = filesize($pngPicToCompress);
        $copied = copy($pngPicToCompress, $pngPicToCompressOriginal);
        $output = $this->picCompressor->compressPic($pngPicToCompress);
        clearstatcache();
        $this->assertGreaterThan(filesize($pngPicToCompress), $originalFileSize);
        $moved = rename($pngPicToCompressOriginal, $pngPicToCompress);
    }
}
