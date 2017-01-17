<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Group;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Translation\TranslatorInterface;

class GroupService
{
    private $dm;
    private $repo;
    private $userRepo;
    private $mmobjRepo;
    private $dispatcher;
    private $translator;

    /**
     * Constructor.
     *
     * @param DocumentManager             $documentManager
     * @param GroupEventDispatcherService $dispatcher
     * @param TranslatorInterface         $translator
     */
    public function __construct(DocumentManager $documentManager, GroupEventDispatcherService $dispatcher, TranslatorInterface $translator)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Group');
        $this->userRepo = $this->dm->getRepository('PumukitSchemaBundle:User');
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    /**
     * Create group.
     *
     * @param Group $group
     *
     * @return Group
     */
    public function create(Group $group)
    {
        $groupByKey = $this->repo->findOneByKey($group->getKey());
        $groupByName = $this->repo->findOneByName($group->getName());
        if ($groupByKey && $groupByName) {
            throw new \Exception('There is already a group created with key '.$group->getKey().' and a group created with name '.$group->getName().'.');
        }
        if ($groupByKey) {
            throw new \Exception('There is already a group created with key '.$group->getKey().'.');
        }
        if ($groupByName) {
            throw new \Exception('There is already a group created with name '.$group->getName().'.');
        }
        $this->dm->persist($group);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($group);

        return $group;
    }

    /**
     * Update group.
     *
     * @param Group $group
     * @param bool  $executeFlush
     *
     * @return Group
     */
    public function update(Group $group, $executeFlush = true)
    {
        $auxKeyGroup = $this->repo->findOneByKey($group->getKey());
        if ($auxKeyGroup) {
            if ($auxKeyGroup->getId() != $group->getId()) {
                throw new \Exception('There is already a group created with key '.$group->getKey().'.');
            }
        }

        $auxNameGroup = $this->repo->findOneByName($group->getName());
        if ($auxNameGroup) {
            if ($auxNameGroup->getId() != $group->getId()) {
                throw new \Exception('There is already a group created with name '.$group->getName().'.');
            }
        }

        $group->setUpdatedAt(new \Datetime('now'));
        $this->dm->persist($group);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchUpdate($group);

        return $group;
    }

    /**
     * Delete group.
     *
     * @param Group $group
     * @param bool  $executeFlush
     */
    public function delete(Group $group, $executeFlush = true)
    {
        if (!$this->canBeDeleted($group)) {
            throw new \Exception('Not allowed to delete Group "'.$group->getKey().'": is external Group and/or has existent relations with users and multimedia objects.');
        }
        $this->dm->remove($group);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchDelete($group);
    }

    /**
     * Can be deleted.
     *
     * @param Group $group
     *
     * @return bool
     */
    public function canBeDeleted(Group $group)
    {
        if (!$group->isLocal()) {
            return false;
        }
        if (0 < $this->countUsersInGroup($group)) {
            return false;
        }
        if (0 < $this->countAdminMultimediaObjectsInGroup($group)) {
            return false;
        }
        if (0 < $this->countPlayMultimediaObjectsInGroup($group)) {
            return false;
        }

        return true;
    }

    /**
     * Get delete message.
     *
     * @param Group  $group
     * @param string $locale
     *
     * @return string
     */
    public function getDeleteMessage(Group $group, $locale)
    {
        $message = '';
        if (!$group->isLocal()) {
            $enMessage = 'Group cannot be deleted because the Group is external. Contact your directory server administrator. You can delete relations with MultimediaObjects if any.';
            $message = $this->translator->trans($enMessage, array(), null, $locale);

            return $message;
        }
        $users = $this->countUsersInGroup($group);
        $admin = $this->countAdminMultimediaObjectsInGroup($group);
        $play = $this->countPlayMultimediaObjectsInGroup($group);

        if ((0 === $users) && (0 === $admin) && (0 === $play)) {
            $enMessage = 'ATTENTION!! Are you sure you want to delete this group?';
            $message = $this->translator->trans($enMessage, array(), null, $locale);
        } elseif ((0 < $users) && (0 === $admin) && (0 === $play)) {
            if (1 === $users) {
                $enMessage = 'Group cannot be deleted because there is 1 user related. Please, delete this relation first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
            } else {
                $enMessage = 'Group cannot be deleted because there are %s users related. Please, delete these relations first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
                $message = sprintf($message, $users);
            }
        } elseif ((0 === $users) && (0 < $admin) && (0 === $play)) {
            if (1 === $admin) {
                $enMessage = 'Group cannot be deleted because there is 1 admin MultimediaObject related. Please, delete this relation first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
            } else {
                $enMessage = 'Group cannot be deleted because there are %s admin MultimediaObjects related. Please, delete these relations first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
                $message = sprintf($message, $admin);
            }
        } elseif ((0 === $users) && (0 === $admin) && (0 < $play)) {
            if (1 === $play) {
                $enMessage = 'Group cannot be deleted because there is 1 play MultimediaObject related. Please, delete this relation first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
            } else {
                $enMessage = 'Group cannot be deleted because there are %s play MultimediaObject related. Please, delete these relations first.';
                $message = $this->translator->trans($enMessage, array(), null, $locale);
                $message = sprintf($message, $play);
            }
        } elseif ((0 < $users) && (0 < $admin) && (0 === $play)) {
            $enMessage = 'Group cannot be deleted because there are %s user(s) and %s admin MultimediaObject(s) related. Please, delete these relations first.';
            $message = $this->translator->trans($enMessage, array(), null, $locale);
            $message = sprintf($message, $users, $admin);
        } elseif ((0 < $users) && (0 === $admin) && (0 < $play)) {
            $enMessage = 'Group cannot be deleted because there are %s user(s) and %s play MultimediaObject(s) related. Please, delete these relations first.';
            $message = $this->translator->trans($enMessage, array(), null, $locale);
            $message = sprintf($message, $users, $play);
        } elseif ((0 === $users) && (0 < $admin) && (0 < $play)) {
            $enMessage = 'Group cannot be deleted because there are %s admin MultimediaObject(s) and %s play MultimediaObject(s) related. Please, delete these relations first.';
            $message = $this->translator->trans($enMessage, array(), null, $locale);
            $message = sprintf($message, $admin, $play);
        } elseif ((0 < $users) && (0 < $admin) && (0 < $play)) {
            $enMessage = 'Group cannot be deleted because there are %s user(s), %s admin MultimediaObject(s) and %s play MultimediaObject(s) related. Please, delete these relations first.';
            $message = $this->translator->trans($enMessage, array(), null, $locale);
            $message = sprintf($message, $users, $admin, $play);
        }

        return $message;
    }

    /**
     * Count resources.
     *
     * @param array $groups
     *
     * @return array
     */
    public function countResources($groups)
    {
        $countResources = array();
        foreach ($groups as $group) {
            $countResources[$group->getId()] = $this->countResourcesInGroup($group);
        }

        return $countResources;
    }

    /**
     * Count resources in group.
     *
     * @param Group $group
     *
     * @return array
     */
    public function countResourcesInGroup(Group $group)
    {
        $countResources = array();
        $countResources['users'] = $this->countUsersInGroup($group);
        $countResources['adminMultimediaObjects'] = $this->countAdminMultimediaObjectsInGroup($group);
        $countResources['playMultimediaObjects'] = $this->countPlayMultimediaObjectsInGroup($group);

        return $countResources;
    }

    /**
     * Count admin multimediaObjects in group.
     *
     * @param Group $group
     *
     * @return int
     */
    public function countAdminMultimediaObjectsInGroup(Group $group)
    {
        return $this->mmobjRepo->countWithGroup($group);
    }

    /**
     * Count play multimediaObjects in group.
     *
     * @param Group $group
     *
     * @return int
     */
    public function countPlayMultimediaObjectsInGroup(Group $group)
    {
        return $this->mmobjRepo->countWithGroupInEmbeddedBroadcast($group);
    }

    /**
     * Count users in group.
     *
     * @param Group $group
     *
     * @return int
     */
    public function countUsersInGroup(Group $group)
    {
        return $this->userRepo->createQueryBuilder()
            ->field('groups')->equals($group->getId())
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Find users in group.
     *
     * @param Group $group
     * @param array $sort
     *
     * @return Cursor
     */
    public function findUsersInGroup(Group $group, $sort = array())
    {
        $qb = $this->userRepo->createQueryBuilder()
            ->field('groups')->equals($group->getId());
        if (0 !== count($sort)) {
            $qb->sort($sort);
        }

        return $qb->getQuery()
            ->execute();
    }

    /**
     * Find group by id.
     *
     * @param string $id
     *
     * @return Group
     */
    public function findById($id)
    {
        return $this->repo->find($id);
    }

    /**
     * Find all.
     *
     * @return ArrayCollection
     */
    public function findAll()
    {
        return $this->repo->findBy(array(), array('key' => 1));
    }

    /**
     * Find groups not in
     * the given array.
     *
     * @param array $ids
     *
     * @return Cursor
     */
    public function findByIdNotIn($ids = array())
    {
        return $this->repo->findByIdNotIn($ids);
    }

    /**
     * Find groups not in
     * the given array but
     * in the total of groups
     * given.
     *
     * @param array $ids
     * @param array $total
     *
     * @return Cursor
     */
    public function findByIdNotInOf($ids = array(), $total = array())
    {
        return $this->repo->findByIdNotInOf($ids, $total);
    }
}
