<?php

namespace Pumukit\SecurityBundle\Services;

use Pumukit\SchemaBundle\Document\Group;

class LoginService
{
    public function __construct($userService, $personService, $groupService, $dm) {
        $this->userService = $userService;
        $this->personService = $personService;
        $this->groupService = $groupService;
        $this->groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
    }

    /**
     * Creates a default user with the parameters passed.
     *
     * The permission profile will be 'Auto Publisher' if the user is added to a 'PAS/PDI' group.
     * Otherwise, it will try to get the defaultPermissionProfile from the service.
     *
     * @param string $username
     * @param string $email
     * @param string $origin A string indicating from where was the user created.
     * @param Group $group (optional) Null by default. A group the user can be added to.
     * @param boolean $enabled (optional) True by default. Whether the user is enabled after creation or not.
     * @return User $user The created user
     */
    public function createDefaultUser($username, $email, $origin, Group $group = null, $enabled = true){

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        if(!$group || !in_array($group->getKey(), grouparray('PAS', 'PDI'))){
            $permissionProfile = $permissionProfileService->getDefault();
        }
        else {
            $permissionProfile = $permissionProfileService->getByName("Auto Publisher");
        }
        if (null == $permissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User.');
        }

        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin($origin);
        $user->setEnabled($enabled);

        $this->userService->create($user);
        if($group != null)
            $this->userService->addGroup($group, $user, true, false);
        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    /**
     * Finds a group with the given key or creates one.
     *
     * If the group with the given key is not found, it will create one using the $origin variable.
     * @param string $key
     * @param string $origin
     * @return Group $group
     *
     */
    public function getGroup($key, $origin)
    {
        $cleanKey = preg_replace('/\W/', '', $key);
        $group = $this->groupRepo->findOneByKey($cleanKey);

        if ($group) {
            return $group;
        }
        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin($origin);
        $this->groupService->create($group);

        return $group;

    }
}
