<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\PermissionProfileType;

/**
 * @Security("is_granted('ROLE_ACCESS_PERMISSION_PROFILES')")
 */
class PermissionProfileController extends AdminController implements NewAdminController
{
    public static $resourceName = 'permissionprofile';
    public static $repoName = 'PumukitSchemaBundle:PermissionProfile';

    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session.
     *
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($request->get('criteria', array()));
        $permissionProfiles = $this->getResources($request, $criteria);

        list($permissions, $dependencies) = $this->getPermissions();
        $scopes = PermissionProfile::$scopeDescription;

        $createBroadcastsEnabled = !$this->container->getParameter('pumukit_new_admin.disable_broadcast_creation');
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');

        return array(
            'permissionprofiles' => $permissionProfiles,
            'permissions' => $permissions,
            'scopes' => $scopes,
            'dependencies' => $dependencies,
        );
    }

    /**
     * List action.
     *
     * Overwrite to have permissions list
     *
     * @Template()
     */
    public function listAction(Request $request)
    {
        $session = $this->get('session');
        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($request->get('criteria', array()));
        $permissionProfiles = $this->getResources($request, $criteria);

        $page = $session->get('admin/permissionprofile/page', 1);
        $maxPerPage = $session->get('admin/permissionprofile/paginate', 9);
        $newPermissionProfileId = $request->get('id');
        if ($newPermissionProfileId && (($permissionProfiles->getNbResults() / $maxPerPage) > $page)) {
            $page = $permissionProfiles->getNbPages();
            $session->set('admin/permissionprofile/page', $page);
        }
        $permissionProfiles->setCurrentPage($page);

        list($permissions, $dependencies) = $this->getPermissions();
        $scopes = PermissionProfile::$scopeDescription;

        return array(
            'permissionprofiles' => $permissionProfiles,
            'permissions' => $permissions,
            'scopes' => $scopes,
            'dependencies' => $dependencies,
        );
    }

    /**
     * Create Action
     * Overwrite to give PermissionProfileType name correctly.
     *
     * @Template()
     */
    public function createAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');

        $permissionProfile = new PermissionProfile();
        $form = $this->getForm($permissionProfile);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $permissionProfile = $permissionProfileService->update($permissionProfile, true);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }
            if (null === $permissionProfile) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list', array('id' => $permissionProfile->getId())));
        }

        return array(
            'permissionprofile' => $permissionProfile,
            'form' => $form->createView(),
        );
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @Template()
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');

        $permissionProfile = $this->findOr404($request);
        $form = $this->getForm($permissionProfile);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $permissionProfile = $permissionProfileService->update($permissionProfile);
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
        }

        return array(
            'permissionprofile' => $permissionProfile,
            'form' => $form->createView(),
        );
    }

    /**
     * Overwrite to get form with translations.
     *
     * @param object|null $permissionProfile
     */
    public function getForm($permissionProfile = null)
    {
        $translator = $this->get('translator');
        $locale = $this->getRequest()->getLocale();

        $form = $this->createForm(new PermissionProfileType($translator, $locale), $permissionProfile);

        return $form;
    }

    /**
     * Delete action.
     *
     * Overwrite to change default user permission
     * if the default one is being deleted
     */
    public function deleteAction(Request $request)
    {
        $permissionProfile = $this->findOr404($request);
        $permissionProfileId = $permissionProfile->getId();
        $changeDefault = $permissionProfile->isDefault();

        $response = $this->isAllowedToBeDeleted($permissionProfile);
        if ($response instanceof Response) {
            return $response;
        }

        try {
            $this->get('pumukitschema.factory')->deleteResource($permissionProfile);
            $this->get('pumukitschema.permissionprofile_dispatcher')->dispatchDelete($permissionProfile);
            if ($permissionProfileId === $this->get('session')->get('admin/permissionprofile/id')) {
                $this->get('session')->remove('admin/permissionprofile/id');
            }
            $newDefault = $this->get('pumukitschema.permissionprofile')->checkDefault($permissionProfile);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    /**
     * Batch update action.
     */
    public function batchUpdateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:PermissionProfile');
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');

        $selectedDefault = $this->getRequest()->get('selected_default');
        $selectedScopes = $this->getRequest()->get('selected_scopes');
        $checkedPermissions = $this->getRequest()->get('checked_permissions');

        if ('string' === gettype($selectedScopes)) {
            $selectedScopes = json_decode($selectedScopes, true);
        }
        if ('string' === gettype($checkedPermissions)) {
            $checkedPermissions = json_decode($checkedPermissions, true);
        }

        $newDefaultPermissionProfile = $this->find($selectedDefault);
        if (null !== $newDefaultPermissionProfile) {
            if (!$newDefaultPermissionProfile->isDefault()) {
                $newDefaultPermissionProfile->setDefault(true);
                $newDefaultPermissionProfile = $permissionProfileService->update($newDefaultPermissionProfile);
            }
        }

        $allPermissionProfiles = $this->isGranted('ROLE_SUPER_ADMIN') ? $repo->findAll() : $repo->findBySystem(false);

        //Doing a batch update for all checked profiles. This will remove everything except the checked permissions.
        $permissionProfiles = $this->buildPermissionProfiles($checkedPermissions, $selectedScopes);
        foreach ($permissionProfiles as $profileId => $p) {
            $permissionProfile = $this->findPermissionProfile($allPermissionProfiles, $profileId);
            if (null === $permissionProfile) {
                continue;
            }
            try {
                $permissionProfile = $permissionProfileService->setScope($permissionProfile, $p['scope'], false);
                $permissionProfileService->batchUpdate($permissionProfile, $p['permissions'], false);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    /**
     * Returns an array with all permissions and there newly set (if any) permissions and scope.
     *
     * returns $permissionProfiles = array(
     *             'PROFILE_NAME' => array('PERM1', 'PERM2', 'PERM3', ...),
     *             (...) ,
     *         );
     */
    private function buildPermissionProfiles($checkedPermissions, $selectedScopes)
    {
        $permissionProfiles = array();
        //Adds scope and checked permissions to permissions.
        foreach ($checkedPermissions as $permission) {
            $data = $this->separateAttributePermissionProfilesIds($permission);
            $permissionProfiles[$data['profileId']]['permissions'][] = $data['attribute'];
        }
        foreach ($selectedScopes as $selectedScope) {
            $data = $this->separateAttributePermissionProfilesIds($selectedScope);
            if (isset($permissionProfiles[$data['profileId']])) {
                $permissionProfiles[$data['profileId']]['scope'] = $data['attribute'];
            } else {
                $permissionProfiles[$data['profileId']] = array(
                    'permissions' => array(),
                    'scope' => $data['attribute'],
                );
            }
        }

        return $permissionProfiles;
    }

    private function separateAttributePermissionProfilesIds($pair = '')
    {
        $data = array('attribute' => '', 'profileId' => '');
        if ($pair) {
            $output = explode('__', $pair);
            if (array_key_exists(0, $output)) {
                $data['attribute'] = $output[0];
            }
            if (array_key_exists(1, $output)) {
                $data['profileId'] = $output[1];
            }
        }

        return $data;
    }

    private function findPermissionProfile($permissionProfiles, $id = '')
    {
        foreach ($permissionProfiles as $permissionProfile) {
            if ($id == $permissionProfile->getId()) {
                return $permissionProfile;
            }
        }

        return null;
    }

    private function isAllowedToBeDeleted(PermissionProfile $permissionProfile)
    {
        $userService = $this->get('pumukitschema.user');
        $usersWithPermissionProfile = $userService->countUsersWithPermissionProfile($permissionProfile);

        if (0 < $usersWithPermissionProfile) {
            return new Response('Can not delete this permission profile "'.$permissionProfile->getName().'". There are '.$usersWithPermissionProfile.' user(s) with this permission profile.', Response::HTTP_FORBIDDEN);
        }

        return true;
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * Override to get 9 resources per page
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting();
        if (!isset($sorting['rank'])) {
            $sorting['rank'] = 1;
        }
        $session = $this->get('session');
        $session_namespace = 'admin/permissionprofile';

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 9));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 9))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($session_namespace.'/page', 1));

        return $resources;
    }

    private function getPermissions()
    {
        $permissionService = $this->get('pumukitschema.permission');
        $permissions = $permissionService->getAllPermissions();

        if ($this->container->getParameter('pumukit_new_admin.disable_broadcast_creation')) {
            unset($permissions[Permission::ACCESS_BROADCASTS]);
        }

        if (!$this->container->hasParameter('pumukit2.use_series_channels') || !$this->container->getParameter('pumukit2.use_series_channels')) {
            unset($permissions[Permission::ACCESS_SERIES_TYPES]);
        }

        $dependencies = $permissionService->getAllDependencies();

        return array($permissions, $dependencies);
    }
}
