<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
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

    /**
     * @return SongExcerpt[]
     */
    public function findLatest(int $limit = 12): array
    {
        return $this->createQueryBuilder('excerpt')
            ->addSelect('song', 'album', 'artist', 'tag')
            ->join('excerpt.song', 'song')
            ->join('song.album', 'album')
            ->join('album.artist', 'artist')
            ->leftJoin('excerpt.tags', 'tag')
            ->orderBy('excerpt.createdAt', 'DESC')
            ->addOrderBy('excerpt.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRandom(): ?SongExcerpt
    {
        $rows = $this->createQueryBuilder('excerpt')
            ->select('excerpt.id')
            ->getQuery()
            ->getScalarResult();

        if ($rows === []) {
            return null;
        }

        $row = $rows[random_int(0, count($rows) - 1)];

        return $this->baseListQueryBuilder()
            ->andWhere('excerpt.id = :id')
            ->setParameter('id', (int) $row['id'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findAllOrdered(): array
    {
        return $this->baseListQueryBuilder()
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.releaseYear', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findByTag(Tag $tag): array
    {
        return $this->baseListQueryBuilder()
            ->andWhere(':tag MEMBER OF excerpt.tags')
            ->setParameter('tag', $tag)
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.releaseYear', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findByArtist(Artist $artist): array
    {
        return $this->baseListQueryBuilder()
            ->andWhere('artist = :artist')
            ->setParameter('artist', $artist)
            ->orderBy('album.releaseYear', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findByAlbum(Album $album): array
    {
        return $this->baseListQueryBuilder()
            ->andWhere('album = :album')
            ->setParameter('album', $album)
            ->orderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findBySong(Song $song): array
    {
        return $this->baseListQueryBuilder()
            ->andWhere('song = :song')
            ->setParameter('song', $song)
            ->orderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function findByReleaseYear(int $year): array
    {
        return $this->baseListQueryBuilder()
            ->andWhere('album.releaseYear = :year')
            ->setParameter('year', $year)
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SongExcerpt[]
     */
    public function search(string $term): array
    {
        $query = strtolower(trim($term));

        if ($query === '') {
            return [];
        }

        return $this->baseListQueryBuilder()
            ->andWhere('LOWER(excerpt.body) LIKE :query')
            ->orWhere('LOWER(excerpt.note) LIKE :query')
            ->orWhere('LOWER(song.title) LIKE :query')
            ->orWhere('LOWER(album.title) LIKE :query')
            ->orWhere('LOWER(artist.name) LIKE :query')
            ->orWhere('LOWER(tag.name) LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('artist.name', 'ASC')
            ->addOrderBy('album.releaseYear', 'ASC')
            ->addOrderBy('album.title', 'ASC')
            ->addOrderBy('song.title', 'ASC')
            ->addOrderBy('excerpt.position', 'ASC')
            ->addOrderBy('excerpt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function baseListQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('excerpt')
            ->addSelect('song', 'album', 'artist', 'tag')
            ->join('excerpt.song', 'song')
            ->join('song.album', 'album')
            ->join('album.artist', 'artist')
            ->leftJoin('excerpt.tags', 'tag');
    }
}
