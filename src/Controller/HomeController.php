<?php

namespace App\Controller;

use App\Repository\SongExcerptRepository;
use App\Repository\AlbumRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        SongExcerptRepository $songExcerptRepository,
        TagRepository $tagRepository,
        AlbumRepository $albumRepository,
    ): Response
    {
        $latestExcerpts = $songExcerptRepository->findLatest(5);
        $featuredExcerpt = array_shift($latestExcerpts);

        return $this->render('home/index.html.twig', [
            'featuredExcerpt' => $featuredExcerpt,
            'recentExcerpts' => $latestExcerpts,
            'popularTags' => $tagRepository->findMostUsed(8),
            'recentYears' => array_slice($albumRepository->findReleaseYears(), 0, 8),
        ]);
    }

    #[Route('/random', name: 'excerpt_random', methods: ['GET'])]
    public function random(SongExcerptRepository $songExcerptRepository): RedirectResponse
    {
        $excerpt = $songExcerptRepository->findRandom();

        if ($excerpt === null) {
            $this->addFlash('success', 'Aucun extrait à ouvrir pour le moment.');

            return $this->redirectToRoute('home');
        }

        return $this->redirectToRoute('excerpt_show', ['id' => $excerpt->getId()]);
    }
}
