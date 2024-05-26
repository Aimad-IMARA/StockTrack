<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\GetDataService;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('user/category', name: 'user.category.index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('admin/category/get',name: 'admin.category.data')]
    public function getData(CategoryRepository $categoryRepository, Request $request): JsonResponse
    {
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start'));
        $length = intval($request->get('length'));
        $search = $request->get('search')['value'];

        $orderColumnIndex = intval($request->get('order')[0]['column']);
        $orderDirection = $request->get('order')[0]['dir'];
        $columns = $request->get('columns');

        $orderColumn = $columns[$orderColumnIndex]['data'];
        $query = $categoryRepository->createQueryBuilder('c')
            ->setFirstResult($start)
            ->setMaxResults($length);

        if (!in_array($orderColumn, ['image', 'action'])) {
            $query->orderBy('c.' . $orderColumn, $orderDirection);
        }


        if (!empty($search)) {
            $query->where('c.name LIKE :search or c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $results = $query->getQuery()->getResult();
        $totalRecords = $categoryRepository->count([]);
        $totalRecordsFiltered = !empty($search) ? count($results) : $totalRecords;

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
            $image = $this->render('partials/image.html.twig',[
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
        $category = $categoryRepository->find($category->getId());

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
            if($image){
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
            }else{
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
