<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use App\Form\SongExcerptType;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/excerpts')]
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

            if ($this->validateSelectionData($form, $data)) {
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

            if ($this->validateSelectionData($form, $data)) {
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
        $album = $this->resolveAlbum($entityManager, $data);
        $song = $this->findOrCreateSong($entityManager, $album, trim((string) $data['songTitle']));

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
    private function validateSelectionData(FormInterface $form, array $data): bool
    {
        $hasExistingAlbum = ($data['album'] ?? null) instanceof Album;
        $newAlbumTitle = trim((string) ($data['newAlbumTitle'] ?? ''));
        $hasNewAlbum = $newAlbumTitle !== '';

        if (!$hasExistingAlbum && !$hasNewAlbum) {
            $form->get('album')->addError(new FormError('Choisis un album existant ou renseigne un autre album.'));
        }

        if ($hasNewAlbum) {
            $hasExistingArtist = ($data['artist'] ?? null) instanceof Artist;
            $hasNewArtist = trim((string) ($data['newArtistName'] ?? '')) !== '';

            if (!$hasExistingArtist && !$hasNewArtist) {
                $form->get('artist')->addError(new FormError('Choisis un artiste existant ou renseigne un autre artiste.'));
            }

            if ($data['releaseYear'] === null) {
                $form->get('releaseYear')->addError(new FormError('Renseigne l’année du nouvel album.'));
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
        $artist = $entityManager->getRepository(Artist::class)->findOneBy(['name' => $name]);

        if ($artist instanceof Artist) {
            return $artist;
        }

        $artist = (new Artist())->setName($name);
        $entityManager->persist($artist);

        return $artist;
    }

    private function findOrCreateAlbum(EntityManagerInterface $entityManager, Artist $artist, string $title, int $releaseYear): Album
    {
        $album = $entityManager->getRepository(Album::class)->findOneBy([
            'artist' => $artist,
            'title' => $title,
            'releaseYear' => $releaseYear,
        ]);

        if ($album instanceof Album) {
            return $album;
        }

        $album = (new Album())
            ->setArtist($artist)
            ->setTitle($title)
            ->setReleaseYear($releaseYear);

        $entityManager->persist($album);

        return $album;
    }

    private function findOrCreateSong(EntityManagerInterface $entityManager, Album $album, string $title): Song
    {
        $song = $entityManager->getRepository(Song::class)->findOneBy([
            'album' => $album,
            'title' => $title,
        ]);

        if ($song instanceof Song) {
            return $song;
        }

        $song = (new Song())
            ->setAlbum($album)
            ->setTitle($title);

        $entityManager->persist($song);

        return $song;
    }

    private function findOrCreateTag(EntityManagerInterface $entityManager, string $name): Tag
    {
        $tag = $entityManager->getRepository(Tag::class)->findOneBy(['name' => $name]);

        if ($tag instanceof Tag) {
            return $tag;
        }

        $tag = (new Tag())->setName($name);
        $entityManager->persist($tag);

        return $tag;
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
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }
}
