<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/artists')]
class ArtistController extends AbstractController
{
    #[Route('', name: 'artist_index')]
    public function index(ArtistRepository $artistRepository): Response
    {
        return $this->render('artist/index.html.twig', [
            'artists' => $artistRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/{id}', name: 'artist_show', requirements: ['id' => '\d+'])]
    public function show(Artist $artist, SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
            'excerpts' => $songExcerptRepository->findByArtist($artist),
        ]);
    }
}
