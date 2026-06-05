<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/albums')]
class AlbumController extends AbstractController
{
    #[Route('/{id}', name: 'album_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Album $album, SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('album/show.html.twig', [
            'album' => $album,
            'excerpts' => $songExcerptRepository->findByAlbum($album),
        ]);
    }
}
