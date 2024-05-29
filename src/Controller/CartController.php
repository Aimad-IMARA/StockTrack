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

    #[Route('user/cart', name: 'user.cart')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $this->pr->find($id),
                'quantity' => $quantity
            ];
        }
        $total = array_sum(array_map(function ($item) {
            return $item['quantity'] * $item['product']->getPrice();
        }, $cartWithData));
        dd($cart);
        return $this->render('cart/index.html.twig', [
            'items' => $cartWithData,
            'total' => $total
        ]);
    }

    #[Route('/user/cart/add/{id}', name: 'user.cart.add', methods: ['GET'])]
    public function addToCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if(!isset($cart[$id])) {
            $cart[$id] = 1;
        } else {
            $cart[$id]++;
        }
        $session->set('cart', $cart);
        return $this->redirectToRoute('user.cart');
    }
}
