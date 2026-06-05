<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\SongExcerptRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tags')]
class TagController extends AbstractController
{
    #[Route('', name: 'tag_index', methods: ['GET'])]
    public function index(TagRepository $tagRepository): Response
    {
        return $this->render('tag/index.html.twig', [
            'tags' => $tagRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/{id}', name: 'tag_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Tag $tag, SongExcerptRepository $songExcerptRepository): Response
    {
        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
            'excerpts' => $songExcerptRepository->findByTag($tag),
        ]);
    }
}
