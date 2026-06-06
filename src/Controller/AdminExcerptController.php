<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use App\Form\ExcerptImportType;
use App\Form\SongExcerptType;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TagRepository;
use App\Service\ExcerptCsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/excerpts')]
#[IsGranted('ROLE_ADMIN')]
class AdminExcerptController extends AbstractController
{
    #[Route('/new', name: 'admin_excerpt_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        TagRepository $tagRepository,
    ): Response
    {
        $form = $this->createForm(SongExcerptType::class, null, $this->createFormOptions(
            $artistRepository,
            $albumRepository,
            $tagRepository,
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            if ($this->validateSelectionData($form, $entityManager, $data)) {
                $excerpt = new SongExcerpt();
                $this->applyFormData($entityManager, $excerpt, $data);
                $entityManager->persist($excerpt);
                $entityManager->flush();

                $this->addFlash('success', 'L’extrait a été ajouté au carnet.');

                return $this->redirectToRoute('excerpt_show', ['id' => $excerpt->getId()]);
            }
        }

        return $this->render('admin/excerpt/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/import', name: 'admin_excerpt_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        ExcerptCsvImporter $excerptCsvImporter,
    ): Response
    {
        $form = $this->createForm(ExcerptImportType::class);
        $form->handleRequest($request);

        $summary = null;
        $status = null;
        $showConfirm = false;

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            $content = (string) ($data['content'] ?? '');

            try {
                if ($form->get('confirm')->isClicked()) {
                    $summary = $excerptCsvImporter->import($content);

                    if ($summary['errors'] === [] && $summary['imported'] > 0) {
                        $this->addFlash(
                            'success',
                            sprintf(
                                '%d extrait%s importé%s depuis le CSV.',
                                $summary['imported'],
                                $summary['imported'] > 1 ? 's' : '',
                                $summary['imported'] > 1 ? 's' : '',
                            )
                        );

                        return $this->redirectToRoute('excerpt_index');
                    }

                    if ($summary['imported'] > 0) {
                        $showConfirm = true;
                        $status = [
                            'type' => 'warning',
                            'message' => sprintf(
                                '%d extrait%s importé%s. Certaines lignes demandent une correction.',
                                $summary['imported'],
                                $summary['imported'] > 1 ? 's' : '',
                                $summary['imported'] > 1 ? 's' : '',
                            ),
                        ];
                    } elseif ($summary['errors'] === []) {
                        $status = [
                            'type' => 'error',
                            'message' => 'Aucune ligne valide n’a pu être importée.',
                        ];
                    } else {
                        $showConfirm = true;
                        $status = [
                            'type' => 'error',
                            'message' => 'Certaines lignes n’ont pas pu être importées.',
                        ];
                    }
                } else {
                    $summary = $excerptCsvImporter->preview($content);
                    $showConfirm = $summary['ready'] > 0;

                    if ($summary['errors'] === [] && $summary['ready'] > 0) {
                        $status = [
                            'type' => 'success',
                            'message' => sprintf(
                                '%d ligne%s prête%s à être importée%s.',
                                $summary['ready'],
                                $summary['ready'] > 1 ? 's' : '',
                                $summary['ready'] > 1 ? 's' : '',
                                $summary['ready'] > 1 ? 's' : '',
                            ),
                        ];
                    } elseif ($summary['ready'] > 0) {
                        $status = [
                            'type' => 'warning',
                            'message' => sprintf(
                                '%d ligne%s prête%s. Certaines lignes demandent une correction avant import.',
                                $summary['ready'],
                                $summary['ready'] > 1 ? 's' : '',
                                $summary['ready'] > 1 ? 's' : '',
                            ),
                        ];
                    } else {
                        $status = [
                            'type' => 'error',
                            'message' => 'Aucune ligne valide n’a été détectée dans la prévisualisation.',
                        ];
                    }
                }
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));
                $status = [
                    'type' => 'error',
                    'message' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $prefix = $form->get('confirm')->isClicked() ? 'Import impossible : ' : 'Prévisualisation impossible : ';
                $form->addError(new FormError($prefix.$exception->getMessage()));
                $status = [
                    'type' => 'error',
                    'message' => $prefix.$exception->getMessage(),
                ];
            }
        }

        return $this->render('admin/excerpt/import.html.twig', [
            'form' => $form,
            'form_show_confirm' => $showConfirm,
            'summary' => $summary,
            'status' => $status,
        ]);
    }
    #[Route('/{id}/edit', name: 'admin_excerpt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        SongExcerpt $excerpt,
        Request $request,
        EntityManagerInterface $entityManager,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        TagRepository $tagRepository,
    ): Response
    {
        $form = $this->createForm(SongExcerptType::class, $this->createFormData($excerpt), $this->createFormOptions(
            $artistRepository,
            $albumRepository,
            $tagRepository,
            [
            'submit_label' => 'Mettre à jour l’extrait',
            ],
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            if ($this->validateSelectionData($form, $entityManager, $data)) {
                $this->applyFormData($entityManager, $excerpt, $data);
                $excerpt->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->flush();
                $this->cleanupUnusedCatalogEntities($entityManager);

                $this->addFlash('success', 'L’extrait a été mis à jour.');

                return $this->redirectToRoute('excerpt_show', ['id' => $excerpt->getId()]);
            }
        }

        return $this->render('admin/excerpt/edit.html.twig', [
            'excerpt' => $excerpt,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_excerpt_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(SongExcerpt $excerpt, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_excerpt_'.$excerpt->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($excerpt);
        $entityManager->flush();
        $this->cleanupUnusedCatalogEntities($entityManager);

        $this->addFlash('success', 'L’extrait a été supprimé.');

        return $this->redirectToRoute('excerpt_index');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyFormData(EntityManagerInterface $entityManager, SongExcerpt $excerpt, array $data): void
    {
        $currentSong = $excerpt->getSong();
        $album = $this->resolveAlbum($entityManager, $data);
        $song = $this->findOrCreateSong($entityManager, $album, trim((string) $data['songTitle']));
        $this->applySongSourceUrl($song, $this->normalizeOptionalText($data['songSourceUrl'] ?? null), $currentSong === $song);

        $excerpt
            ->setSong($song)
            ->setBody(trim((string) $data['body']))
            ->setPosition($data['position'] !== null ? (int) $data['position'] : null)
            ->setNote($this->normalizeOptionalText($data['note'] ?? null));

        foreach ($excerpt->getTags()->toArray() as $tag) {
            $excerpt->removeTag($tag);
        }

        foreach ($data['tags'] ?? [] as $tag) {
            if ($tag instanceof Tag) {
                $excerpt->addTag($tag);
            }
        }

        foreach ($this->parseTagNames((string) ($data['newTagNames'] ?? '')) as $tagName) {
            $excerpt->addTag($this->findOrCreateTag($entityManager, $tagName));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createFormData(SongExcerpt $excerpt): array
    {
        $song = $excerpt->getSong();
        $album = $song?->getAlbum();
        $artist = $album?->getArtist();

        return [
            'artist' => $artist,
            'newArtistName' => null,
            'album' => $album,
            'newAlbumTitle' => null,
            'releaseYear' => $album?->getReleaseYear(),
            'songTitle' => $song?->getTitle(),
            'songSourceUrl' => $song?->getSourceUrl(),
            'body' => $excerpt->getBody(),
            'position' => $excerpt->getPosition(),
            'tags' => $excerpt->getTags()->toArray(),
            'newTagNames' => null,
            'note' => $excerpt->getNote(),
        ];
    }

    /**
     * @param array<string, mixed> $extraOptions
     *
     * @return array<string, mixed>
     */
    private function createFormOptions(
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        TagRepository $tagRepository,
        array $extraOptions = [],
    ): array {
        return array_replace([
            'albums' => $albumRepository->findAllOrderedByArtistAndTitle(),
            'artists' => $artistRepository->findAllOrderedByName(),
            'tags' => $tagRepository->findAllOrderedByName(),
        ], $extraOptions);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateSelectionData(FormInterface $form, EntityManagerInterface $entityManager, array $data): bool
    {
        $hasExistingAlbum = ($data['album'] ?? null) instanceof Album;
        $newAlbumTitle = $this->normalizeCatalogText($data['newAlbumTitle'] ?? null);
        $hasNewAlbum = $newAlbumTitle !== '';
        $existingAlbum = null;

        if ($hasExistingAlbum && $hasNewAlbum) {
            $form->get('album')->addError(new FormError('Choisis un album existant ou renseigne un nouvel album, pas les deux.'));
        }

        if (!$hasExistingAlbum && !$hasNewAlbum) {
            $form->get('album')->addError(new FormError('Choisis un album existant ou renseigne un autre album.'));
        }

        if ($hasNewAlbum) {
            $hasExistingArtist = ($data['artist'] ?? null) instanceof Artist;
            $newArtistName = $this->normalizeCatalogText($data['newArtistName'] ?? null);
            $hasNewArtist = $newArtistName !== '';

            if ($hasExistingArtist && $hasNewArtist) {
                $form->get('newArtistName')->addError(new FormError('Choisis un artiste existant ou saisis un nouvel artiste, pas les deux.'));
            }

            if (!$hasExistingArtist && !$hasNewArtist) {
                $form->get('artist')->addError(new FormError('Choisis un artiste existant ou renseigne un autre artiste.'));
            }

            if ($data['releaseYear'] === null) {
                $form->get('releaseYear')->addError(new FormError('Renseigne l’année du nouvel album.'));
            }

            if ($hasNewArtist && $this->findArtistByNormalizedName($entityManager, $newArtistName) instanceof Artist) {
                $form->get('newArtistName')->addError(new FormError('Cet artiste existe déjà, choisis-le dans la liste.'));
            }

            $artist = $hasExistingArtist ? $data['artist'] : null;
            if ($artist instanceof Artist && $data['releaseYear'] !== null) {
                $existingAlbum = $this->findAlbumByNormalizedIdentity(
                    $entityManager,
                    $artist,
                    $newAlbumTitle,
                    (int) $data['releaseYear']
                );
            } elseif ($hasNewArtist && $data['releaseYear'] !== null) {
                $existingArtist = $this->findArtistByNormalizedName($entityManager, $newArtistName);

                if ($existingArtist instanceof Artist) {
                    $existingAlbum = $this->findAlbumByNormalizedIdentity(
                        $entityManager,
                        $existingArtist,
                        $newAlbumTitle,
                        (int) $data['releaseYear']
                    );
                }
            }

            if ($existingAlbum instanceof Album) {
                $form->get('newAlbumTitle')->addError(new FormError('Cet album existe déjà, choisis-le dans la liste.'));
            }
        }

        return $form->isValid();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveAlbum(EntityManagerInterface $entityManager, array $data): Album
    {
        $newAlbumTitle = trim((string) ($data['newAlbumTitle'] ?? ''));

        if ($newAlbumTitle === '' && ($data['album'] ?? null) instanceof Album) {
            return $data['album'];
        }

        $artist = $this->resolveArtist($entityManager, $data);

        return $this->findOrCreateAlbum($entityManager, $artist, $newAlbumTitle, (int) $data['releaseYear']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveArtist(EntityManagerInterface $entityManager, array $data): Artist
    {
        $newArtistName = trim((string) ($data['newArtistName'] ?? ''));

        if ($newArtistName !== '') {
            return $this->findOrCreateArtist($entityManager, $newArtistName);
        }

        /** @var Artist $artist */
        $artist = $data['artist'];

        return $artist;
    }

    private function findOrCreateArtist(EntityManagerInterface $entityManager, string $name): Artist
    {
        $name = $this->normalizeCatalogText($name);
        $artist = $this->findArtistByNormalizedName($entityManager, $name);

        if ($artist instanceof Artist) {
            return $artist;
        }

        $artist = (new Artist())->setName($name);
        $entityManager->persist($artist);
        $entityManager->flush();

        return $artist;
    }

    private function findOrCreateAlbum(EntityManagerInterface $entityManager, Artist $artist, string $title, int $releaseYear): Album
    {
        $title = $this->normalizeCatalogText($title);
        $album = $this->findAlbumByNormalizedIdentity($entityManager, $artist, $title, $releaseYear);

        if ($album instanceof Album) {
            return $album;
        }

        $album = (new Album())
            ->setArtist($artist)
            ->setTitle($title)
            ->setReleaseYear($releaseYear);

        $entityManager->persist($album);
        $entityManager->flush();

        return $album;
    }

    private function findOrCreateSong(EntityManagerInterface $entityManager, Album $album, string $title): Song
    {
        $title = $this->normalizeCatalogText($title);
        $song = $this->findSongByNormalizedIdentity($entityManager, $album, $title);

        if ($song instanceof Song) {
            return $song;
        }

        $song = (new Song())
            ->setAlbum($album)
            ->setTitle($title);

        $entityManager->persist($song);
        $entityManager->flush();

        return $song;
    }

    private function findOrCreateTag(EntityManagerInterface $entityManager, string $name): Tag
    {
        $name = $this->normalizeCatalogText($name);
        $tag = $this->findTagByNormalizedName($entityManager, $name);

        if ($tag instanceof Tag) {
            return $tag;
        }

        $tag = (new Tag())->setName($name);
        $entityManager->persist($tag);
        $entityManager->flush();

        return $tag;
    }

    private function applySongSourceUrl(Song $song, ?string $sourceUrl, bool $isCurrentExcerptSong): void
    {
        if ($sourceUrl !== null || $isCurrentExcerptSong || $song->getId() === null) {
            $song->setSourceUrl($sourceUrl);
        }
    }

    private function cleanupUnusedCatalogEntities(EntityManagerInterface $entityManager): void
    {
        $unusedTags = $entityManager->getRepository(Tag::class)
            ->createQueryBuilder('tag')
            ->leftJoin('tag.excerpts', 'excerpt')
            ->groupBy('tag.id')
            ->having('COUNT(excerpt.id) = 0')
            ->getQuery()
            ->getResult();

        foreach ($unusedTags as $tag) {
            $entityManager->remove($tag);
        }

        $unusedSongs = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('song')
            ->leftJoin('song.excerpts', 'excerpt')
            ->groupBy('song.id')
            ->having('COUNT(excerpt.id) = 0')
            ->getQuery()
            ->getResult();

        foreach ($unusedSongs as $song) {
            $entityManager->remove($song);
        }

        $entityManager->flush();

        $unusedAlbums = $entityManager->getRepository(Album::class)
            ->createQueryBuilder('album')
            ->leftJoin('album.songs', 'song')
            ->groupBy('album.id')
            ->having('COUNT(song.id) = 0')
            ->getQuery()
            ->getResult();

        foreach ($unusedAlbums as $album) {
            $entityManager->remove($album);
        }

        $entityManager->flush();

        $unusedArtists = $entityManager->getRepository(Artist::class)
            ->createQueryBuilder('artist')
            ->leftJoin('artist.albums', 'album')
            ->groupBy('artist.id')
            ->having('COUNT(album.id) = 0')
            ->getQuery()
            ->getResult();

        foreach ($unusedArtists as $artist) {
            $entityManager->remove($artist);
        }

        $entityManager->flush();
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

    private function findArtistByNormalizedName(EntityManagerInterface $entityManager, string $name): ?Artist
    {
        return $entityManager->getRepository(Artist::class)
            ->createQueryBuilder('artist')
            ->andWhere('LOWER(TRIM(artist.name)) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findAlbumByNormalizedIdentity(EntityManagerInterface $entityManager, Artist $artist, string $title, int $releaseYear): ?Album
    {
        return $entityManager->getRepository(Album::class)
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

    private function findSongByNormalizedIdentity(EntityManagerInterface $entityManager, Album $album, string $title): ?Song
    {
        return $entityManager->getRepository(Song::class)
            ->createQueryBuilder('song')
            ->andWhere('song.album = :album')
            ->andWhere('LOWER(TRIM(song.title)) = :title')
            ->setParameter('album', $album)
            ->setParameter('title', mb_strtolower($title))
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findTagByNormalizedName(EntityManagerInterface $entityManager, string $name): ?Tag
    {
        return $entityManager->getRepository(Tag::class)
            ->createQueryBuilder('tag')
            ->andWhere('LOWER(TRIM(tag.name)) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
