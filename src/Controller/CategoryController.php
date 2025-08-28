<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoryController extends AbstractController
{
    #[Route('/admin/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        return $this->render('category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/admin/category/add', name: 'app_category_add')]
    public function addCategory(EntityManagerInterface $em, Request $request, SluggerInterface $slugger): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug automatiquement
            $category->setSlug(strtolower($slugger->slug($category->getName())));
            
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Ajout de votre catégorie réussi !');
            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/addCategory.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/category/{slug}/update/{id}', name: 'app_category_update')]
    public function update(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, Category $category): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour le slug si le nom change
            $category->setSlug(strtolower($slugger->slug($category->getName())));
            
            $em->persist($category);
            $em->flush();

            $this->addFlash('info', 'Catégorie mise à jour avec succès.');
            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/editCategory.html.twig', [
            'form' => $form->createView(),
            'update' => $category,
        ]);
    }

    #[Route('/admin/category/{slug}/delete/{id}', name: 'app_category_delete')]
    public function delete(EntityManagerInterface $em, Category $category): Response
    {
        $em->remove($category);
        $em->flush();
        $this->addFlash('danger', 'Votre catégorie a bien été supprimée !');

        return $this->redirectToRoute('app_category');
    }

    #[Route('/category/{slug}', name: 'app_category_show')]
    public function show(Category $category): Response
    {
        // Le ParamConverter récupère automatiquement la catégorie via le slug
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }
}
