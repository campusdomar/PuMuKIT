<?php

namespace Pumukit\EncoderBundle\Tests\Executor;

use Pumukit\EncoderBundle\Executor\RemoteHTTPExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class RemoteHTTPExecutorTest extends WebTestCase
{
    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
    }

    public function testSimple()
    {
        static::markTestSkipped('Remote cpu not available in test.');

        $cpu = [
            'host' => '127.0.0.1:9000',
            'user' => 'pumukit',
            'password' => 'PUMUKIT',
        ];

        $executor = new RemoteHTTPExecutor();
        $out = $executor->execute('sleep 1 && echo a', $cpu);
        static::assertSame("a\n", "{$out}");
    }
}
