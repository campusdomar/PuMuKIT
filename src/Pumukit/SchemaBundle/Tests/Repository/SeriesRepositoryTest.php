<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Group;

class SeriesRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $personService;
    private $factoryService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->personService = static::$kernel->getContainer()->get('pumukitschema.person');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')
            ->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repo = null;
        $this->personService = null;
        $this->factoryService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testRepositoryEmpty()
    {
        $this->assertEquals(0, count($this->repo->findAll()));
    }

    public function testRepository()
    {
        $series = new Series();

        $title = 'Series title';
        $series->setTitle($title);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findAll()));
        $this->assertEquals($series, $this->repo->find($series->getId()));

        $pic1 = new Pic();
        $pic1->setUrl('http://domain.com/pic1.png');

        $pic2 = new Pic();
        $pic2->setUrl('http://domain.com/pic2.png');

        $pic3 = new Pic();
        $pic3->setUrl('http://domain.com/pic3.png');

        $series->addPic($pic1);
        $series->addPic($pic2);
        $series->addPic($pic3);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals($pic1, $series->getPic());
        $this->assertEquals($pic2, $series->getPicById($pic2->getId()));
        $this->assertEquals(null, $series->getPicById(null));
    }

    public function testFindSeriesWithTags()
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);
        $mm34 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm12->addTag($tag1);
        $mm12->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);
        $mm22->addTag($tag2);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm32->addTag($tag2);
        $mm32->addTag($tag3);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($mm34);
        $this->dm->flush();

        // SORT
        $sort = array();
        $sortAsc = array('title' => 1);
        $sortDesc = array('title' => -1);

        // FIND SERIES WITH TAG
        $this->assertEquals(3, count($this->repo->findWithTag($tag1)));
        $limit = 2;
        $this->assertEquals(2, $this->repo->findWithTag($tag1, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(2, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sort, $limit, $page)->count(true));

        $this->assertEquals(1, $this->repo->findWithTag($tag3)->count(true));

        // FIND SERIES WITH TAG (SORT)
        $arrayAsc = array($series1, $series2, $series3);
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc)->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 2;
        $page = 1;
        $arrayAsc = array($series3);
        $arrayAscResult = array_values($this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->toArray());
        $this->assertEquals(1, $this->repo->findWithTag($tag1, $sortAsc, $limit, $page)->count(true));
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = array($series3, $series2, $series1);
        $arrayDescResult = array_values($this->repo->findWithTag($tag1, $sortDesc)->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 2;
        $page = 1;
        $arrayDesc = array($series1);
        $arrayDescResult = array_values($this->repo->findWithTag($tag1, $sortDesc, $limit, $page)->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITH TAG
        $this->assertEquals(1, count($this->repo->findOneWithTag($tag2)));
        $this->assertEquals(1, count($this->repo->findOneWithTag($tag3)));
        $this->assertEquals($series3, $this->repo->findOneWithTag($tag3));

        // FIND SERIES WITH ANY TAG
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(3, $this->repo->findWithAnyTag($arrayTags)->count(true));
        $limit = 2;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(2, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag3);
        $this->assertEquals(1, $this->repo->findWithAnyTag($arrayTags)->count(true));

        // FIND SERIES WITH ANY TAG (SORT)
        $arrayTags = array($tag1, $tag2);
        $arrayAsc = array($series1, $series2, $series3);
        $query = $this->repo->findWithAnyTag($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 2;
        $arrayAsc = array($series1, $series2);
        $query = $this->repo->findWithAnyTag($arrayTags, $sortAsc, $limit);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = array($series3, $series2, $series1);
        $query = $this->repo->findWithAnyTag($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 2;
        $arrayDesc = array($series3, $series2);
        $query = $this->repo->findWithAnyTag($arrayTags, $sortDesc, $limit);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND SERIES WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(2, $this->repo->findWithAllTags($arrayTags)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags, $sort, $limit, $page)->count(true));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, $this->repo->findWithAllTags($arrayTags)->count(true));

        // FIND SERIES WITH ALL TAGS (SORT)
        $arrayTags = array($tag1, $tag2);
        $arrayAsc = array($series1, $series2);
        $query = $this->repo->findWithAllTags($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayAsc = array($series2);
        $query = $this->repo->findWithAllTags($arrayTags, $sortAsc, $limit, $page);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = array($series2, $series1);
        $query = $this->repo->findWithAllTags($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayDesc = array($series1);
        $query = $this->repo->findWithAllTags($arrayTags, $sortDesc, $limit, $page);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITH ALL TAGS
        $arrayTags = array($tag1, $tag2);
        $this->assertEquals(1, count($this->repo->findOneWithAllTags($arrayTags)));

        $arrayTags = array($tag2, $tag3);
        $this->assertEquals(1, count($this->repo->findOneWithAllTags($arrayTags)));
        $this->assertEquals($series3, $this->repo->findOneWithAllTags($arrayTags));

        // FIND SERIES WITHOUT TAG
        $this->assertEquals(2, $this->repo->findWithoutTag($tag3)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit)->count(true));
        $page = 0;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutTag($tag3, $sort, $limit, $page)->count(true));

        // FIND SERIES WITHOUT TAG (SORT)
        $arrayAsc = array($series1, $series2);
        $query = $this->repo->findWithoutTag($tag3, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayAsc = array($series2);
        $query = $this->repo->findWithoutTag($tag3, $sortAsc, $limit, $page);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = array($series2, $series1);
        $query = $this->repo->findWithoutTag($tag3, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $page = 1;
        $arrayDesc = array($series1);
        $query = $this->repo->findWithoutTag($tag3, $sortDesc, $limit, $page);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }

        // FIND ONE SERIES WITHOUT TAG
        $this->assertEquals(1, count($this->repo->findOneWithoutTag($tag3)));

        // FIND SERIES WITHOUT ALL TAGS
        $mm11->addTag($tag3);
        $mm12->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->flush();

        $arrayTags = array($tag1, $tag2, $tag3);
        $this->assertEquals(2, $this->repo->findWithoutAllTags($arrayTags)->count(true));
        $limit = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit)->count(true));
        $page = 1;
        $this->assertEquals(1, $this->repo->findWithoutAllTags($arrayTags, $sort, $limit, $page)->count(true));

        // FIND SERIES WITHOUT ALL TAGS (SORT)
        $arrayAsc = array($series2, $series3);
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortAsc);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }
        $limit = 1;
        $arrayAsc = array($series2);
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortAsc, $limit);
        $arrayAscResult = array_values($query->toArray());
        foreach ($arrayAsc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayAscResult[$i]->getId());
        }

        $arrayDesc = array($series3, $series2);
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortDesc);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
        $limit = 1;
        $arrayDesc = array($series3);
        $query = $this->repo->findWithoutAllTags($arrayTags, $sortDesc, $limit);
        $arrayDescResult = array_values($query->toArray());
        foreach ($arrayDesc as $i => $series) {
            $this->assertEquals($series->getId(), $arrayDescResult[$i]->getId());
        }
    }

    public function testCreateBuilderWithTag()
    {
        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm33 = $this->factoryService->createMultimediaObject($series3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm22);
        $this->dm->persist($mm33);
        $this->dm->flush();

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm22->addTag($tag2);
        $mm22->addTag($tag3);

        $mm33->addTag($tag1);
        $mm33->addTag($tag3);

        $this->dm->persist($mm11);
        $this->dm->persist($mm22);
        $this->dm->persist($mm33);
        $this->dm->flush();

        // SORT
        $sort = array();
        $sortAsc = array('title' => 1);
        $sortDesc = array('title' => -1);

        $this->assertEquals(1, count($this->repo->createBuilderWithTag($tag1)));
        $this->assertEquals(1, count($this->repo->createBuilderWithTag($tag1, $sort)));
        $this->assertEquals(1, count($this->repo->createBuilderWithTag($tag2, $sortAsc)));
        $this->assertEquals(1, count($this->repo->createBuilderWithTag($tag3, $sortDesc)));
    }

    public function testFindByPicId()
    {
        $series1 = $this->factoryService->createSeries();
        $title1 = 'Series 1';
        $series1->setTitle($title1);

        $pic = new Pic();
        $this->dm->persist($pic);

        $series1->addPic($pic);

        $this->dm->persist($series1);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findByPicId($pic->getId())));
    }

    public function testFindSeriesByPersonId()
    {
        $series1 = $this->factoryService->createSeries();
        $title1 = 'Series 1';
        $series1->setTitle($title1);

        $series2 = $this->factoryService->createSeries();
        $title2 = 'Series 2';
        $series2->setTitle($title2);

        $series3 = $this->factoryService->createSeries();
        $title3 = 'Series 3';
        $series3->setTitle($title3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);

        $personJohn = new Person();
        $nameJohn = 'John Smith';
        $personJohn->setName($nameJohn);

        $personBob = new Person();
        $nameBob = 'Bob Clark';
        $personBob->setName($nameBob);

        $personKate = new Person();
        $nameKate = 'Kate Simmons';
        $personKate->setName($nameKate);

        $this->dm->persist($personJohn);
        $this->dm->persist($personBob);
        $this->dm->persist($personKate);

        $roleActor = new Role();
        $codActor = 'actor';
        $roleActor->setCod($codActor);

        $rolePresenter = new Role();
        $codPresenter = 'presenter';
        $rolePresenter->setCod($codPresenter);

        $this->dm->persist($roleActor);
        $this->dm->persist($rolePresenter);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $title11 = 'Multimedia Object 11';
        $mm11->setTitle($title11);
        $mm11->addPersonWithRole($personJohn, $roleActor);
        $mm11->addPersonWithRole($personBob, $roleActor);
        $mm11->addPersonWithRole($personJohn, $rolePresenter);

        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $title12 = 'Multimedia Object 12';
        $mm12->setTitle($title12);
        $mm12->addPersonWithRole($personBob, $roleActor);
        $mm12->addPersonWithRole($personBob, $rolePresenter);

        $mm13 = $this->factoryService->createMultimediaObject($series1);
        $title13 = 'Multimedia Object 13';
        $mm13->setTitle($title13);
        $mm13->addPersonWithRole($personKate, $roleActor);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $title21 = 'Multimedia Object 21';
        $mm21->setTitle($title21);
        $mm21->addPersonWithRole($personKate, $rolePresenter);
        $mm21->addPersonWithRole($personKate, $roleActor);

        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $title31 = 'Multimedia Object 31';
        $mm31->setTitle($title31);
        $mm31->addPersonWithRole($personJohn, $rolePresenter);

        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $title32 = 'Multimedia Object 3212312';
        $mm32->setTitle($title32);
        $mm32->addPersonWithRole($personJohn, $roleActor);
        $mm32->addPersonWithRole($personBob, $roleActor);
        $mm32->addPersonWithRole($personJohn, $rolePresenter);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $seriesKate = $this->repo->findSeriesByPersonId($personKate->getId());
        $this->assertEquals(2, count($seriesKate));
        $this->assertEquals(array($series1, $series2), array_values($seriesKate->toArray()));

        $seriesJohn = $this->repo->findSeriesByPersonId($personJohn->getId());
        $this->assertEquals(2, count($seriesJohn));
        $this->assertEquals(array($series1, $series3), array_values($seriesJohn->toArray()));

        $seriesBob = $this->repo->findSeriesByPersonId($personBob->getId());
        $this->assertEquals(2, count($seriesBob));
        $this->assertEquals(array($series1, $series3), array_values($seriesBob->toArray()));

        $seriesJohnActor = $this->repo->findByPersonIdAndRoleCod($personJohn->getId(), $roleActor->getCod());
        $seriesJohnPresenter = $this->repo->findByPersonIdAndRoleCod($personJohn->getId(), $rolePresenter->getCod());
        $seriesBobActor = $this->repo->findByPersonIdAndRoleCod($personBob->getId(), $roleActor->getCod());
        $seriesBobPresenter = $this->repo->findByPersonIdAndRoleCod($personBob->getId(), $rolePresenter->getCod());
        $seriesKateActor = $this->repo->findByPersonIdAndRoleCod($personKate->getId(), $roleActor->getCod());
        $seriesKatePresenter = $this->repo->findByPersonIdAndRoleCod($personKate->getId(), $rolePresenter->getCod());

        $this->assertEquals(2, count($seriesJohnActor));
        $this->assertTrue(in_array($series1, $seriesJohnActor->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnActor->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor->toArray()));

        $this->assertEquals(2, count($seriesJohnPresenter));
        $this->assertTrue(in_array($series1, $seriesJohnPresenter->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnPresenter->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnPresenter->toArray()));

        $this->assertEquals(2, count($seriesBobActor));
        $this->assertTrue(in_array($series1, $seriesBobActor->toArray()));
        $this->assertFalse(in_array($series2, $seriesBobActor->toArray()));
        $this->assertTrue(in_array($series3, $seriesBobActor->toArray()));

        $this->assertEquals(1, count($seriesBobPresenter));
        $this->assertTrue(in_array($series1, $seriesBobPresenter->toArray()));
        $this->assertFalse(in_array($series2, $seriesBobPresenter->toArray()));
        $this->assertFalse(in_array($series3, $seriesBobPresenter->toArray()));

        $this->assertEquals(2, count($seriesKateActor));
        $this->assertTrue(in_array($series1, $seriesKateActor->toArray()));
        $this->assertTrue(in_array($series2, $seriesKateActor->toArray()));
        $this->assertFalse(in_array($series3, $seriesKateActor->toArray()));

        $this->assertEquals(1, count($seriesKatePresenter));
        $this->assertFalse(in_array($series1, $seriesKatePresenter->toArray()));
        $this->assertTrue(in_array($series2, $seriesKatePresenter->toArray()));
        $this->assertFalse(in_array($series3, $seriesKatePresenter->toArray()));

        $group1 = new Group();
        $group1->setKey('group1');
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setKey('group2');
        $group2->setName('Group 2');
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $mm21->addGroup($group1);
        $this->dm->persist($mm21);
        $this->dm->flush();

        $groups = array($group1->getId());
        $seriesJohnActor1 = $this->repo->findByPersonIdAndRoleCodOrGroups($personJohn->getId(), $roleActor->getCod(), $groups);
        $groups = array($group2->getId());
        $seriesJohnActor2 = $this->repo->findByPersonIdAndRoleCodOrGroups($personJohn->getId(), $roleActor->getCod(), $groups);

        $this->assertEquals(3, count($seriesJohnActor1));
        $this->assertTrue(in_array($series1, $seriesJohnActor1->toArray()));
        $this->assertTrue(in_array($series2, $seriesJohnActor1->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor1->toArray()));

        $this->assertEquals(2, count($seriesJohnActor2));
        $this->assertTrue(in_array($series1, $seriesJohnActor2->toArray()));
        $this->assertFalse(in_array($series2, $seriesJohnActor2->toArray()));
        $this->assertTrue(in_array($series3, $seriesJohnActor2->toArray()));
    }

    public function testFindBySeriesType()
    {
        $seriesType1 = $this->createSeriesType('Series Type 1');
        $seriesType2 = $this->createSeriesType('Series Type 2');
        $seriesType3 = $this->createSeriesType('Series Type 3');

        $series1 = $this->factoryService->createSeries();
        $series2 = $this->factoryService->createSeries();
        $series3 = $this->factoryService->createSeries();
        $series4 = $this->factoryService->createSeries();
        $series5 = $this->factoryService->createSeries();

        $series1->setSeriesType($seriesType1);
        $series2->setSeriesType($seriesType1);
        $series3->setSeriesType($seriesType2);
        $series4->setSeriesType($seriesType3);
        $series5->setSeriesType($seriesType3);

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->persist($series4);
        $this->dm->persist($series5);
        $this->dm->persist($seriesType1);
        $this->dm->persist($seriesType2);
        $this->dm->persist($seriesType3);

        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->findBySeriesType($seriesType1)));
        $this->assertEquals(1, count($this->repo->findBySeriesType($seriesType2)));
        $this->assertEquals(2, count($this->repo->findBySeriesType($seriesType3)));

        /*
        $this->assertEquals(2, count($seriesType1->getSeries()));
        $this->assertEquals(1, count($seriesType2->getSeries()));
        $this->assertEquals(2, count($seriesType3->getSeries()));
        $this->assertEquals(array($series1, $series2), $seriesType1->getSeries());
        $this->assertEquals(array($series3), $seriesType2->getSeries());
        $this->assertEquals(array($series4, $series5), $seriesType3->getSeries());
        */
    }

    public function testSimpleMultimediaObjectsInSeries()
    {
        $series1 = $this->createSeries('Series 1');

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $this->assertEquals(3, count($series1->getMultimediaObjects()));
    }

    public function testMultimediaObjectsInSeries()
    {
        $this->markTestSkipped('S');

        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');

        // NOTE: After creation we must take the initialized document
        $series1 = $this->repo->find($series1->getId());
        $series2 = $this->repo->find($series2->getId());

        $this->assertEquals(0, count($series1->getMultimediaObjects()));
        $this->assertEquals(0, count($series2->getMultimediaObjects()));

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        // TODO: Clear doctrine mongo cache

        $this->assertEquals(3, count($series1->getMultimediaObjects()));
        $this->assertEquals(2, count($series2->getMultimediaObjects()));

        $this->dm->remove($mm11);
        $this->dm->flush();

        // TODO: Clear doctrine mongo cache

        $this->assertEquals(2, count($series1->getMultimediaObjects()));
        $this->assertEquals(2, count($series2->getMultimediaObjects()));

        $this->assertTrue($series1->containsMultimediaObject($mm12));
        $this->assertFalse($series1->containsMultimediaObject($mm11));
    }

    public function testRankInAddMultimediaObject()
    {
        $series1 = $this->createSeries('Series 1');
        $this->assertEquals(0, count($series1->getMultimediaObjects()));

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);
        $mm14 = $this->factoryService->createMultimediaObject($series1);
        $mm15 = $this->factoryService->createMultimediaObject($series1);

        $this->assertEquals(1, $mm11->getRank());
        $this->assertEquals(2, $mm12->getRank());
        $this->assertEquals(3, $mm13->getRank());
        $this->assertEquals(4, $mm14->getRank());
        $this->assertEquals(5, $mm15->getRank());

        $series2 = $this->createSeries('Series 2');
        $this->assertEquals(0, count($series2->getMultimediaObjects()));

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);
        $mm24 = $this->factoryService->createMultimediaObject($series2);
        $mm25 = $this->factoryService->createMultimediaObject($series2);

        $this->assertEquals(1, $mm21->getRank());
        $this->assertEquals(2, $mm22->getRank());
        $this->assertEquals(3, $mm23->getRank());
        $this->assertEquals(4, $mm24->getRank());
        $this->assertEquals(5, $mm25->getRank());
    }

    public function testMultimediaObjectsWithTags()
    {
        $series = $this->createSeries('Series 1');
        //$this->assertEquals(0, count($series->getMultimediaObjects()));
        // TODO clear doctrine mongo cache after this call

        $mm1 = $this->factoryService->createMultimediaObject($series);
        $mm2 = $this->factoryService->createMultimediaObject($series);
        $mm3 = $this->factoryService->createMultimediaObject($series);
        $mm4 = $this->factoryService->createMultimediaObject($series);
        $mm5 = $this->factoryService->createMultimediaObject($series);

        $tag0 = new Tag();
        $tag1 = new Tag();
        $tag2 = new Tag();
        $tag3 = new Tag();
        $tag4 = new Tag();
        $tag5 = new Tag();
        $tag6 = new Tag();
        $tag7 = new Tag();
        $tag8 = new Tag();

        $tag0->setCod('tag0');
        $tag1->setCod('tag1');
        $tag2->setCod('tag2');
        $tag3->setCod('tag3');
        $tag4->setCod('tag4');
        $tag5->setCod('tag5');
        $tag6->setCod('tag6');
        $tag7->setCod('tag7');
        $tag8->setCod('tag8');

        $this->dm->persist($tag0);
        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->persist($tag4);
        $this->dm->persist($tag5);
        $this->dm->persist($tag6);
        $this->dm->persist($tag7);
        $this->dm->persist($tag8);
        $this->dm->flush();

        $mm1->addTag($tag1);

        $mm2->addTag($tag2);
        $mm2->addTag($tag1);
        $mm2->addTag($tag3);

        $mm3->addTag($tag1);
        $mm3->addTag($tag2);

        $mm4->addTag($tag4);
        $mm4->addTag($tag5);
        $mm4->addTag($tag6);

        $mm5->addTag($tag4);
        $mm5->addTag($tag7);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->persist($mm5);
        $this->dm->flush();

        $this->assertTrue($series->containsMultimediaObjectWithTag($tag4));
        $this->assertFalse($series->containsMultimediaObjectWithTag($tag8));

        $this->assertEquals(array($mm1, $mm2, $mm3), $series->getMultimediaObjectsWithTag($tag1));
        $this->assertEquals($mm1, $series->getMultimediaObjectWithTag($tag1));
        $this->assertNull($series->getMultimediaObjectWithTag($tag8));
        $this->assertEquals($mm1, $series->getMultimediaObjectWithAnyTag(array($tag1, $tag8)));
        $this->assertEquals(array($mm2), $series->getMultimediaObjectsWithAllTags(array($tag1, $tag2, $tag3)));
        $this->assertEquals($mm2, $series->getMultimediaObjectWithAllTags(array($tag2, $tag1)));
        $this->assertNull($series->getMultimediaObjectWithAllTags(array($tag2, $tag1, $tag8)));
        $this->assertEquals(4, count($series->getMultimediaObjectsWithAnyTag(array($tag1, $tag7))));
        $this->assertEquals(array($mm1, $mm2, $mm3, $mm5), $series->getMultimediaObjectsWithAnyTag(array($tag1, $tag7)));
        $this->assertEquals(1, count($series->getMultimediaObjectWithAnyTag(array($tag1))));
        $this->assertNull($series->getMultimediaObjectWithAnyTag(array($tag8)));
        $this->assertEquals(5, count($series->getFilteredMultimediaObjectsWithTags()));
        $this->assertEquals(3, count($series->getFilteredMultimediaObjectsWithTags(array($tag1))));
        $this->assertEquals(1, count($series->getFilteredMultimediaObjectsWithTags(array($tag1), array($tag2, $tag3))));
        $this->assertEquals(0, count($series->getFilteredMultimediaObjectsWithTags(array(), array($tag2, $tag3), array($tag1))));
        $this->assertEquals(3, count($series->getFilteredMultimediaObjectsWithTags(array(), array(), array($tag4))));
        $this->assertEquals(0, count($series->getFilteredMultimediaObjectsWithTags(array(), array(), array($tag4, $tag1))));
        $this->assertEquals(5, count($series->getFilteredMultimediaObjectsWithTags(array(), array(), array(), array($tag4, $tag1))));
        $this->assertEquals(1, count($series->getFilteredMultimediaObjectsWithTags(array($tag2, $tag3), array(), array(), array($tag3))));
    }

    public function testPicsInSeries()
    {
        $series = $this->createSeries('Series');

        $pic1 = new Pic();
        $pic1->setUrl('http://domain.com/pic1.png');

        $pic2 = new Pic();
        $pic2->setUrl('http://domain.com/pic2.png');

        $pic3 = new Pic();
        $pic3->setUrl('http://domain.com/pic3.png');

        $series->addPic($pic1);
        $series->addPic($pic2);
        $series->addPic($pic3);

        $this->dm->persist($series);
        $this->dm->flush();

        $this->assertEquals(3, count($this->repo->find($series->getId())->getPics()));
        $this->assertEquals($pic2, $this->repo->find($series->getId())->getPicById($pic2->getId()));

        $arrayPics = array($pic1, $pic2, $pic3);
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $series->upPicById($pic2->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $arrayPics = array($pic2, $pic1, $pic3);
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $series->downPicById($pic1->getId());

        $this->dm->persist($series);
        $this->dm->flush();

        $arrayPics = array($pic2, $pic3, $pic1);
        $this->assertEquals($arrayPics, $this->repo->find($series->getId())->getPics()->toArray());

        $this->assertTrue($series->containsPic($pic3));

        $series->removePicById($pic3->getId());

        $this->assertFalse($series->containsPic($pic3));
    }

    public function testFindWithTagAndSeriesType()
    {
        $seriesType1 = new SeriesType();
        $seriesType1->setName('Series Type 1');
        $seriesType2 = new SeriesType();
        $seriesType2->setName('Series Type 2');

        $this->dm->persist($seriesType1);
        $this->dm->persist($seriesType2);

        $tag1 = new Tag();
        $tag1->setCod('tag1');
        $tag2 = new Tag();
        $tag2->setCod('tag2');
        $tag3 = new Tag();
        $tag3->setCod('tag3');

        $this->dm->persist($tag1);
        $this->dm->persist($tag2);
        $this->dm->persist($tag3);
        $this->dm->flush();

        $series1 = $this->createSeries('Series 1');
        $series1->setSeriesType($seriesType1);
        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);
        $mm13 = $this->factoryService->createMultimediaObject($series1);

        $series2 = $this->createSeries('Series 2');
        $series2->setSeriesType($seriesType1);
        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm23 = $this->factoryService->createMultimediaObject($series2);

        $series3 = $this->createSeries('Series 3');
        $series3->setSeriesType($seriesType2);
        $mm31 = $this->factoryService->createMultimediaObject($series3);
        $mm32 = $this->factoryService->createMultimediaObject($series3);
        $mm33 = $this->factoryService->createMultimediaObject($series3);
        $mm34 = $this->factoryService->createMultimediaObject($series3);

        $mm11->addTag($tag1);
        $mm11->addTag($tag2);

        $mm12->addTag($tag1);
        $mm12->addTag($tag2);

        $mm13->addTag($tag1);
        $mm13->addTag($tag2);

        $mm21->addTag($tag2);

        $mm22->addTag($tag1);
        $mm22->addTag($tag2);

        $mm23->addTag($tag1);

        $mm31->addTag($tag1);

        $mm32->addTag($tag2);
        $mm32->addTag($tag3);

        $mm33->addTag($tag1);

        $mm34->addTag($tag1);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm13);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->persist($mm23);
        $this->dm->persist($mm31);
        $this->dm->persist($mm32);
        $this->dm->persist($mm33);
        $this->dm->persist($mm34);
        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(2, count($this->repo->findWithTagAndSeriesType($tag1, $seriesType1)));
        $this->assertEquals(2, count($this->repo->findWithTagAndSeriesType($tag2, $seriesType1)));
        $this->assertEquals(0, count($this->repo->findWithTagAndSeriesType($tag3, $seriesType1)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag1, $seriesType2)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag2, $seriesType2)));
        $this->assertEquals(1, count($this->repo->findWithTagAndSeriesType($tag3, $seriesType2)));
    }

    public function testFindOneBySeriesProperty()
    {
        $series1 = $this->createSeries('Series 1');
        $series1->setProperty('dataexample', 'title1');

        $series2 = $this->createSeries('Series 2');
        $series2->setProperty('dataexample', 'title2');

        $series3 = $this->createSeries('Series 3');
        $series3->setProperty('dataexample', 'title3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(1, count($this->repo->findOneBySeriesProperty('dataexample', $series1->getProperty('dataexample'))));
        $this->assertEquals(0, count($this->repo->findOneBySeriesProperty('data', $series2->getProperty('dataexample'))));
        $this->assertEquals(0, count($this->repo->findOneBySeriesProperty('dataexample', $series3->getProperty('data'))));
        $this->assertEquals(1, count($this->repo->findOneBySeriesProperty('dataexample', $series3->getProperty('dataexample'))));
    }

    public function testCount()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $this->assertEquals(3, $this->repo->count());
    }

    public function testCountPublic()
    {
        $series1 = $this->createSeries('Series 1');
        $series2 = $this->createSeries('Series 2');
        $series3 = $this->createSeries('Series 3');

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->persist($series3);
        $this->dm->flush();

        $mm = $this->createMultimediaObjectAssignedToSeries('mm_public1', $series1);
        $mm2 = $this->createMultimediaObjectAssignedToSeries('mm_public2', $series2);

        $this->assertEquals(2, $this->repo->countPublic());
    }

    private function createSeriesType($name)
    {
        $description = 'description';
        $series_type = new SeriesType();

        $series_type->setName($name);
        $series_type->setDescription($description);

        $this->dm->persist($series_type);
        $this->dm->flush();

        return $series_type;
    }

    private function createSeries($title)
    {
        $subtitle = 'subtitle';
        $description = 'description';
        $test_date = new \DateTime('now');

        $series = $this->factoryService->createSeries();

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setPublicDate($test_date);

        $this->dm->persist($series);

        return $series;
    }

    private function createMultimediaObjectAssignedToSeries($title, Series $series)
    {
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $subtitle = 'Subtitle';
        $description = 'Description';
        $duration = 123;

        $mm = $this->factoryService->createMultimediaObject($series);

        $mm->setStatus($status);
        $mm->setRecordDate($record_date);
        $mm->setPublicDate($public_date);
        $mm->setTitle($title);
        $mm->setSubtitle($subtitle);
        $mm->setDescription($description);
        $mm->setDuration($duration);
        $this->dm->persist($mm);

        return $mm;
    }
}
