<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
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
        return $this->render('cart/index.html.twig', [
            'items' => $cartWithData,
            'total' => $total
        ]);
    }

    #[Route('/user/cart/add/{id}', name: 'user.cart.add', methods: ['GET'])]
    public function addToCart(int $id, SessionInterface $session, RequestStack $requestStack): Response
    {
        $cart = $session->get('cart', []);
        if (!isset($cart[$id])) {
            $cart[$id] = 1;
        } else {
            $cart[$id]++;
        }
        $session->set('cart', $cart);
        $this->addFlash('success', 'Product added successfully!');
        // Get the referer URL
        $request = $requestStack->getCurrentRequest();
        $referer = $request->headers->get('referer');

        // Redirect to the referer URL if it exists, otherwise to the cart page
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('user.cart');
    }
}
