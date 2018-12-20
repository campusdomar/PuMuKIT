<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\NewAdminBundle\Form\Type\RoleType;
use Pumukit\SchemaBundle\Document\Role;

/**
 * @Security("is_granted('ROLE_ACCESS_ROLES')")
 */
class RoleController extends SortableAdminController implements NewAdminController
{
    public static $resourceName = 'role';
    public static $repoName = 'PumukitSchemaBundle:Role';

    /**
     * Update role.
     *
     * @Template("PumukitNewAdminBundle:Role:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $role = $personService->findRoleById($request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new RoleType($translator, $locale), $role);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($form->bind($request)->isValid()) {
                try {
                    $person = $personService->updateRole($role);
                } catch (\Exception $e) {
                    return new JsonResponse(array('status' => $e->getMessage()), 409);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_role_list'));
            } else {
                $errors = $this->get('validator')->validate($role);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
        }

        return array(
            'role' => $role,
            'form' => $form->createView(),
        );
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);
        $sorting['rank'] = 'asc';
        $session = $this->get('session');
        $session_namespace = 'admin/'.$this->getResourceName();

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($session_namespace.'/page', 1));

        return $resources;
    }

    /**
     * Delete action.
     */
    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $this->getResourceName();

        if (0 !== $resource->getNumberPeopleInMultimediaObject()) {
            return new Response("Can not delete role '".$resource->getName()."', There are Multimedia objects with this role. ", 409);
        }

        $this->get('pumukitschema.factory')->deleteResource($resource);
        if ($resourceId === $this->get('session')->get('admin/'.$resourceName.'/id')) {
            $this->get('session')->remove('admin/'.$resourceName.'/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $resourceName = $this->getResourceName();

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            if (0 !== $resource->getNumberPeopleInMultimediaObject()) {
                continue;
            }

            try {
                $factory->deleteResource($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/'.$resourceName.'/id')) {
                $this->get('session')->remove('admin/'.$resourceName.'/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    public function createNew()
    {
        return new Role();
    }
}
