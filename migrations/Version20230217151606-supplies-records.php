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

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230217151606 extends AbstractMigration
{
    public const CATEGORIES = [
        ['name' => 'supplies.category.name.food'],
        ['name' => 'supplies.category.name.drinks'],
        ['name' => 'supplies.category.name.medical'],
        ['name' => 'supplies.category.name.hygiene'],
        ['name' => 'supplies.category.name.camping'],
        ['name' => 'supplies.category.name.cleaning'],
        ['name' => 'supplies.category.name.electronics'],
        ['name' => 'supplies.category.name.pet'],
        ['name' => 'supplies.category.name.coffee'],
        ['name' => 'supplies.category.name.tea'],
        ['name' => 'supplies.category.name.clothing'],
        ['name' => 'supplies.category.name.other'],
    ];

    public const MEASURES = [
        [
            'name' => 'supplies.measure.name.milliliter',
            'unit' => 'supplies.measure.unit.milliliter',
            'physicalQuantity' => 'volume',
        ],
        [
            'name' => 'supplies.measure.name.liter',
            'unit' => 'supplies.measure.unit.liter',
            'physicalQuantity' => 'volume',
        ],
        [
            'name' => 'supplies.measure.name.gram',
            'unit' => 'supplies.measure.unit.gram',
            'physicalQuantity' => 'mass',
        ],
        [
            'name' => 'supplies.measure.name.kilogram',
            'unit' => 'supplies.measure.unit.kilogram',
            'physicalQuantity' => 'mass',
        ],
        [
            'name' => 'supplies.measure.name.piece',
            'unit' => 'supplies.measure.unit.piece',
            'physicalQuantity' => 'piece'
        ],
        [
            'name' => 'supplies.measure.name.meter',
            'unit' => 'supplies.measure.unit.meter',
            'physicalQuantity' => 'length'
        ],
    ];

    public const PACKAGING = [
        ['name' => 'supplies.packaging.name.tin-can'],
        ['name' => 'supplies.packaging.name.canning-jar'],
        ['name' => 'supplies.packaging.name.glass'],
        ['name' => 'supplies.packaging.name.plastic-bag'],
        ['name' => 'supplies.packaging.name.glass-bottle'],
        ['name' => 'supplies.packaging.name.plastic-bottle'],
        ['name' => 'supplies.packaging.name.tube'],
        ['name' => 'supplies.packaging.name.aluminum'],
        ['name' => 'supplies.packaging.name.plastic-net'],
        ['name' => 'supplies.packaging.name.cardboard'],
        ['name' => 'supplies.packaging.name.paper'],
        ['name' => 'supplies.packaging.name.tetra-pak'],
        ['name' => 'supplies.packaging.name.plastic-can'],
        ['name' => 'supplies.packaging.name.foil'],
    ];

    public function getDescription(): string
    {
        return 'add records to supplies tables';
    }

    public function up(Schema $schema): void
    {
        // add records to the category table
        foreach(self::CATEGORIES as $category) {
            $this->addSql(
                "INSERT INTO supplies_category (name) VALUES (:name)", $category
            );
        }

        // add records to the measure table
        foreach(self::MEASURES as $measure) {
            $this->addSql(
                "INSERT INTO supplies_measure (name, unit, physical_quantity) 
                        VALUES (:name, :unit, :physicalQuantity)", $measure
            );
        }

        // add records to the packaging table
        foreach(self::PACKAGING as $packaging) {
            $this->addSql(
                "INSERT INTO supplies_packaging (name) VALUES (:name)", $packaging
            );
        }
    }

    public function down(Schema $schema): void
    {
        // delete records from the category table
        foreach(self::PACKAGING as $packaging) {
            $this->addSql(
                "DELETE FROM supplies_category WHERE name = :name", $packaging
            );
        }

        // delete records from the measure table
        foreach(self::MEASURES as $measure) {
            $this->addSql(
                "DELETE FROM supplies_measure WHERE name = :name AND unit = :unit", $measure
            );
        }

        // delete records from the packaging table
        foreach(self::CATEGORIES as $category) {
            $this->addSql(
                "DELETE FROM supplies_category WHERE name = :name", $category
            );
        }
    }
}
