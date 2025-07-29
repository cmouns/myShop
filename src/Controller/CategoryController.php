<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use Doctrine\ORM\Mapping\Entity;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(CategoryRepository $data,): Response

    {
        $categories = $data->findall();
        return $this->render('category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/category/add', name: 'app_category_add')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response

    {
        $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('notice','Ajout de votre catégorie réussi !!');
            return $this->redirectToRoute('app_category');

        }

        return $this->render('category/addCategory.html.twig',[
            'form' => $form->createView()
        ]); 
    }

 
    #[Route('/category/update/{id}', name: 'app_category_update')]
    public function update(Request $request, EntityManagerInterface $em, Category $update): Response
    {
    $form = $this->createForm(CategoryFormType::class, $update);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($update); 
        $em->flush();            

        $this->addFlash('success', 'Catégorie mise à jour avec succès.');

        return $this->redirectToRoute('app_category');
    }

    return $this->render('category/editCategory.html.twig', [
        'form' => $form->createView(),
        'update' => $update,
    ]);
}

#[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function delete(EntityManagerInterface $em, Category $delete): Response
    {
     
       
            $em->remove($delete);
            $em->flush();
            $this->addFlash('notice','Suppression réussi !!');

        return $this->redirectToRoute('app_category');
    
    }
}  

