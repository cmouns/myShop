<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class Cart
{
    public function __construct(private ProductRepository $productRepository)
    {
    }

    public function getCart(SessionInterface $session): array
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $errors = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $stock = $product->getStock();
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'stock' => $stock
                ];

                if ($quantity > $stock) {
                    $errors[] = [
                        'name' => $product->getName(),
                        'max' => $stock
                    ];
                }
            }
        }

        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        return [
            'cart' => $cartWithData,
            'total' => $total,
            'errors' => $errors
        ];
    }
}