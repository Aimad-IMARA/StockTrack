<?php

namespace App\Service;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class GetDataService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getData(EntityRepository $repository, Request $request, array $searchableFields)
    {
        $start = intval($request->get('start'));
        $length = intval($request->get('length'));
        $search = $request->get('search')['value'];
        $orderColumnIndex = intval($request->get('order')[0]['column']);
        $orderDirection = $request->get('order')[0]['dir'];
        $columns = $request->get('columns');
        $orderColumn = $columns[$orderColumnIndex]['data'];

        $query = $repository->createQueryBuilder('e')
            ->setFirstResult($start)
            ->setMaxResults($length);

        if (!in_array($orderColumn,['action','image'])) {
            $query->orderBy('e.' . $orderColumn, $orderDirection);
        }

        if (!empty($search)) {
            $orX = $query->expr()->orX();
            foreach ($searchableFields as $field) {
                $orX->add($query->expr()->like('e.' . $field, ':search'));
            }
            $query->where($orX)->setParameter('search', '%' . $search . '%');
        }

        return $query->getQuery()->getResult();
    }
}
