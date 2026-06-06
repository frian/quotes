<?php

namespace App\Tests\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminExcerptCrudTest extends AbstractControllerWebTestCase
{
    protected static function seedMode(): string
    {
        return 'empty';
    }

    public function testAnonymousVisitorIsRedirectedAwayFromAdminExcerptForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/excerpts/new');

        self::assertResponseRedirects('http://localhost/login');
    }

    public function testAdminCanCreateExcerptWithNewCatalogEntries(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $artistName = 'Crud Artist '.uniqid('', true);
        $albumTitle = 'Crud Album '.uniqid('', true);
        $songTitle = 'Crud Song '.uniqid('', true);
        $tagName = 'crud-tag-'.uniqid();

        $crawler = $client->request('GET', '/admin/excerpts/new');
        $form = $crawler->selectButton('Ajouter l’extrait')->form([
            'song_excerpt[newArtistName]' => $artistName,
            'song_excerpt[newAlbumTitle]' => $albumTitle,
            'song_excerpt[releaseYear]' => '2026',
            'song_excerpt[songTitle]' => $songTitle,
            'song_excerpt[songSourceUrl]' => 'https://example.com/crud-song',
            'song_excerpt[body]' => 'A created excerpt body',
            'song_excerpt[position]' => '2',
            'song_excerpt[newTagNames]' => $tagName,
            'song_excerpt[note]' => 'A created personal note',
        ]);

        $client->submit($form);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.excerpt-body', 'A created excerpt body');
        self::assertSelectorTextContains('.personal-note', 'A created personal note');
        self::assertSelectorTextContains('.tag-list', $tagName);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $artist = $entityManager->getRepository(Artist::class)->findOneBy(['name' => $artistName]);
        self::assertInstanceOf(Artist::class, $artist);

        $album = $entityManager->getRepository(Album::class)->findOneBy([
            'artist' => $artist,
            'title' => $albumTitle,
            'releaseYear' => 2026,
        ]);
        self::assertInstanceOf(Album::class, $album);

        $song = $entityManager->getRepository(Song::class)->findOneBy([
            'album' => $album,
            'title' => $songTitle,
        ]);
        self::assertInstanceOf(Song::class, $song);
        self::assertSame('https://example.com/crud-song', $song->getSourceUrl());
        self::assertInstanceOf(Tag::class, $entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]));
    }

    public function testAdminCanEditExcerpt(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);
        $excerpt = $this->createExcerptFixture('Edit');

        $crawler = $client->request('GET', sprintf('/admin/excerpts/%d/edit', $excerpt->getId()));
        $form = $crawler->selectButton('Mettre à jour l’extrait')->form([
            'song_excerpt[body]' => 'Updated excerpt body',
            'song_excerpt[note]' => 'Updated personal note',
            'song_excerpt[position]' => '7',
            'song_excerpt[songSourceUrl]' => 'https://example.com/updated-song',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/excerpts/'.$excerpt->getId());
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.excerpt-body', 'Updated excerpt body');
        self::assertSelectorTextContains('.personal-note', 'Updated personal note');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $updatedExcerpt = $entityManager->getRepository(SongExcerpt::class)->find($excerpt->getId());
        self::assertInstanceOf(SongExcerpt::class, $updatedExcerpt);
        self::assertSame(7, $updatedExcerpt->getPosition());
        self::assertSame('https://example.com/updated-song', $updatedExcerpt->getSong()?->getSourceUrl());
        self::assertInstanceOf(\DateTimeImmutable::class, $updatedExcerpt->getUpdatedAt());
    }

    public function testAdminCanDeleteExcerpt(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);
        $excerpt = $this->createExcerptFixture('Delete');
        $excerptId = $excerpt->getId();

        $crawler = $client->request('GET', sprintf('/admin/excerpts/%d/edit', $excerptId));
        $form = $crawler->selectButton('Supprimer l’extrait')->form();

        $client->submit($form);

        self::assertResponseRedirects('/excerpts');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertNull($entityManager->getRepository(SongExcerpt::class)->find($excerptId));
    }

    private function loginAsAdmin(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Entrer')->form([
            '_username' => 'admin',
            '_password' => 'change-me',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
    }

    private function createExcerptFixture(string $prefix): SongExcerpt
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $artist = (new Artist())->setName($prefix.' Artist '.uniqid('', true));
        $album = (new Album())
            ->setArtist($artist)
            ->setTitle($prefix.' Album '.uniqid('', true))
            ->setReleaseYear(2026);
        $song = (new Song())
            ->setAlbum($album)
            ->setTitle($prefix.' Song '.uniqid('', true))
            ->setSourceUrl('https://example.com/original-song');
        $excerpt = (new SongExcerpt())
            ->setSong($song)
            ->setBody($prefix.' excerpt body')
            ->setPosition(1)
            ->setNote($prefix.' personal note');

        $entityManager->persist($artist);
        $entityManager->persist($album);
        $entityManager->persist($song);
        $entityManager->persist($excerpt);
        $entityManager->flush();

        return $excerpt;
    }
}
