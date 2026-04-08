<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\EntityManager;
use App\Model\Product;
use App\Repository\ProductRepository;
use App\Service\UtilitiesService;

class ProductController extends Controller
{

    public function new(): void
    {
        /**
         * @todo
         * Ce que vous devez faire :
         * - Validation des inputs (not null, not blank, minLength, maxLength,...) et sanitize les données entrées (parser* le contenu)
         * - filter_var($data, $type) : pour la validation
         * - htmlspecialchars($data) : pour parser les données
         * parser* : formatter
         */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product = new Product();

            if (!empty($_FILES['picture']['tmp_name'])) {
                $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('products_', true) . '.' . $ext;

                $destDir = dirname(__DIR__) . '/../public/uploads/products';
                if (!is_dir($destDir)) mkdir($destDir, 0775, true);

                move_uploaded_file($_FILES['picture']['tmp_name'], $destDir . '/' . $filename);

                $product->setPicture('/uploads/products/' . $filename);
            }

            /**
             * @info faille XSS stocké
             * les données recupéré ne sont pas controllés
             */
            $title = htmlspecialchars($_POST['title']);

            $product->setTitle($title)
                ->setSlug(UtilitiesService::slugify($title))
                ->setDescription(htmlspecialchars($_POST['description']))
                ->setPrice(filter_var($_POST['price'], FILTER_VALIDATE_FLOAT, [
                    'options' => [
                        'min_range' => 0,
                    ],
                    'flags' => FILTER_FLAG_ALLOW_FRACTION
                ]));

            $entityManager = new EntityManager();
            $entityManager->persist($product);
            $entityManager->flush();

            $this->redirect('/');
        }

        $this->render('product/new');
    }

    public function show(string $slug, int $id): void
    {
        $productRepository = new ProductRepository();
        $product = $productRepository->findById($id) ?: $productRepository->findBySlug($slug);

        if (!$product) {
            throw new \Exception('Product not found');
        }

        if ($product->getSlug() !== $slug || $product->getId() !== $id) {
            $this->redirect("/product/{$product->getSlug()}/{$product->getId()}");
        }

        $this->render('product/show', [
            'product' => $product
        ]);
    }
}