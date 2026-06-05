<?php

namespace App\Controller;

use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function index(Request $request, SongExcerptRepository $songExcerptRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->render('search/index.html.twig', [
            'excerpts' => $query !== '' ? $songExcerptRepository->search($query) : [],
            'query' => $query,
        ]);
    }
}
