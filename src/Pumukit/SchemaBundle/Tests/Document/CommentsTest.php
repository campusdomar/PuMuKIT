<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Comments;

/**
 * @internal
 * @coversNothing
 */
final class CommentsTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $date = new \DateTime('now');
        $text = 'description text';
        $multimedia_object_id = 1;

        $comment = new Comments();

        $comment->setDate($date);
        $comment->setText($text);
        $comment->setMultimediaObjectId($multimedia_object_id);

        static::assertSame($date, $comment->getDate());
        static::assertSame($text, $comment->getText());
        static::assertSame($multimedia_object_id, $comment->getMultimediaObjectId());
    }
}
