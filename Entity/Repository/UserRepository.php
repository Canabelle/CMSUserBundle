<?php

namespace Canabelle\CMSUserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Canabelle\CMSUserBundle\Entity\User;

class UserRepository extends EntityRepository
{
    /**
     * Returns active users query
     *
     * @return Query
     */
    public function getActiveUsersQuery()
    {
        $qb = $this->createQueryBuilder('u');
        return $qb
            ->where('u.enabled = :true')
            ->setParameter('true', true)
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
            ->getQuery();
    }

    /**
     * Returns active users
     *
     * @return User[]
     */
    public function getActiveUsers()
    {
        $query = $this->getActiveUsersQuery();
        try {
            $users = $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }

        $collator = new \Collator('cs_CZ');
        usort($users, function (User $a, User $b) use ($collator) {
            if ($a->getName() != $b->getName()) {
                return $collator->compare($a->getName(), $b->getName());
            } else {
                return strnatcmp($a->getId(), $b->getId());
            }
        });

        return $users;
    }

    /**
     * Returns users query
     *
     * @return Query
     */
    public function getUsersQuery()
    {
        $qb = $this->createQueryBuilder('u');
        return $qb
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
            ->getQuery();
    }

    /**
     * Returns users
     *
     * @return User[]
     */
    public function getUsers()
    {
        $query = $this->getUsersQuery();
        try {
            $users = $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }

        $collator = new \Collator('cs_CZ');
        usort($users, function (User $a, User $b) use ($collator) {
            if ($a->getName() != $b->getName()) {
                return $collator->compare($a->getName(), $b->getName());
            } else {
                return strnatcmp($a->getId(), $b->getId());
            }
        });

        return $users;
    }
}