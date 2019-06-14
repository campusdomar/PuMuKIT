<?php

namespace Pumukit\OaiBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Pumukit\OaiBundle\Utils\ResumptionToken;

/**
 * @internal
 * @coversNothing
 */
final class ResumptionTokenTest extends TestCase
{
    public function testConstructAndGetter()
    {
        $token = new ResumptionToken();
        static::assertSame(0, $token->getOffset());
        static::assertNull($token->getFrom());
        static::assertNull($token->getUntil());
        static::assertNull($token->getMetadataPrefix());
        static::assertNull($token->getSet());

        $offset = 10;
        $from = new \DateTime('yesterday');
        $until = new \DateTime('tomorrow');
        $metadataPrefix = 'oai_dc';
        $set = 'castillo';
        $token = new ResumptionToken($offset, $from, $until, $metadataPrefix, $set);
        static::assertSame($offset, $token->getOffset());
        static::assertSame($from, $token->getFrom());
        static::assertSame($until, $token->getUntil());
        static::assertSame($metadataPrefix, $token->getMetadataPrefix());
        static::assertSame($set, $token->getSet());

        static::assertTrue(\strlen($token->encode()) > 0);
    }

    /**
     * @expectedException \Pumukit\OaiBundle\Utils\ResumptionTokenException
     */
    public function testInvalidDecode()
    {
        $rawToken = base64_encode('}}~~{{');
        $token = ResumptionToken::decode($rawToken);
    }

    public function testDecode()
    {
        $rawToken = 'eyJvZmZzZXQiOjEwLCJtZXRhZGF0YVByZWZpeCI6Im9haV9kYyIsInNldCI6ImNhc3RpbGxvIiwiZnJvbSI6MTQ3MDYwNzIwMCwidW50aWwiOjE0NzA3ODAwMDB9';

        $offset = 10;
        $metadataPrefix = 'oai_dc';
        $set = 'castillo';
        $token = ResumptionToken::decode($rawToken);

        static::assertSame($offset, $token->getOffset());
        static::assertSame($metadataPrefix, $token->getMetadataPrefix());
        static::assertSame($set, $token->getSet());
    }
}
