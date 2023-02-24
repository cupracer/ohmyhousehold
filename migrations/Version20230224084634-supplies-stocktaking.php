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

final class Version20230224084634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'setup stocktaking tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE supplies_stocktaking (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_14A3000F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_stocktaking_inventory_item (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, stocktaking_id INT NOT NULL, brand_name VARCHAR(255) NOT NULL, commodity_name VARCHAR(255) NOT NULL, product_name VARCHAR(255) DEFAULT NULL, category_name VARCHAR(255) NOT NULL, best_before_date DATE DEFAULT NULL, status INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, identifier_codes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_7C8CA08D7294869C (article_id), INDEX IDX_7C8CA08D89B86B49 (stocktaking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplies_stocktaking_inventory_item ADD CONSTRAINT FK_7C8CA08D7294869C FOREIGN KEY (article_id) REFERENCES supplies_article (id)');
        $this->addSql('ALTER TABLE supplies_stocktaking_inventory_item ADD CONSTRAINT FK_7C8CA08D89B86B49 FOREIGN KEY (stocktaking_id) REFERENCES supplies_stocktaking (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplies_stocktaking_inventory_item DROP FOREIGN KEY FK_7C8CA08D7294869C');
        $this->addSql('ALTER TABLE supplies_stocktaking_inventory_item DROP FOREIGN KEY FK_7C8CA08D89B86B49');
        $this->addSql('DROP TABLE supplies_stocktaking');
        $this->addSql('DROP TABLE supplies_stocktaking_inventory_item');
    }
}
