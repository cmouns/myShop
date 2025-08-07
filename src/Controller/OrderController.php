<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session , ProductRepository $productRepo, EntityManagerInterface $em): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        $cart = $session->get('cart', []);
        $cardWithDatas = [];

        foreach ($cart as $id => $quantity) {
                $cardWithDatas[] = [
                    'products' => $productRepo->find($id),
                    'quantity' => $quantity,
                    'form' => $form->createView(),
                ];
            }

        $total = array_sum(array_map(function($item) {
            return $item['products']->getPrice() * $item['quantity'];
        }, $cardWithDatas));

    
        $order = new Order(); // Créer une nouvelle instance de la classe Order
        $form = $this->createForm(OrderType::class, $order); // Créer le formulaire pour l'entité Order
        $form->handleRequest($request); // Gérer la requête pour le formulaire

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(), // Passer la vue du formulaire à la vue Twig
            'total' => $total

        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city/shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();
        return $this->json([
            'status' => 200,
            'content' => $cityShippingPrice
        ]);
    }

}