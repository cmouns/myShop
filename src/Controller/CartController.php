<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Cart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepo)
    {
    }

    // Affichage du panier - utilise ton service Cart (qui renvoie ['cart' => ..., 'total' => ...])
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function gestioncart(SessionInterface $session, Cart $cartService): Response
    {
        $cartData = $cartService->getCart($session);

        return $this->render('cart/index.html.twig', [
            'item' => $cartData['cart'] ?? [],
            'total' => $cartData['total'] ?? 0,
            'errors' => $cartData['errors'] ?? [],
        ]);
    }


    // Route pour mettre à jour le panier depuis le formulaire (bouton "Mettre à jour le panier")
    #[Route('/cart/update', name: 'app_cart_update', methods: ['POST'])]
    public function updateCart(SessionInterface $session, Request $request): Response
    {
        // récupère toutes les quantités envoyées par le formulaire
        $quantities = (array) $request->request->all('quantities');

        $cart = $session->get('cart', []);

        foreach ($quantities as $id => $qty) {
            $id = (int) $id;
            $quantity = max(1, (int) $qty); // min = 1
            $cart[$id] = $quantity;
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Panier mis à jour avec succès.');

        return $this->redirectToRoute('app_cart');
    }



    // -- Tes autres méthodes existantes (ajouter / supprimer / vider) --
    #[Route('/cart/add/{id}', name: 'app_cart_new', methods: ['GET'])]
    public function addProductCart(\App\Entity\Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        $currentQty = $cart[$product->getId()] ?? 0;
        if ($product->getStock() > $currentQty) {
            $cart[$product->getId()] = $currentQty + 1;
            $session->set('cart', $cart);
            $this->addFlash('success', 'Produit ajouté au panier avec succès !');
        } else {
            $this->addFlash('danger', 'Le stock est insuffisant pour ajouter plus de ce produit !');
        }

        return $this->redirectToRoute('app_cart');
    }


    #[Route('/cart/remove/{id}', name: 'app_cart_product_remove', methods: ['GET'])]
    public function removeProductCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        $this->addFlash('danger', 'Produit supprimé du panier avec succès !');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/', name: 'app_cart_remove', methods: ['GET'])]
    public function removeCart(SessionInterface $session): Response
    {
        $session->set('cart', []);
        $this->addFlash('danger', 'Panier supprimé avec succès !');

        return $this->redirectToRoute('app_cart');
    }
}
