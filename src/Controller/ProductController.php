<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Entity\AddProductHistory;
use App\Repository\ProductRepository;
use App\Repository\AddProductHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
            
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug sécurisé
            $name = $product->getName();
            $cleanName = preg_replace('/[\/\\\]+/', '-', $name); // remplace / et \ par -
            $product->setSlug(strtolower($slugger->slug($cleanName)));

            // Gestion de l'image
            $image = $form->get('image')->getData();
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName = $slugger->slug($originalName);
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move($this->getParameter('image_directory'), $newFileImageName);
                } catch (FileException $exception) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image');
                }

                $product->setImage($newFileImageName);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            // Historique du stock
            $stockHistory = new AddProductHistory();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été créé avec succès.');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{slug}/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour du slug si le nom change
            $name = $product->getName();
            $cleanName = preg_replace('/[\/\\\]+/', '-', $name);
            $product->setSlug(strtolower($slugger->slug($cleanName)));

            // Gestion de l'image
            $image = $form->get('image')->getData();
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName = $slugger->slug($originalName);
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move($this->getParameter('image_directory'), $newFileImageName);
                } catch (FileException $exception) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image');
                }

                $product->setImage($newFileImageName);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Le produit a été modifié avec succès.');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('danger', 'Le produit a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/add/{slug}/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function showHistoryProductStock(Product $product, AddProductHistoryRepository $addProductHistoryRepository): Response
    {
        $productAddHistory = $addProductHistoryRepository->findBy(['product' => $product], ['id' => 'DESC']);

        return $this->render('product/addedHistoryStockShow.html.twig', [
            'productsAdded' => $productAddHistory,
        ]);
    }

    
}
