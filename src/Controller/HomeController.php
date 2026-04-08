<?php

namespace App\Controller;

use App\Core\Controller;
use App\Repository\ProductRepository;

class HomeController extends Controller
{
    public function index(): void
    {
        /**
         * @todo
         * - Filtrer le input avec la methode filter_input
         * */
        $q = filter_input(INPUT_GET, 'q', FILTER_VALIDATE_URL);

        $productRepository = new ProductRepository();
        $products = $productRepository->findAll();

        $this->render('home', [
            'products' => $products,
            'q' => $q
        ]);
    }
}
