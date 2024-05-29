<?php

namespace App\Controller;

use AllowDynamicProperties;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\GetDataService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryController extends AbstractController
{
    public function __construct(GetDataService $getDataService)
    {
        $this->getDataService = $getDataService;
    }

    #[Route('user/category', name: 'user.category.index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('user/category/{id}/products', name: 'user.category.products', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function categoryProducts(Category $category, ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $featuredProducts = $productRepository->findFeatured();
        $products = $paginator->paginate(
            $category->getProducts(),
            $request->query->getInt('page', 1),
            8
        );
        return $this->render('category/products.html.twig', [
            'category' => $category,
            'products' => $products,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    #[Route('admin/category/get', name: 'admin.category.data')]
    public function getData(CategoryRepository $categoryRepository, Request $request): JsonResponse
    {
        $searchableFields = ['name', 'description'];
        $results = $this->getDataService->getData($categoryRepository, $request, $searchableFields);
        $data = [];

        foreach ($results as $result) {
            $deleteHtml = $this->render('partials/deleteButton.html.twig', [
                'model' => 'category',
                'id' => $result->getId(),
            ])->getContent();

            $editHtml = $this->render('partials/editButton.html.twig', [
                'model' => 'category',
                'id' => $result->getId(),
            ])->getContent();
            $image = $this->render('partials/image.html.twig', [
                'imgSrc' => 'images/categories/'.$result->getImage(),
                'imgAlt' => $result->getImage(),
            ])->getContent();
            $actionHtml = '<div class="d-flex justify-content-center  gap-2 w-100">'.$editHtml . $deleteHtml .'</div>';
            $data[] = [
                'name' => $result->getName(),
                'description' => $result->getDescription(),
                'image' => $image,
                'action' => $actionHtml,
            ];
        }

        $draw = intval($request->get('draw'));
        $totalRecords = $categoryRepository->count([]);
        $totalRecordsFiltered = !empty($search) ? count($results) : $totalRecords;


        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordsFiltered,
            'data' => $data,
        ]);
    }

    #[Route('admin/category', name: 'admin.category.index')]
    public function adminIndex(): Response
    {
        return $this->render('admin/category/index.html.twig', []);
    }

    #[Route('user/category/{id}', name: 'user.category.show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function show(CategoryRepository $categoryRepository, Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('admin/category/delete/{id}', name: 'admin.category.delete', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function delete(EntityManagerInterface $em, Category $category): RedirectResponse
    {
        $em->remove($category);
        $em->flush();
        $this->addFlash('danger', 'Category has been deleted.');
        return $this->redirectToRoute('admin.category.index');
    }

    #[Route('admin/category/edit/{id}', name: 'admin.category.edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function edit(CategoryRepository $categoryRepository, Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category, ['is_edit' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Category has been updated.');
            return $this->redirectToRoute('admin.category.index');
        }
        return $this->render('admin/category/categoryForm.html.twig', [
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('admin/category/add', name: 'admin.category.add', methods: ['GET', 'POST'])]
    public function add(EntityManagerInterface $em, Request $request, SluggerInterface $slugger): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $image = $form->get('image')->getData();
            if($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $slugger->slug($originalName);
                $newFileName = $safeName . '-' . uniqid() . '.' . $image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('images_dir') . '/categories',
                        $newFileName
                    );
                } catch (FileException $e) {

                }
                $category->setImage($newFileName);
            } else {
                $category->setImage('no-image-yet');
            }

            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Category has been added.');
            return $this->redirectToRoute('admin.category.index');
        }
        return $this->render('admin/category/categoryForm.html.twig', [
            'is_edit' => false,
            'form' => $form
        ]);
    }
}
