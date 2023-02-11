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

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230211180218 extends AbstractMigration
{
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
    ];

    public function getDescription(): string
    {
        return 'add records to supplies_measure';
    }

    public function up(Schema $schema): void
    {
        foreach(self::MEASURES as $measure) {
            $this->addSql(
                "INSERT INTO supplies_measure (name, unit, physical_quantity) 
                        VALUES (:name, :unit, :physicalQuantity)", $measure
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach(self::MEASURES as $measure) {
            $this->addSql(
                "DELETE FROM supplies_measure WHERE name = :name AND unit = :unit", $measure
            );
        }
    }
}
