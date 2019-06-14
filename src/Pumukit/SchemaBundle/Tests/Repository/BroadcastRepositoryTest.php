<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @deprecated in version 2.3
 *
 * @internal
 * @coversNothing
 */
final class BroadcastRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    protected function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository(Broadcast::class)
        ;

        $this->dm->getDocumentCollection(MultimediaObject::class)
            ->remove([])
        ;
        $this->dm->getDocumentCollection(Broadcast::class)
            ->remove([])
        ;
        $this->dm->flush();
    }

    protected function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        static::assertSame(0, \count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $broadcastPrivate = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private');
        static::assertSame(1, \count($this->repo->findAll()));

        $broadcastPublic = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public');
        static::assertSame(2, \count($this->repo->findAll()));
    }

    public function testFindDistinctIdsByBroadcastTypeId()
    {
        $private1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private1');
        $public1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public1');
        $public2 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB, 'public2');
        $private2 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PRI, 'private2');
        $corporative1 = $this->createBroadcast(Broadcast::BROADCAST_TYPE_COR, 'corporative1');

        $privates = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI)->toArray();

        static::assertTrue(\in_array($private1->getId(), $privates, true));
        static::assertTrue(\in_array($private2->getId(), $privates, true));
        static::assertFalse(\in_array($public1->getId(), $privates, true));
        static::assertFalse(\in_array($public2->getId(), $privates, true));
        static::assertFalse(\in_array($corporative1->getId(), $privates, true));

        $publics = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_PUB)->toArray();

        static::assertFalse(\in_array($private1->getId(), $publics, true));
        static::assertFalse(\in_array($private2->getId(), $publics, true));
        static::assertTrue(\in_array($public1->getId(), $publics, true));
        static::assertTrue(\in_array($public2->getId(), $publics, true));
        static::assertFalse(\in_array($corporative1->getId(), $publics, true));

        $corporatives = $this->repo->findDistinctIdsByBroadcastTypeId(Broadcast::BROADCAST_TYPE_COR)->toArray();

        static::assertFalse(\in_array($private1->getId(), $corporatives, true));
        static::assertFalse(\in_array($private2->getId(), $corporatives, true));
        static::assertFalse(\in_array($public1->getId(), $corporatives, true));
        static::assertFalse(\in_array($public2->getId(), $corporatives, true));
        static::assertTrue(\in_array($corporative1->getId(), $corporatives, true));
    }

    private function createBroadcast($broadcastTypeId, $name)
    {
        $locale = 'en';
        $passwd = 'password';
        $defaultSel = Broadcast::BROADCAST_TYPE_PRI === $broadcastTypeId;
        $description = ucfirst($broadcastTypeId).' broadcast';

        $broadcast = new Broadcast();
        $broadcast->setLocale($locale);
        $broadcast->setName($name);
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd($passwd);
        $broadcast->setDefaultSel($defaultSel);
        $broadcast->setDescription($description);

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }
}
