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

use App\Entity\Supplies\Commodity;
use App\Repository\Supplies\CategoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CommodityFixtures extends Fixture implements FixtureGroupInterface
{
    public const NUM_OBJECTS = 200;
    public const REFERENCE_ID = 'supplies-commodity-';

    public function __construct(
        private readonly CategoryRepository $categoryRepository
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $categories = $this->categoryRepository->findAll();

        for ($i = 1; $i <= self::NUM_OBJECTS; $i++) {
            $commodity = new Commodity();
            $commodity->setName('Commodity_' . $i);
            $commodity->setCategory($categories[array_rand($categories)]);
            $manager->persist($commodity);
            $this->addReference(self::REFERENCE_ID . $i, $commodity);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['demo_supplies'];
    }
}
