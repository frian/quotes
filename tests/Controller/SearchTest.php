<?php

namespace App\Tests\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;

class SearchTest extends AbstractControllerWebTestCase
{
    protected static function seedMode(): string
    {
        return 'empty';
    }

    public function testSearchFindsExcerptByBodyAndTag(): void
    {
        $client = static::createClient();
        $this->createSearchFixture();

        $client->request('GET', '/search?q=quietneedle');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.excerpt-body', 'This body keeps the quietneedle visible');
        self::assertSelectorTextContains('.tag-list', 'searchneedle');
    }

    public function testSearchShowsEmptyStateForUnknownTerm(): void
    {
        $client = static::createClient();
        $this->createSearchFixture();

        $client->request('GET', '/search?q=missing-fragment');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty-state', 'Aucun extrait trouvé');
    }

    public function testSearchFindsExcerptByTag(): void
    {
        $client = static::createClient();
        $this->createSearchFixture();

        $client->request('GET', '/search?q=SEARCHNEEDLE');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.excerpt-body', 'This body keeps the quietneedle visible');
        self::assertSelectorTextContains('.tag-list', 'searchneedle');
    }

    private function createSearchFixture(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $artist = (new Artist())->setName('Search Artist');
        $album = (new Album())
            ->setArtist($artist)
            ->setTitle('Search Album')
            ->setReleaseYear(2026);
        $song = (new Song())
            ->setAlbum($album)
            ->setTitle('Search Song');
        $tag = (new Tag())->setName('searchneedle');
        $excerpt = (new SongExcerpt())
            ->setSong($song)
            ->setBody('This body keeps the quietneedle visible')
            ->addTag($tag);

        $entityManager->persist($artist);
        $entityManager->persist($album);
        $entityManager->persist($song);
        $entityManager->persist($tag);
        $entityManager->persist($excerpt);
        $entityManager->flush();
    }
}
