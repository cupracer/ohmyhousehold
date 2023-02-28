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

namespace App\Service\Supplies;

use App\Entity\NavbarNotificationItem;
use App\Entity\Supplies\Article;
use App\Entity\Supplies\ExpiringArticleNavbarNotificationItem;
use App\Repository\Supplies\ArticleRepository;
use DateTime;

class ArticleService {
    public function __construct(
        private readonly ArticleRepository $articleRepository
    )
    {
    }

    public function getExpiringArticles(): array
    {
        $notifications = [];

        //TODO: make variables configurable
        $daysLeftLimit = 14;
        $daysLeftWarning = 7;

        /** @var Article $article */
        foreach($this->articleRepository->findAllExpiringArticles($daysLeftLimit) as $article) {
            $navbarNotificationItem = new ExpiringArticleNavbarNotificationItem();
            $navbarNotificationItem->setCategory('supplies_article');
            $navbarNotificationItem->setTitle($article->getProduct()->getName());
            $navbarNotificationItem->setExpiryDate($article->getBestBeforeDate());
            $navbarNotificationItem->setItemId($article->getId());

            if($article->getBestBeforeDate() < (new DateTime())->modify("midnight")) {
                $navbarNotificationItem->setCssClass('danger');
            }elseif($article->getBestBeforeDate() <= (new DateTime())->modify("midnight")->modify('+ ' . $daysLeftWarning . ' days')) {
                $navbarNotificationItem->setCssClass('warning');
            }else {
                $navbarNotificationItem->setCssClass('black');
            }

            $notifications[] = $navbarNotificationItem;
        }

        return $notifications;
    }
}