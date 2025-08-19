<?php
namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment{
    private $redirectUrl;

    public function __construct()
    {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        Stripe::setApiVersion('2025-07-30.basil'); // Assurez-vous que la version est correcte
    }

    public function startPayment($cart, $shippingCost){


        $cartProducts = $cart['cart'];
        $products = [
            [
                'qte' => 1,
                'name' => 'Frais de livraison',
                'price' => $shippingCost
            ]
        ];

        foreach ($cartProducts as $value) {
            // Initialisation d'un tableau vide pour stocker les informations d'un produit
            $productItem = [];
            // Récupération du nom du produit
            $productItem['name'] = $value['product']->getName();
            // Récupération du prix du produit
            $productItem['price'] = $value['product']->getPrice();
            // Récupération de la quantité du produit
            $productItem['qte'] = $value['quantity'];
            // Ajout du produit formaté au tableau des produits
            $products[] = $productItem;
        }

        $session = Session::create([
            'line_items'=>[  //produits qui vont etre payer
                array_map(fn(array $product) => [
                    'quantity' => $product['qte'],
                    'price_data' => [
                        'currency' => 'Eur',
                        'product_data' => [
                           'name' => $product['name']
                        ],
                        'unit_amount' => $product['price']*100, //prix donnée en centimes donc on multiplie
                    ],
                ],$products)
            ],
            'mode' => 'payment', // Mode de paiement
        ]);

        $this->redirectUrl = $session->url; //redirection vers stripe pour le paiement

    }
     public function getStripeRedirectUrl(){ //permet de recuperer l'url de l'utilisateur pour stripe
        return $this->redirectUrl;
    }
};
