<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/songs')]
class SongController extends AbstractController
{
    #[Route('/{id}', name: 'song_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Song $song, SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('song/show.html.twig', [
            'song' => $song,
            'excerpts' => $songExcerptRepository->findBySong($song),
        ]);
    }
}
