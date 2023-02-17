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

namespace App\DataFixtures\Supplies;

use App\Entity\Supplies\Brand;
use App\Entity\Supplies\Commodity;
use App\Entity\Supplies\Product;
use App\Repository\Supplies\MeasureRepository;
use App\Repository\Supplies\PackagingRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const NUM_OBJECTS = 50;
    public const REFERENCE_ID = 'supplies-product-';

    public function __construct(
        private readonly MeasureRepository $measureRepository,
        private readonly PackagingRepository $packagingRepository
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $measures = $this->measureRepository->findAll();
        $packaging = $this->packagingRepository->findAll();

        for ($i = 1; $i <= self::NUM_OBJECTS; $i++) {
            $product = new Product();
            $product->setName('Product_' . $i);
            $product->setCommodity($this->getReference(
                CommodityFixtures::REFERENCE_ID . mt_rand(1, CommodityFixtures::NUM_OBJECTS),
                Commodity::class
            ));
            $product->setBrand($this->getReference(
                BrandFixtures::REFERENCE_ID . mt_rand(1, BrandFixtures::NUM_OBJECTS),
                Brand::class
            ));
            $product->setMeasure($measures[array_rand($measures)]);
            $product->setQuantity(mt_rand(1, 1000));
            $product->setOrganicCertification((bool) mt_rand(0, 1));
            $product->setPackaging($packaging[array_rand($packaging)]);

            $manager->persist($product);
            $this->addReference(self::REFERENCE_ID . $i, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CommodityFixtures::class,
            BrandFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['demo_supplies'];
    }
}
