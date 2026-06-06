<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return Tag[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('tag')
            ->orderBy('tag.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tag[]
     */
    public function findMostUsed(int $limit = 8): array
    {
        return $this->createQueryBuilder('tag')
            ->addSelect('COUNT(excerpt.id) AS HIDDEN excerptCount')
            ->leftJoin('tag.excerpts', 'excerpt')
            ->groupBy('tag.id')
            ->orderBy('excerptCount', 'DESC')
            ->addOrderBy('tag.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
