<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods : ['GET'])]
    public function index(ProductRepository $repo, CategoryRepository $categoryRepo): Response
    {
        return $this->render('home_page/index.html.twig', [
            'products' => $repo->findAll(),
            'categories' => $categoryRepo->findAll(),
        ]);
    }

    #[Route('/product/{id}/show', name: 'app_home_product_show')]
    public function showProduct(Product $product,ProductRepository $repo): Response
    {
        $lastProductsAdd = $repo->findBy([],['id'=>'DESC'],5);
        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products'=> $lastProductsAdd
        ]);

        
    }

    #[Route('/product/subcategory/{id}/filter', name: 'app_home_product_filter', methods : ['GET'])]
    public function filter($id,SubCategoryRepository $subCategoryRepo, CategoryRepository $categoryRepo): Response
    {   
        $product = $subCategoryRepo->find($id)->getProducts();

        return $this->render('home_page/filter.html.twig', [
            'products'=> $product,
            'subCategory' => $subCategoryRepo->find($id),
            'categories' => $categoryRepo->findAll(),
            
        ]);
    }

    #[Route('/product/category/{id}/filter', name: 'app_home_category_filter', methods: ['GET'])]
    public function filterByCategory($id, CategoryRepository $categoryRepo): Response
    {
        $category = $categoryRepo->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        // Récupère tous les produits des sous-catégories
        $products = [];
        foreach ($category->getSubCategories() as $subCategory) {
            foreach ($subCategory->getProducts() as $product) {
                $products[] = $product;
            }
        }

        return $this->render('home_page/filter.html.twig', [
            'products' => $products,
            'category' => $category,
            'categories' => $categoryRepo->findAll(),
        ]);
    }
}


