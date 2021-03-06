<?php

namespace Canabelle\CMSUserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class GroupRepository extends EntityRepository
{
    /**
     * @return Query
     */
    public function getGroupsQuery()
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.name', 'asc')
            ->getQuery();
    }
}