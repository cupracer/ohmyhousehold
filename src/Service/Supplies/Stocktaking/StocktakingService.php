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

namespace App\Service\Supplies\Stocktaking;

use App\Entity\Supplies\Stocktaking\InventoryItem;
use App\Entity\Supplies\Stocktaking\Stocktaking;
use App\Repository\Supplies\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class StocktakingService
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    // create InventoryItem for each current supply article
    public function createInventoryItemsForAllCurrentArticles(Stocktaking $stocktaking): array
    {
        // get all supply articles that are not withdrawn
        $articles = $this->articleRepository->findBy(['withdrawalDate' => null]);
        $inventoryItems = [];

        // create InventoryItem for each supply article
        foreach ($articles as $article) {
            $inventoryItem = new InventoryItem();
            $inventoryItem->setArticle($article);
            $inventoryItem->setCategoryName($article->getProduct()->getCommodity()->getCategory()->getName());
            $inventoryItem->setBrandName($article->getProduct()->getBrand()->getName());
            $inventoryItem->setCommodityName($article->getProduct()->getCommodity()->getName());
            $inventoryItem->setProductName($article->getProduct()->getName());

            foreach ($article->getProduct()->getIdentifierCodes() as $identifierCode) {
                $inventoryItem->addIdentifierCode(
                    sprintf("%s-%s", $identifierCode->getType(), $identifierCode->getCode())
                );
            }

            $inventoryItem->setStocktaking($stocktaking);

            $this->entityManager->persist($inventoryItem);
            $inventoryItems[] = $inventoryItem;
        }

        return $inventoryItems;
    }
}