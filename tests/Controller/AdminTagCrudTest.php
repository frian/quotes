<?php

namespace App\Tests\Controller;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Song;
use App\Entity\SongExcerpt;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminTagCrudTest extends AbstractControllerWebTestCase
{
    protected static function seedMode(): string
    {
        return 'empty';
    }

    public function testAnonymousVisitorIsRedirectedAwayFromAdminTagEdit(): void
    {
        $client = static::createClient();
        $tag = $this->createTagFixture('Public Redirect Tag');
        $client->request('GET', sprintf('/admin/tags/%d/edit', $tag->getId()));

        self::assertResponseRedirects('http://localhost/login');
    }

    public function testAdminCanEditTagName(): void
    {
        $client = static::createClient();
        $tag = $this->createTagFixture('Original Tag Name');
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', sprintf('/admin/tags/%d/edit', $tag->getId()));
        $form = $crawler->selectButton('Mettre à jour le tag')->form([
            'tag[name]' => '  Updated   Tag Name  ',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/tags/'.$tag->getId());
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.detail-intro__heading', 'Updated Tag Name');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $updatedTag = $entityManager->getRepository(Tag::class)->find($tag->getId());
        self::assertInstanceOf(Tag::class, $updatedTag);
        self::assertSame('Updated Tag Name', $updatedTag->getName());
    }

    public function testAdminCanDeleteTagUsedByExcerpt(): void
    {
        $client = static::createClient();
        $tag = $this->createTagFixture('Delete Me Tag', true);
        $tagId = $tag->getId();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', sprintf('/admin/tags/%d/edit', $tagId));
        $form = $crawler->selectButton('Supprimer le tag')->form();

        $client->submit($form);

        self::assertResponseRedirects('/tags');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        self::assertNull($entityManager->getRepository(Tag::class)->find($tagId));
        self::assertSame(1, $entityManager->getRepository(SongExcerpt::class)->count([]));
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

    private function createTagFixture(string $name, bool $attachToExcerpt = false): Tag
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $tag = (new Tag())->setName($name);
        $entityManager->persist($tag);

        if ($attachToExcerpt) {
            $artist = (new Artist())->setName('Tag Fixture Artist '.uniqid('', true));
            $album = (new Album())
                ->setArtist($artist)
                ->setTitle('Tag Fixture Album '.uniqid('', true))
                ->setReleaseYear(2026);
            $song = (new Song())
                ->setAlbum($album)
                ->setTitle('Tag Fixture Song '.uniqid('', true));
            $excerpt = (new SongExcerpt())
                ->setSong($song)
                ->setBody('Excerpt linked to tag fixture');
            $excerpt->addTag($tag);

            $entityManager->persist($artist);
            $entityManager->persist($album);
            $entityManager->persist($song);
            $entityManager->persist($excerpt);
        }

        $entityManager->flush();

        return $tag;
    }
}
