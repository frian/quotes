<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    /**
     * @return int[]
     */
    public function findReleaseYears(): array
    {
        $rows = $this->createQueryBuilder('album')
            ->select('DISTINCT album.releaseYear AS releaseYear')
            ->orderBy('album.releaseYear', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['releaseYear'], $rows);
    }

    /**
     * @return Album[]
     */
    public function findAllOrderedByArtistAndTitle(): array
    {
        return $this->createQueryBuilder('album')
            ->join('album.artist', 'artist')
            ->addSelect('artist')
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.releaseYear', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
