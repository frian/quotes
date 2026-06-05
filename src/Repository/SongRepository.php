<?php

namespace App\Repository;

use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Song>
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    /**
     * @return Song[]
     */
    public function findAllOrderedByArtistAlbumAndTitle(): array
    {
        return $this->createQueryBuilder('song')
            ->join('song.album', 'album')
            ->join('album.artist', 'artist')
            ->addSelect('album', 'artist')
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.releaseYear', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
