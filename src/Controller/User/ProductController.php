<?php

namespace App\Controller\User;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/product', name: 'user.product.')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route('/{id}', name: 'show',requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function show(ProductRepository $productRepository,Product $product): Response
    {
        $product = $productRepository->find($product->getId());

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
