<?php

/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Repository\Supplies;

use App\Entity\Supplies\Article;
use App\Entity\Supplies\Product;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllInStockByProduct(Product $product): array
    {
        $qb = $this->createQueryBuilder('a');

        return $qb
            ->andWhere('a.product = :product')
            ->andWhere($qb->expr()->isNull('a.withdrawalDate'))
            ->andWhere($qb->expr()->isNull('a.discardDate'))
            ->setParameter('product', $product)
            ->orderBy('a.bestBeforeDate', 'ASC')
            ->addOrderBy('a.purchaseDate', 'ASC')
            ->getQuery()
            ->execute()
        ;
    }

    public function findAllExpiringArticles(int $remainingDays): array
    {
        $dateToCheck = (new DateTime())->modify('midnight');

        $qb = $this->createQueryBuilder('a');

        return $qb
            ->andWhere($qb->expr()->isNull('a.withdrawalDate'))
            ->andWhere($qb->expr()->isNull('a.discardDate'))
            ->andWhere(
                $qb->expr()->lte("DATE_SUB(a.bestBeforeDate, :remainingDays, 'DAY')", ":dateToCheck"),
            )
            ->setParameter('remainingDays', $remainingDays)
            ->setParameter('dateToCheck', $dateToCheck)
            ->orderBy('a.bestBeforeDate', 'ASC')
            ->getQuery()
            ->execute()
            ;
    }
}
