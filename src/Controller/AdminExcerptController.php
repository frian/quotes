<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use App\Form\SongExcerptType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/excerpts')]
class AdminExcerptController extends AbstractController
{
    #[Route('/new', name: 'admin_excerpt_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SongExcerptType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            $excerpt = new SongExcerpt();
            $this->applyFormData($entityManager, $excerpt, $data);
            $entityManager->persist($excerpt);
            $entityManager->flush();

            $this->addFlash('success', 'L’extrait a été ajouté au carnet.');

            return $this->redirectToRoute('excerpt_show', ['id' => $excerpt->getId()]);
        }

        return $this->render('admin/excerpt/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_excerpt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(SongExcerpt $excerpt, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SongExcerptType::class, $this->createFormData($excerpt), [
            'submit_label' => 'Mettre à jour l’extrait',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            $this->applyFormData($entityManager, $excerpt, $data);
            $excerpt->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'L’extrait a été mis à jour.');

            return $this->redirectToRoute('excerpt_show', ['id' => $excerpt->getId()]);
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

        $this->addFlash('success', 'L’extrait a été supprimé.');

        return $this->redirectToRoute('excerpt_index');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyFormData(EntityManagerInterface $entityManager, SongExcerpt $excerpt, array $data): void
    {
        $artist = $this->findOrCreateArtist($entityManager, trim((string) $data['artistName']));
        $album = $this->findOrCreateAlbum($entityManager, $artist, trim((string) $data['albumTitle']), (int) $data['releaseYear']);
        $song = $this->findOrCreateSong($entityManager, $album, trim((string) $data['songTitle']));

        $excerpt
            ->setSong($song)
            ->setBody(trim((string) $data['body']))
            ->setPosition($data['position'] !== null ? (int) $data['position'] : null)
            ->setNote($this->normalizeOptionalText($data['note'] ?? null));

        foreach ($excerpt->getTags()->toArray() as $tag) {
            $excerpt->removeTag($tag);
        }

        foreach ($this->parseTagNames((string) ($data['tagNames'] ?? '')) as $tagName) {
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
            'artistName' => $artist?->getName(),
            'albumTitle' => $album?->getTitle(),
            'releaseYear' => $album?->getReleaseYear(),
            'songTitle' => $song?->getTitle(),
            'body' => $excerpt->getBody(),
            'position' => $excerpt->getPosition(),
            'tagNames' => implode(', ', $excerpt->getTags()->map(static fn (Tag $tag): string => $tag->getName() ?? '')->toArray()),
            'note' => $excerpt->getNote(),
        ];
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
