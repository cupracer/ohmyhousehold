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

use App\Entity\Supplies\IdentifierCode;
use App\Entity\Supplies\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class IdentifierCodeFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const NUM_OBJECTS = 50;
    public const REFERENCE_ID = 'supplies-identifiercode-';

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= self::NUM_OBJECTS; $i++) {
            $type = IdentifierCode::TYPES[array_rand(IdentifierCode::TYPES)];

            $identifierCode = new IdentifierCode();
            $identifierCode->setType($type['name']);

            $identifierCode->setCode(mt_rand(
                (int) str_pad("1", $type['length'], "0", STR_PAD_RIGHT),
                (int) str_pad("9", $type['length'], "9", STR_PAD_RIGHT)
            ));

            $identifierCode->setProduct(
                $this->getReference(
                    ProductFixtures::REFERENCE_ID . mt_rand(1, ProductFixtures::NUM_OBJECTS),
                    Product::class
            ));

            $manager->persist($identifierCode);
            $this->addReference(self::REFERENCE_ID . $i, $identifierCode);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }
}
