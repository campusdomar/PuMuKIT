<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * PersonRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PersonRepository extends DocumentRepository
{
    public function findByRoleCodAndEmail($roleCode, $email)
    {
        $people = $this->createQueryBuilder()
            ->field('email')->equals($email)
            ->getQuery()
            ->execute()
        ;

        $mmobjRepo = $this->getDocumentManager()
            ->getRepository(MultimediaObject::class)
        ;
        foreach ($people as $person) {
            $mms = $mmobjRepo->findByPersonIdWithRoleCod($person->getId(), $roleCode);
            if ($mms->count() > 0) {
                return $person;
            }
        }

        return null;
    }
}
