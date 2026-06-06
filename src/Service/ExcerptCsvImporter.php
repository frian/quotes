<?php

namespace App\Service;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;

class ExcerptCsvImporter
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array{mode: string, total: int, ready: int, errors: string[]}
     */
    public function preview(string $content): array
    {
        $rows = $this->parseRows($content);
        $ready = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;

            try {
                $this->validateRow($row);
                $ready++;
            } catch (\InvalidArgumentException $exception) {
                $errors[] = sprintf('Ligne %d : %s', $lineNumber, $exception->getMessage());
            }
        }

        return [
            'mode' => 'preview',
            'total' => count($rows),
            'ready' => $ready,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{mode: string, total: int, imported: int, errors: string[]}
     */
    public function import(string $content): array
    {
        $rows = $this->parseRows($content);
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;

            try {
                $validatedRow = $this->validateRow($row);
                $excerpt = $this->createExcerptFromValidatedRow($validatedRow);
                $this->entityManager->persist($excerpt);
                $this->entityManager->flush();
                $imported++;
            } catch (\InvalidArgumentException $exception) {
                $errors[] = sprintf('Ligne %d : %s', $lineNumber, $exception->getMessage());
            }
        }

        return [
            'mode' => 'import',
            'total' => count($rows),
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseRows(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new \InvalidArgumentException('Le CSV est vide.');
        }

        $delimiter = $this->detectDelimiter($content);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Impossible de préparer l’import.');
        }

        fwrite($handle, $content);
        rewind($handle);

        $headers = null;
        $rows = [];

        while (($columns = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($headers === null) {
                $headers = array_map(
                    static fn (string $header): string => preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim(ltrim($header, "\xEF\xBB\xBF")))) ?? '',
                    $columns,
                );
                continue;
            }

            if ($columns === [null] || $columns === false || array_filter($columns, static fn ($value): bool => trim((string) $value) !== '') === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $position => $header) {
                $row[$header] = isset($columns[$position]) ? trim((string) $columns[$position]) : '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        if ($headers === null) {
            throw new \InvalidArgumentException('Le CSV doit contenir une ligne d’en-tête.');
        }

        $requiredHeaders = ['artist', 'album', 'year', 'song', 'body'];
        $hasRequiredHeaders = array_reduce(
            $requiredHeaders,
            static fn (bool $carry, string $header): bool => $carry && in_array($header, $headers, true),
            true,
        );

        if (!$hasRequiredHeaders) {
            $contentLines = preg_split('/\R/', $content) ?: [];
            $firstLine = (string) ($contentLines[0] ?? '');
            $firstLineColumns = str_getcsv($firstLine, $delimiter, '"', '');
            $firstLineHeaders = array_map(
                static fn (string $header): string => preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim(ltrim($header, "\xEF\xBB\xBF")))) ?? '',
                $firstLineColumns,
            );

            $looksLikeHeaderRow = in_array('artist', $firstLineHeaders, true) && in_array('album', $firstLineHeaders, true);

            if (!$looksLikeHeaderRow) {
                $fallbackValues = array_slice(array_pad($firstLineColumns, 9, ''), 0, 9);
                $rows = array_merge([
                    array_combine(
                        ['artist', 'album', 'year', 'song', 'body', 'source_url', 'tags', 'note', 'position'],
                        $fallbackValues,
                    ) ?: [],
                ], $rows);
            }
        }

        return $rows;
    }

    private function detectDelimiter(string $content): string
    {
        $lines = preg_split('/\R/', $content, 2) ?: [];
        $firstLine = (string) ($lines[0] ?? '');
        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');
        $tabCount = substr_count($firstLine, "\t");

        if ($tabCount > $semicolonCount && $tabCount > $commaCount) {
            return "\t";
        }

        return $semicolonCount >= $commaCount ? ';' : ',';
    }

    /**
     * @param array<string, string> $row
     *
     * @return array{
     *     artist: string,
     *     album: string,
     *     year: int,
     *     song: string,
     *     body: string,
     *     source_url: ?string,
     *     note: ?string,
     *     position: ?int,
     *     tag_names: string[]
     * }
     */
    private function validateRow(array $row): array
    {
        $artistName = $this->normalizeCatalogText($row['artist'] ?? '');
        $albumTitle = $this->normalizeCatalogText($row['album'] ?? '');
        $songTitle = $this->normalizeCatalogText($row['song'] ?? '');
        $body = $this->normalizeBodyText($row['body'] ?? '');

        if ($artistName === '' || $albumTitle === '' || $songTitle === '' || $body === '') {
            throw new \InvalidArgumentException('artist, album, song et body sont obligatoires.');
        }

        $yearValue = $this->normalizeCatalogText($row['year'] ?? '');
        if ($yearValue === '' || !ctype_digit($yearValue)) {
            throw new \InvalidArgumentException('year doit contenir une année valide.');
        }

        $position = $this->normalizeCatalogText($row['position'] ?? '');
        $resolvedPosition = null;
        if ($position !== '') {
            if (!ctype_digit($position)) {
                throw new \InvalidArgumentException('position doit être numérique.');
            }

            $resolvedPosition = (int) $position;
        }

        return [
            'artist' => $artistName,
            'album' => $albumTitle,
            'year' => (int) $yearValue,
            'song' => $songTitle,
            'body' => $body,
            'source_url' => $this->normalizeOptionalText($row['source_url'] ?? null),
            'note' => $this->normalizeOptionalText($row['note'] ?? null),
            'position' => $resolvedPosition,
            'tag_names' => $this->parseTagNames($row['tags'] ?? ''),
        ];
    }

    /**
     * @param array{
     *     artist: string,
     *     album: string,
     *     year: int,
     *     song: string,
     *     body: string,
     *     source_url: ?string,
     *     note: ?string,
     *     position: ?int,
     *     tag_names: string[]
     * } $row
     */
    private function createExcerptFromValidatedRow(array $row): SongExcerpt
    {
        $artist = $this->findOrCreateArtist($row['artist']);
        $album = $this->findOrCreateAlbum($artist, $row['album'], $row['year']);
        $song = $this->findOrCreateSong($album, $row['song']);
        $this->applySongSourceUrl($song, $row['source_url']);

        $excerpt = (new SongExcerpt())
            ->setSong($song)
            ->setBody($row['body'])
            ->setNote($row['note'])
            ->setPosition($row['position']);

        foreach ($row['tag_names'] as $tagName) {
            $excerpt->addTag($this->findOrCreateTag($tagName));
        }

        return $excerpt;
    }

    private function normalizeBodyText(mixed $value): string
    {
        $text = trim((string) $value);
        $text = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return preg_replace('/[ \t]+$/m', '', $text) ?? $text;
    }

    private function findOrCreateArtist(string $name): Artist
    {
        $name = $this->normalizeCatalogText($name);
        $artist = $this->findArtistByNormalizedName($name);

        if ($artist instanceof Artist) {
            return $artist;
        }

        $artist = (new Artist())->setName($name);
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $artist;
    }

    private function findOrCreateAlbum(Artist $artist, string $title, int $releaseYear): Album
    {
        $title = $this->normalizeCatalogText($title);
        $album = $this->findAlbumByNormalizedIdentity($artist, $title, $releaseYear);

        if ($album instanceof Album) {
            return $album;
        }

        $album = (new Album())
            ->setArtist($artist)
            ->setTitle($title)
            ->setReleaseYear($releaseYear);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $album;
    }

    private function findOrCreateSong(Album $album, string $title): Song
    {
        $title = $this->normalizeCatalogText($title);
        $song = $this->findSongByNormalizedIdentity($album, $title);

        if ($song instanceof Song) {
            return $song;
        }

        $song = (new Song())
            ->setAlbum($album)
            ->setTitle($title);

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $song;
    }

    private function findOrCreateTag(string $name): Tag
    {
        $name = $this->normalizeCatalogText($name);
        $tag = $this->findTagByNormalizedName($name);

        if ($tag instanceof Tag) {
            return $tag;
        }

        $tag = (new Tag())->setName($name);
        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    private function applySongSourceUrl(Song $song, ?string $sourceUrl): void
    {
        if ($sourceUrl !== null || $song->getId() === null) {
            $song->setSourceUrl($sourceUrl);
        }
    }

    /**
     * @return string[]
     */
    private function parseTagNames(string $tagNames): array
    {
        $names = array_map('trim', explode(',', $tagNames));
        $names = array_filter($names, static fn (string $name): bool => $name !== '');

        return array_values(array_unique($names));
    }

    private function normalizeOptionalText(mixed $value): ?string
    {
        $text = $this->normalizeCatalogText($value);

        return $text !== '' ? $text : null;
    }

    private function normalizeCatalogText(mixed $value): string
    {
        $text = trim((string) $value);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    private function findArtistByNormalizedName(string $name): ?Artist
    {
        return $this->entityManager->getRepository(Artist::class)
            ->createQueryBuilder('artist')
            ->andWhere('LOWER(TRIM(artist.name)) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findAlbumByNormalizedIdentity(Artist $artist, string $title, int $releaseYear): ?Album
    {
        return $this->entityManager->getRepository(Album::class)
            ->createQueryBuilder('album')
            ->join('album.artist', 'artist')
            ->andWhere('album.artist = :artist')
            ->andWhere('LOWER(TRIM(album.title)) = :title')
            ->andWhere('album.releaseYear = :releaseYear')
            ->setParameter('artist', $artist)
            ->setParameter('title', mb_strtolower($title))
            ->setParameter('releaseYear', $releaseYear)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findSongByNormalizedIdentity(Album $album, string $title): ?Song
    {
        return $this->entityManager->getRepository(Song::class)
            ->createQueryBuilder('song')
            ->andWhere('song.album = :album')
            ->andWhere('LOWER(TRIM(song.title)) = :title')
            ->setParameter('album', $album)
            ->setParameter('title', mb_strtolower($title))
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findTagByNormalizedName(string $name): ?Tag
    {
        return $this->entityManager->getRepository(Tag::class)
            ->createQueryBuilder('tag')
            ->andWhere('LOWER(TRIM(tag.name)) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
