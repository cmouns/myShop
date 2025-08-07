<?php
namespace App\Services;

use App\Repository\ProductRepository;

    

class Cart{
    public function __construct(private readonly ProductRepository $productRepo){
    }


}
