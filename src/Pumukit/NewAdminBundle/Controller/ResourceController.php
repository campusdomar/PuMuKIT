<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pumukit\SchemaBundle\Utils\Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;

class ResourceController extends Controller
{
    public static $resourceName = 'series';
    public static $repoName = 'PumukitSchemaBundle:Series';

    public function getResourceName()
    {
        return static::$resourceName;
    }

    public function getPluralResourceName()
    {
        return static::$resourceName.'s';
    }

    private function getRedirectRoute($routeName = 'index')
    {
        $resourceName = $this->getResourceName();

        return 'pumukitnewadmin_'.$resourceName.'_'.$routeName;
    }

    public function redirectToIndex()
    {
        $resourceName = $this->getResourceName();

        return $this->redirect($this->generateUrl($this->getRedirectRoute()));
    }

    private function getRedirectParameters($name)
    {
        return array();
    }

    public function getRepository()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        return $dm->getRepository(static::$repoName);
    }

    public function getSorting(Request $request = null, $session_namespace = null)
    {
        return array();
    }

    protected function createPager($criteria, $sorting)
    {
        $repo = $this->getRepository();

        $queryBuilder = $repo->createQueryBuilder();

        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->sort($sorting);

        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);

        return $pagerfanta;
    }

    public function findOr404(Request $request, array $criteria = array())
    {
        if ($request->get('slug')) {
            $default = array('slug' => $request->get('slug'));
        } elseif ($request->get('id')) {
            $default = array('id' => $request->get('id'));
        } else {
            $default = array();
        }

        $criteria = array_merge($default, $criteria);

        $repo = $this->getRepository();
        if (!$resource = $repo->findOneBy($criteria)
        ) {
            throw new NotFoundHttpException(
                sprintf(
                    'Requested %s does not exist with these criteria: %s.',
                    $this->getResourceName(),
                    json_encode($criteria)
                )
            );
        }

        return $resource;
    }

    public function update($resource)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($resource);
        $dm->flush();
    }
}
