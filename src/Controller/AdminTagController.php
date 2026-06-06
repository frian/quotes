<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tags')]
#[IsGranted('ROLE_ADMIN')]
class AdminTagController extends AbstractController
{
    #[Route('/{id}/edit', name: 'admin_tag_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Tag $tag,
        Request $request,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
    ): Response {
        $form = $this->createForm(TagType::class, [
            'name' => $tag->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{name: string} $data */
            $data = $form->getData();
            $name = $this->normalizeTagName($data['name']);

            $existingTag = $this->findTagByNormalizedName($tagRepository, $name);

            if ($existingTag instanceof Tag && $existingTag->getId() !== $tag->getId()) {
                $form->get('name')->addError(new FormError('Ce tag existe déjà, choisis plutôt la fiche existante.'));
            } else {
                $tag->setName($name);
                $entityManager->flush();

                $this->addFlash('success', 'Le tag a été mis à jour.');

                return $this->redirectToRoute('tag_show', ['id' => $tag->getId()]);
            }
        }

        return $this->render('admin/tag/edit.html.twig', [
            'form' => $form,
            'tag' => $tag,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_tag_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Tag $tag, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_tag_'.$tag->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($tag);
        $entityManager->flush();

        $this->addFlash('success', 'Le tag a été supprimé.');

        return $this->redirectToRoute('tag_index');
    }

    private function normalizeTagName(string $name): string
    {
        $trimmed = trim($name);

        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }

    private function findTagByNormalizedName(TagRepository $tagRepository, string $name): ?Tag
    {
        return $tagRepository->createQueryBuilder('tag')
            ->andWhere('LOWER(TRIM(tag.name)) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
