<?php

namespace App\Repository;

use App\Entity\SongExcerpt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SongExcerpt>
 */
class SongExcerptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SongExcerpt::class);
    }
}
