<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/years')]
class YearController extends AbstractController
{
    #[Route('', name: 'year_index')]
    public function index(AlbumRepository $albumRepository): Response
    {
        return $this->render('year/index.html.twig', [
            'years' => $albumRepository->findReleaseYears(),
        ]);
    }

    #[Route('/{year}', name: 'year_show', requirements: ['year' => '\d{4}'])]
    public function show(int $year, SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('year/show.html.twig', [
            'year' => $year,
            'excerpts' => $songExcerptRepository->findByReleaseYear($year),
        ]);
    }
}
