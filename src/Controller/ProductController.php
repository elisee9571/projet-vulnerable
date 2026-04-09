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
            $product->setTitle($_POST['title'])
                ->setSlug(UtilitiesService::slugify($_POST['title']))
                ->setDescription($_POST['description'])
                ->setPrice($_POST['price']);

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
            throw new \Exception('Product not found', 404);
        }

        if ($product->getSlug() !== $slug || $product->getId() !== $id) {
            $this->redirect("/product/{$product->getSlug()}/{$product->getId()}");
        }

        $this->render('product/show', [
            'product' => $product
        ]);
    }

    public function delete(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /**
         * @todo
         * Faire une page html :
         *  - ajouter un form action="/product/id/delete" method="post"
         *  - et regarder ce que ca fait
         *
         * Ce que vous devez faire :
         * Dans votre condition :
         *  - Verifier si le $_POST['csrf_token'] === $_SESSION['csrf_token']
         *  - si c'est vrai alors on supprime
         * - sinon on retourne:
         *  - $_SESSION['error'] = "Invalid CSRF token";
         *  - $this->redirect("/product/{$product->getSlug()}/{$product->getId()}");
         */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productRepository = new ProductRepository();
            $product = $productRepository->findById($id);

            if (!$product) {
                throw new \Exception('Product not found', 404);
            }

            if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
                $entityManager = new EntityManager();
                $entityManager->delete($product);

                $this->redirect('/');
            } else {
                $_SESSION['error'] = "Invalid CSRF token";
                $this->redirect("/product/{$product->getSlug()}/{$product->getId()}");
            }
        }
    }
}