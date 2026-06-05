<?php

namespace App\Controller;

use App\Entity\SongExcerpt;
use App\Repository\SongExcerptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/excerpts')]
class ExcerptController extends AbstractController
{
    #[Route('', name: 'excerpt_index')]
    public function index(SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('excerpt/index.html.twig', [
            'excerpts' => $songExcerptRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{id}', name: 'excerpt_show', requirements: ['id' => '\d+'])]
    public function show(SongExcerpt $excerpt): Response
    {
        return $this->render('excerpt/show.html.twig', [
            'excerpt' => $excerpt,
        ]);
    }
}
