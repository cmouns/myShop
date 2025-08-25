<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\CardScheme;

final class CartController extends AbstractController
{   

    public function __construct(private readonly ProductRepository $productRepo)
    {
    }
    
    #[Route('/cart', name: 'app_cart' , methods : ['GET'])]
    public function gestioncart(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cardWithDatas = [];

        foreach ($cart as $id => $quantity) {
                $cardWithDatas[] = [
                    'products' => $this->productRepo->find($id),
                    'quantity' => $quantity
                ];
            }
        
        $total = array_sum(array_map(function($item) {
            return $item['products']->getPrice() * $item['quantity'];
        }, $cardWithDatas));
        
        return $this->render('cart/index.html.twig', [
            'item' => $cardWithDatas,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_new' , methods : ['GET'])]
    public function addProductCart(int $id, SessionInterface $session, Product $product): Response //int $id oblige à n'accepter que des entiers
    {
        // Vérifier si le produit existe
        $cart = $session->get('cart', []);
        if (!isset($cart[$id]) && $product->getStock() > 0 || $cart[$id] < $product->getStock()) {
            if(!empty($cart[$id])) {
                $cart[$id]++;
            // Sinon, on l'ajoute avec une quantité de 1 car il n'est pas encore dans le panier
            } else {
                $cart[$id] = 1;
            }
        }else{
            $this->addFlash('danger', 'Le stock est insuffisant pour ajouter plus de ce produit !');
            return $this->redirectToRoute('app_home_page');
        }
        // Si le produit est déjà dans le panier, on incrémente la quantité
        // if ($product->getStock() > $cart[$id]) {
            
        // }
       
        // Enregistrer le panier dans la session
        // On utilise la méthode set pour mettre à jour le panier dans la session
        // Si le panier n'existe pas, on le crée avec un tableau vide
        $session->set('cart', $cart);
        $this->addFlash('success', 'Produit ajouté au panier avec succès !');

        return $this->redirectToRoute('app_cart');
    }
     
    #[Route('/cart/remove/{id}', name: 'app_cart_product_remove' , methods : ['GET'])]
    public function removeProductCart(int $id, SessionInterface $session ): Response //int $id oblige à n'accepter que des entiers
    {
        // Vérifier si le produit existe
        $cart = $session->get('cart', []);

       if(!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        $this->addFlash('danger', 'Produit supprimé du panier avec succès !');

        return $this->redirectToRoute('app_cart');
    }

// 
    // Route pour supprimer le panier entier
    #[Route('/cart/remove/', name: 'app_cart_remove' , methods : ['GET'])]
    public function removeCart(SessionInterface $session ): Response //int $id oblige à n'accepter que des entiers
    {
        // On vide le panier en le remplaçant par un tableau vide
        $session->set('cart',[]);
        $this->addFlash('danger', 'Panier supprimé avec succès !');

        return $this->redirectToRoute('app_cart');
    }
}
