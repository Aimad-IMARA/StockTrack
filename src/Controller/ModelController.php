<?php

namespace App\Controller;

use App\Repository\ModelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ModelController extends AbstractController
{
    #[Route('/model', name: 'app_model')]
    public function index(ModelRepository $modelRepository): Response
    {
        $models = $modelRepository->findAll();
        return $this->render('model/index.html.twig', [
            'models' => $models,
        ]);
    }
}
