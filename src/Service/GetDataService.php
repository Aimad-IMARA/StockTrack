<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetDataService
{
    public function getData($repository, Request $request, $_query = '')
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
            ->setMaxResults($length)
            ->orderBy('e.' . $orderColumn, $orderDirection);

        if (!empty($search)) {
            $query .= $_query;
        }

        return $query->getQuery()->getResult();


    }
}
