<?php

namespace Pumukit\EncoderBundle\Tests\Executor;

use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
final class LocalExecutorTest extends WebTestCase
{
    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
    }

    public function testSimple()
    {
        $executor = new LocalExecutor();
        $out = $executor->execute('sleep 1 && echo a');
        static::assertSame("a\n\n", "{$out}");
    }
}
