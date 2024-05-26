<?php

namespace App\Controller;

use App\Entity\Model;
use App\Entity\Product;
use App\Form\ModelType;
use App\Form\ProductType;
use App\Repository\ModelRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('user/product', name: 'user.product.index')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route('user/product/{id}', name: 'user.product.show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function show(ProductRepository $productRepository, Product $product): Response
    {
        $product = $productRepository->find($product->getId());
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('admin/product/data', name: 'admin.product.data')]
    public function getData(ProductRepository $productRepository, Request $request): Response
    {
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start'));
        $length = intval($request->get('length'));
        $search = $request->get('search')['value'];

        $orderColumnIndex = intval($request->get('order')[0]['column']);
        $orderDirection = $request->get('order')[0]['dir'];
        $columns = $request->get('columns');
        $orderColumn = $columns[$orderColumnIndex]['data'];

        $query = $productRepository->createQueryBuilder('p')
            ->setFirstResult($start)
            ->setMaxResults($length);

        if (!in_array($orderColumn, ['image', 'action'])) {
            $query->orderBy('p.' . $orderColumn, $orderDirection);
        }

        if (!empty($search)) {
            $query->where('p.name LIKE :search or p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $results = $query->getQuery()->getResult();
        $totalRecords = $productRepository->count([]);
        $totalRecordsFiltered = !empty($search) ? count($results) : $totalRecords;

        $data = [];
        foreach ($results as $result) {
            $deleteHtml = $this->render('partials/deleteButton.html.twig', [
                'model' => 'product',
                'id' => $result->getId(),
            ])->getContent();

            $editHtml = $this->render('partials/editButton.html.twig', [
                'model' => 'product',
                'id' => $result->getId(),
            ])->getContent();
            $image = $this->render('partials/image.html.twig',[
                'imgSrc' => 'images/products/'.$result->getImage(),
                'imgAlt' => $result->getImage(),
            ])->getContent();
            $actionHtml = '<div class="d-flex justify-content-center  gap-2 w-100">'.$editHtml . $deleteHtml .'</div>';
            $data[] = [
                'image' => $image ,
                'name' => $result->getName(),
                'description' => $result->getDescription(),
                'price' => $result->getPrice(),
                'category' => $result->getCategory(),
                'action' => $actionHtml,
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordsFiltered,
            'data' => $data,
        ]);
    }

    #[Route('admin/product', name: 'admin.product.index', methods: ['GET'])]
    public function adminIndex(): Response
    {
        return $this->render('admin/product/index.html.twig', []);
    }

    #[Route('admin/product/add', name: 'admin.product.add')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $slugger->slug($originalName);
                $newFileName = $safeName . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('images_dir') . '/products',
                        $newFileName
                    );
                } catch (FileException $e) {

                }
                $product->setImage($newFileName);
            }else{
                $product->setImage('no-image-yet');
            }
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Model has been added.');
            return $this->redirectToRoute('admin.product.index');
        }
        return $this->render('admin/product/productForm.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('admin/product/delete/{id}', name: 'admin.product.delete', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function delete(EntityManagerInterface $em, Product $product): RedirectResponse
    {
        $em->remove($product);
        $em->flush();
        $this->addFlash('danger', 'Proudct has been deleted.');
        return $this->redirectToRoute('admin.product.index');
    }

    #[Route('admin/product/edit/{id}', name: 'admin.product.edit')]
    public function edit(EntityManagerInterface $em, Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product, ['is_edit' => true]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Product has been edited.');
            return $this->redirectToRoute('admin.product.index');
        }
        return $this->render('admin/product/productForm.html.twig', [
            'form' => $form,
            'is_edit' => true,
        ]);
    }
}
