<?php

/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the “Software”), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Repository\Supplies;

use App\Entity\Supplies\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;


class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // This method is used to count the number of available products for a Select2 output.

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function getCountAvailableProducts(bool $inUseOnly, string $search = '')
    {
        $query = $this->createQueryBuilder('p');

        $query = $query->select($query->expr()->count('p'))
            ->innerJoin('p.commodity', 'cm')
            ->innerJoin('p.brand', 'br')
            ->innerJoin('cm.category', 'cat')
            ->innerJoin('p.packaging', 'pkg')
            ->leftJoin('p.identifierCodes', 'ic')
        ;

        $this->addSearchExpressions($search, $query);

        if($inUseOnly) {
            $query
                ->leftJoin('p.articles',
                    'a',
                    Join::WITH,
                    $query->expr()->isNull('a.withdrawalDate')
                )
                ->andWhere($query->expr()->isNotNull('a'))
            ;
        }

        return $query
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    // This method is used to get the data for a Select2 output.
    public function getFilteredData($start, $length, bool $inUseOnly, string $search = ''): array
    {
        // This method generates an array which is to be used for a Select2 output.

        $result = [
            'recordsTotal' => $this->getCountAvailableProducts($inUseOnly),
        ];

        // no need to run the same query again if no search term is used.
        $result['recordsFiltered'] = $search ?
            $this->getCountAvailableProducts($inUseOnly, $search) :
            $result['recordsTotal'];

        $query = $this->createQueryBuilder('p')
            ->innerJoin('p.commodity', 'cm')
            ->innerJoin('p.brand', 'br')
            ->innerJoin('cm.category', 'cat')
            ->innerJoin('p.packaging', 'pkg')
            ->leftJoin('p.identifierCodes', 'ic')
        ;

        if($length > 0) {
            $query
                ->setFirstResult($start)
                ->setMaxResults($length);
        }

        $this->addSearchExpressions($search, $query);

        // count articles which are not withdrawn

            $query
                ->leftJoin('p.articles',
                    'a',
                    Join::WITH,
                    $query->expr()->isNull('a.withdrawalDate')
                )
                ->addSelect('COUNT(a) AS numUsage')
                ->groupBy('p.id');

        if($inUseOnly) {
            $query->andWhere($query->expr()->isNotNull('a'));
        }

        $result['data'] = $query
            ->getQuery()
            ->execute()
        ;

        return $result;
    }

    /**
     * Adds search expressions to the query.
     *
     * @param string $search
     * @param QueryBuilder $query
     * @return void
     */
    private function addSearchExpressions(string $search, QueryBuilder $query): void
    {
        if ($search) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('p.name', ':search'),
                $query->expr()->like('cm.name', ':search'),
                $query->expr()->like('br.name', ':search'),
                $query->expr()->like('cat.name', ':search'),
                $query->expr()->like('pkg.name', ':search'),
                $query->expr()->like('ic.code', ':search'),
            ));

            $query->setParameter('search', '%' . $search . '%');
        }
    }
}
