<?php

namespace App\Controller\User;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/category', name: 'user.category.')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }
    #[Route('/{id}', name: 'show',requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function show(CategoryRepository $categoryRepository,Category $category): Response
    {
        $category = $categoryRepository->find($category->getId());

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }
}
