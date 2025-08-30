<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods : ['GET'])]
    public function index(ProductRepository $repo, CategoryRepository $categoryRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $data = $repo->findBy([], ['id' => 'DESC']);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        $categories = $categoryRepo->findAll();
        $categoriesWithData = $this->getCategoriesWithData($categoryRepo);

        $productsWithSlugs = [];
        foreach ($products as $product) {
            $firstSubCategory = $product->getSubCategories()->first();
            $productsWithSlugs[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'image' => $product->getImage(),
                'price' => $product->getPrice(),
                'sub_category_slug' => $firstSubCategory ? $firstSubCategory->getSlug() : null,
            ];
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'productsWithSlugs' => $productsWithSlugs,
            'categories' => $categories,
            'categoriesWithData' => $categoriesWithData,
        ]);
    }

    #[Route(
        '/{sub_category_slug}/{id}/{slug}',
        name: 'app_home_product_show',
        requirements: [
            'sub_category_slug' => '(?!sub|editor|admin|category|new).*',
            'id' => '\d+'
        ]
    )]
    public function showProduct(Product $product, ProductRepository $repo, CategoryRepository $categoryRepo): Response
    {
        $lastProductsAdd = $repo->findBy([], ['id' => 'DESC'], 5);
        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products' => $lastProductsAdd,
            'subCategory' => $product->getSubCategories()->first(),
            'categoriesWithData' => $this->getCategoriesWithData($categoryRepo),
        ]);
    }

    #[Route(
        '/{category_slug}/{sub_category_slug}',
        name: 'app_home_product_filter',
        requirements: [
            'category_slug' => '(?!editor|sub|admin|pay|cart|success).*',
            'sub_category_slug' => '(?!product|category|new|success).*'
        ],
        methods: ['GET']
    )]
    public function filter(
        string $category_slug,
        string $sub_category_slug,
        CategoryRepository $categoryRepo,
        SubCategoryRepository $subCategoryRepo
    ): Response {
        $category = $categoryRepo->findOneBy(['slug' => $category_slug]);
        $subCategory = $subCategoryRepo->findOneBy(['slug' => $sub_category_slug]);

        if (!$category || !$subCategory) {
            throw $this->createNotFoundException('Catégorie ou sous-catégorie introuvable.');
        }

        $products = $subCategory->getProducts();

        return $this->render('home_page/filter.html.twig', [
            'products' => $products,
            'category' => $category,
            'subCategory' => $subCategory,
            'categories' => $categoryRepo->findAll(),
            'categoriesWithData' => $this->getCategoriesWithData($categoryRepo),
        ]);
    }

    #[Route('/product/category/{id}/filter', name: 'app_home_category_filter', methods: ['GET'])]
    public function filterByCategory($id, CategoryRepository $categoryRepo): Response
    {
        $category = $categoryRepo->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

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
            'categoriesWithData' => $this->getCategoriesWithData($categoryRepo),
        ]);
    }

    // Méthode utilitaire à placer en dehors de toute autre méthode
    private function getCategoriesWithData(CategoryRepository $categoryRepo): array
    {
        $categories = $categoryRepo->findAll();
        $categoriesWithData = [];
        foreach ($categories as $category) {
            $subCategoriesWithSlugs = [];
            foreach ($category->getSubCategories() as $subCategory) {
                $subCategoriesWithSlugs[] = [
                    'id' => $subCategory->getId(),
                    'name' => $subCategory->getName(),
                    'slug' => $subCategory->getSlug(),
                ];
            }
            $productForCategory = null;
            foreach ($category->getSubCategories() as $subCategory) {
                $productForCategory = $subCategory->getProducts()->first();
                if ($productForCategory) {
                    break;
                }
            }
            $categoriesWithData[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'subCategories' => $subCategoriesWithSlugs,
                'product' => $productForCategory
                    ? [
                        'id' => $productForCategory->getId(),
                        'name' => $productForCategory->getName(),
                        'slug' => $productForCategory->getSlug(),
                        'image' => $productForCategory->getImage(),
                        'sub_category_slug' => $productForCategory->getSubCategories()->first()
                            ? $productForCategory->getSubCategories()->first()->getSlug()
                            : null,
                    ]
                    : null,
            ];
        }
        return $categoriesWithData;
    }
}
