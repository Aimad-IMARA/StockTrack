<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Model;
use App\Form\ModelType;
use App\Repository\ModelRepository;
use App\Service\GetDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class ModelController extends AbstractController
{
    #[Route('admin/model', name: 'admin.model.index', methods: ['GET'])]
    public function adminIndex(): Response
    {
        return $this->render('admin/model/index.html.twig', []);
    }


    #[Route('admin/model/data', name: 'admin.model.data')]
    public function getData(ModelRepository $modelRepository, Request $request): Response
    {
        $draw = intval($request->get('draw'));
        $start = intval($request->get('start'));
        $length = intval($request->get('length'));
        $search = $request->get('search')['value'];
        $orderColumnIndex = intval($request->get('order')[0]['column']);
        $orderDirection = $request->get('order')[0]['dir'];
        $columns = $request->get('columns');
        $orderColumn = $columns[$orderColumnIndex]['data'];

        $query = $modelRepository->createQueryBuilder('m')
            ->setFirstResult($start)
            ->setMaxResults($length);

        if ($orderColumn != 'action') {
            $query->orderBy('m.' . $orderColumn, $orderDirection);
        }
        if (!empty($search)) {
            $query->where('m.name LIKE :search or m.path LIKE :search or m.modelOrder LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $results = $query->getQuery()->getResult();
        $totalRecords = $modelRepository->count([]);
        $totalRecordsFiltered = !empty($search) ? count($results) : $totalRecords;

        $data = [];
        foreach ($results as $result) {
            $deleteHtml = $this->render('partials/deleteButton.html.twig', [
                'model' => 'model',
                'id' => $result->getId(),
            ])->getContent();

            $editHtml = $this->render('partials/editButton.html.twig', [
                'model' => 'model',
                'id' => $result->getId(),
            ])->getContent();
            $actionHtml = '<div class="d-flex justify-content-center  gap-2 w-100">'.$editHtml . $deleteHtml .'</div>';
            $data[] = [
                'name' => $result->getName(),
                'path' => $result->getPath(),
                'roles' => in_array('ROLE_ADMIN', $result->getRoles()) ? ['ADMIN'] : ['USER'],
                'modelOrder' => $result->getModelOrder(),
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

    #[Route('admin/model/add', name: 'admin.model.add')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $model = new Model();
        $form = $this->createForm(ModelType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($model);
            $em->flush();
            $this->addFlash('success', 'Model has been added.');
            return $this->redirectToRoute('admin.model.index');
        }
        return $this->render('admin/model/modelForm.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('admin/model/delete/{id}', name: 'admin.model.delete', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function delete(EntityManagerInterface $em, Model $model): RedirectResponse
    {
        $em->remove($model);
        $em->flush();
        $this->addFlash('danger', 'Model has been deleted.');
        return $this->redirectToRoute('admin.category.index');
    }

    #[Route('admin/model/edit/{id}', name: 'admin.model.edit')]
    public function edit(EntityManagerInterface $em, Request $request, Model $model): Response
    {
        $form = $this->createForm(ModelType::class, $model, ['is_edit' => true]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Model has been edited.');
            return $this->redirectToRoute('admin.model.index');
        }
        return $this->render('admin/model/modelForm.html.twig', [
            'form' => $form,
            'is_edit' => true,
        ]);
    }

}
