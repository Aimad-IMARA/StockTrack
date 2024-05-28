<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    private $pr;
    public function __construct(private readonly ProductRepository $productRepository)
    {
        $this->pr = $productRepository;
    }

    #[Route('user/cart', name: 'user.   cart')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        foreach ($cart as $id=>$quantity) {
            $cartWithData[] = [
                'product' => $this->pr->find($id),
                'quantity' => $quantity
            ];
        }
        return $this->render('cart/index.html.twig', [
            'controller_name' => 'CartController',
        ]);
    }
}
