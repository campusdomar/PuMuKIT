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
     *
     *
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
     *
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
