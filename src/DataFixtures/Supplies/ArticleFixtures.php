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

namespace App\DataFixtures\Supplies;

use App\Entity\Supplies\Article;
use App\Entity\Supplies\Product;
use App\Entity\Supplies\StorageLocation;
use App\Repository\Supplies\ProductRepository;
use App\Repository\Supplies\StorageLocationRepository;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const NUM_OBJECTS = 1000;
    public const REFERENCE_ID = 'supplies-article-';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StorageLocationRepository $storageLocationRepository
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $products = $this->productRepository->findAll();
        $storageLocations = $this->storageLocationRepository->findAll();

        for ($i = 1; $i <= self::NUM_OBJECTS; $i++) {
            $article = new Article();
            $article->setProduct($this->getReference(
                ProductFixtures::REFERENCE_ID . mt_rand(1, ProductFixtures::NUM_OBJECTS),
                Product::class
            ));
            $article->setStorageLocation($this->getReference(
                StorageLocationFixtures::REFERENCE_ID . mt_rand(1, StorageLocationFixtures::NUM_OBJECTS),
                StorageLocation::class
            ));
            // set purchase date to random date within the last 2 years
            $article->setPurchaseDate(new DateTimeImmutable(sprintf('-%d days', mt_rand(1, 730))));

            // set best before date to either none or a random date within the last 2 months and the next 2 years
            if (mt_rand(0, 9)) {
                $article->setBestBeforeDate(new DateTimeImmutable(sprintf(
                    '%+d days',
                    mt_rand(-60, 730)
                )));
            } else {
                $article->setBestBeforeDate(null);
            }

            // Either set withdrawal date to none or with a 5% chance to a random date within the last 2 years.
            // If the withdrawal date is not set,
            // either set discard date to none or with a 1% chance to a random date within the last 2 years.
            if (mt_rand(0, 19)) {
                $article->setWithdrawalDate(null);
                if (mt_rand(0, 99)) {
                    $article->setDiscardDate(null);
                } else {
                    $article->setDiscardDate(new DateTimeImmutable(sprintf('-%d days', mt_rand(1, 730))));
                }
            } else {
                $article->setWithdrawalDate(new DateTimeImmutable(sprintf('-%d days', mt_rand(1, 730))));
                $article->setDiscardDate(null);
            }

            $manager->persist($article);
            $this->addReference(self::REFERENCE_ID . $i, $article);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
            StorageLocationFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['demo_supplies'];
    }
}
