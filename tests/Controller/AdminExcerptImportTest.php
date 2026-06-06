<?php

namespace App\Tests\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;

class AdminExcerptImportTest extends AbstractControllerWebTestCase
{
    protected static function seedMode(): string
    {
        return 'empty';
    }

    public function testImportPostCreatesExcerptsAndRedirectsToCatalog(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Entrer')->form([
            '_username' => 'admin',
            '_password' => 'change-me',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/');
        $client->followRedirect();

        $artistName = 'Test Import Artist '.uniqid('', true);
        $albumTitle = 'Test Import Album '.uniqid('', true);
        $songTitle = 'Test Import Song '.uniqid('', true);
        $tagOne = 'test-import-tag-one-'.uniqid();
        $tagTwo = 'test-import-tag-two-'.uniqid();

        $crawler = $client->request('GET', '/admin/excerpts/import');
        $form = $crawler->selectButton('Importer le CSV')->form([
            'excerpt_import[content]' => implode("\n", [
                'artist;album;year;song;body;source_url;tags;note;position',
                sprintf(
                    '%s;%s;2026;%s;"First line of the body',
                    $artistName,
                    $albumTitle,
                    $songTitle
                ),
                sprintf(
                    'Second line of the body";https://example.com/import-test;%s, %s;A note about the excerpt;1',
                    $tagOne,
                    $tagTwo
                ),
            ]),
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/excerpts');

        $client->followRedirect();
        self::assertResponseIsSuccessful();

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
        self::assertSame('https://example.com/import-test', $song->getSourceUrl());

        $excerpt = $entityManager->getRepository(SongExcerpt::class)->findOneBy([
            'song' => $song,
            'position' => 1,
        ]);
        self::assertInstanceOf(SongExcerpt::class, $excerpt);
        self::assertStringContainsString('First line of the body', $excerpt->getBody());
        self::assertStringContainsString('Second line of the body', $excerpt->getBody());
        self::assertSame('A note about the excerpt', $excerpt->getNote());

        $tagRepository = $entityManager->getRepository(Tag::class);
        self::assertInstanceOf(Tag::class, $tagRepository->findOneBy(['name' => $tagOne]));
        self::assertInstanceOf(Tag::class, $tagRepository->findOneBy(['name' => $tagTwo]));
    }
}
