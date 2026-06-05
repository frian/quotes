<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    /**
     * @return Artist[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('artist')
            ->orderBy('artist.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
