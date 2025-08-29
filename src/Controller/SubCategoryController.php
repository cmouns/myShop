<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Form\SubCategoryType;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class SubCategoryController extends AbstractController
{
    #[Route('/sub/category', name: 'app_sub_category_index', methods: ['GET'])]
    public function index(SubCategoryRepository $subCategoryRepository): Response
    {
        return $this->render('sub_category/index.html.twig', [
            'sub_categories' => $subCategoryRepository->findAll(),
        ]);
    }

    #[Route('/sub/category/new', name: 'app_sub_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $subCategory = new SubCategory();
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique du slug
            $subCategory->setSlug(strtolower($slugger->slug($subCategory->getName())));

            $em->persist($subCategory);
            $em->flush();

            return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sub_category/new.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Affichage friendly URL : /categorie-slug/sous-categorie-slug
    #[Route('/catalogue/{category_slug}/{sub_category_slug}', name: 'app_sub_category_show', methods: ['GET'])]
    public function show(SubCategoryRepository $repo, string $sub_category_slug): Response
    {
        $subCategory = $repo->findOneBy(['slug' => $sub_category_slug]);

        if (!$subCategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        return $this->render('sub_category/show.html.twig', [
            'sub_category' => $subCategory,
        ]);
    }

    // Edition friendly URL : /categorie-slug/sous-categorie-slug/edit
    #[Route('/{category_slug}/{sub_category_slug}/edit', name: 'app_sub_category_edit', methods: ['GET', 'POST'])]
    public function edit(SubCategoryRepository $repo, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, string $sub_category_slug): Response
    {
        $subCategory = $repo->findOneBy(['slug' => $sub_category_slug]);
        if (!$subCategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour le slug si le nom change
            $subCategory->setSlug(strtolower($slugger->slug($subCategory->getName())));

            $em->flush();

            return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sub_category/edit.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Suppression friendly URL : /categorie-slug/sous-categorie-slug/delete
    #[Route('/{category_slug}/{sub_category_slug}/delete', name: 'app_sub_category_delete', methods: ['POST'])]
    public function delete(Request $request, SubCategoryRepository $repo, string $sub_category_slug, EntityManagerInterface $em): Response
    {
        $subCategory = $repo->findOneBy(['slug' => $sub_category_slug]);

        if (!$subCategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        if ($this->isCsrfTokenValid('delete'.$subCategory->getId(), $request->request->get('_token'))) {
            $em->remove($subCategory);
            $em->flush();
        }

        return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
