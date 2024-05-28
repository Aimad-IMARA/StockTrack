<?php

namespace App\Controller;

use App\Entity\Model;
use App\Entity\User;
use App\Form\ModelType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\GetDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

use function PHPUnit\Framework\isNan;

class UserController extends AbstractController
{
    public function __construct(GetDataService $getDataService)
    {
        $this->getDataService = $getDataService;
    }
    #[Route('user/profile', name: 'user.profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig', [
            'controller_name' => 'PROFILE',
        ]);
    }

    #[Route('admin/user', name: 'admin.user.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/user/index.html.twig', []);
    }
    #[Route('admin/user/data', name: 'admin.user.data', methods: ['GET'])]
    public function getData(UserRepository $userRepository, Request $request): Response
    {
        $searchableFields = ['id', 'username', 'email', 'roles'];
        $results = $this->getDataService->getData($userRepository, $request, $searchableFields);
        $data = [];

        foreach ($results as $result) {
            $deleteHtml = $this->render('partials/deleteButton.html.twig', [
                'model' => 'user',
                'id' => $result->getId(),
            ])->getContent();

            $editHtml = $this->render('partials/editButton.html.twig', [
                'model' => 'user',
                'id' => $result->getId(),
            ])->getContent();
            $actionHtml = in_array('ROLE_ADMIN', $result->getRoles()) ? '' : '<div class="d-flex justify-content-center  gap-2 w-100">'.$editHtml . $deleteHtml .'</div>';
            $data[] = [
                'id' => $result->getId(),
                'email' => $result->getEmail(),
                'username' => $result->getUsername(),
                'roles' => in_array('ROLE_ADMIN', $result->getRoles()) ? ['ADMIN'] : ['USER'],
                'action' => $actionHtml,
            ];
        }
        $draw = intval($request->get('draw'));
        $totalRecords = $userRepository->count([]);
        $totalRecordsFiltered = !empty($search) ? count($results) : $totalRecords;

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordsFiltered,
            'data' => $data,
        ]);
    }

    #[Route('admin/user/delete/{id}', name: 'admin.user.delete', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function delete(EntityManagerInterface $em, User $user): RedirectResponse
    {
        if(in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'ACCESS DENIED!');
            return $this->redirectToRoute('admin.user.index');
        }
        $em->remove($user);
        $em->flush();
        $this->addFlash('danger', 'User has been deleted.');
        return $this->redirectToRoute('admin.user.index');
    }

    #[Route('admin/user/edit/{id}', name: 'admin.user.edit')]
    public function edit(EntityManagerInterface $em, Request $request, User $user): Response
    {
        if(in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'ACCESS DENIED!');
            return $this->redirectToRoute('admin.user.index');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'User has been edited.');
            return $this->redirectToRoute('admin.user.index');
        }
        return $this->render('admin/user/userForm.html.twig', [
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('admin/user/add', name: 'admin.user.add')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'User has been added.');
            return $this->redirectToRoute('admin.user.index');
        }
        return $this->render('admin/user/userForm.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

}
