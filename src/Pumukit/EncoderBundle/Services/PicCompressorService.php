<?php

namespace Pumukit\EncoderBundle\Services;

use Symfony\Component\Process\Process;

class PicCompressorService
{
    private $jpgCommand;
    private $pngCommand;
    private $limitSize = 100;
    private $compressSize = 100;

    public function __construct($jpgCommand = null, $pngCommand = null, $limitSize = 100, $compressSize = 100)
    {
        $this->jpgCommand = $jpgCommand ?: 'jpegoptim -S "{{size}}" "{{input}}"';
        $this->pngCommand = $pngCommand ?: 'optipng "{{input}}"';
        $this->limitSize = $limitSize;
        $this->compressSize = $compressSize;
    }

    public function compressPic($path)
    {
        if (file_exists($path)) {
            if (filesize($path) / 1024 > $this->limitSize) {
                $extension = strtolower(pathinfo($path)['extension']);
                if ($extension === 'jpg' || $extension === 'jpeg') {
                    return $this->compressJPGImage($path);
                } elseif ($extension == 'png') {
                    return $this->compressPNGImage($path);
                }
            }
        }

        return 0;
    }

    public function compressJPGImage($path)
    {
        $vars = array(
            '{{size}}' => $this->compressSize,
            '{{input}}' => $path,
        );

        return $this->executeCompression($this->jpgCommand, $vars);
    }

    public function compressPNGImage($path)
    {
        $vars = array(
            '{{input}}' => $path,
        );

        $this->executeCompression($this->pngCommand, $vars);
    }

    protected function executeCompression($command, $vars)
    {
        $commandLine = str_replace(array_keys($vars), array_values($vars), $command);
        $process = new Process($commandLine);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
