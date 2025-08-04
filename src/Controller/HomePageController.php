<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(ProductRepository $repo): Response
    {
        return $this->render('home_page/index.html.twig', [
            'products' => $repo->findAll(),
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
}


