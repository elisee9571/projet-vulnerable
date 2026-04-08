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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product = new Product();

            /**
             * @info faille UPLOAD
             * - verifier l'extention
             * - MIME type
             * - renommer le fichier
             * (eviter de stocker dans le dossier public)
             */
            if (!empty($_FILES['picture']['tmp_name'])) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES['picture']['tmp_name']);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                ];

                if (!in_array($mimeType, $allowedTypes)) {
                    throw new \Exception('File type unhautorized', 401);
                }

                $ext = $extensions[$mimeType];
                $filename = uniqid('products_', true) . '.' . $ext;

                $destDir = dirname(__DIR__) . '/../public/uploads/products';
                if (!is_dir($destDir)) mkdir($destDir, 0775, true);

                move_uploaded_file($_FILES['picture']['tmp_name'], $destDir . '/' . $filename);

                $product->setPicture('/uploads/products/' . $filename);
            }

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

            $entityManager = new EntityManager();
            $entityManager->delete($product);

            $this->redirect('/');
        }
    }
}