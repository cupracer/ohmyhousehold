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

final class Version20230211181322 extends AbstractMigration
{
    public const PACKAGINGS = [
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
        return 'add records to supplies_packaging';
    }

    public function up(Schema $schema): void
    {
        foreach(self::PACKAGINGS as $packaging) {
            $this->addSql(
                "INSERT INTO supplies_packaging (name) VALUES (:name)", $packaging
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach(self::PACKAGINGS as $packaging) {
            $this->addSql(
                "DELETE FROM supplies_category WHERE name = :name", $packaging
            );
        }
    }
}