<?php

namespace App\Controller\User;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function index(CategoryRepository $categoryRepository,ProductRepository $productRepository): Response
    {

        $products = $productRepository->findAll();
        $categories = $categoryRepository->findAll();
        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
