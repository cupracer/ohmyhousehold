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

final class Version20230217151408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'setup supplies tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplies_article (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, storage_location_id INT NOT NULL, purchase_date DATE DEFAULT NULL, best_before_date DATE DEFAULT NULL, withdrawal_date DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_94246E3A4584665A (product_id), INDEX IDX_94246E3ACDDD8AF (storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_brand (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_633EF0C65E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_64071A7E5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_commodity (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, minimum_global_stock INT DEFAULT NULL, UNIQUE INDEX UNIQ_531CA6A5E237E06 (name), INDEX IDX_531CA6A12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_identifier_code (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, type VARCHAR(10) NOT NULL, code VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_BA56A9564584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_measure (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, unit VARCHAR(255) NOT NULL, physical_quantity VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_161979795E237E06 (name), UNIQUE INDEX UNIQ_16197979DCBB0C53 (unit), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_minimum_commodity_stock (id INT AUTO_INCREMENT NOT NULL, commodity_id INT NOT NULL, storage_location_id INT DEFAULT NULL, count INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_BB2A5F21B4ACC212 (commodity_id), INDEX IDX_BB2A5F21CDDD8AF (storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_minimum_product_stock (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, storage_location_id INT DEFAULT NULL, count INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_842E5E3D4584665A (product_id), INDEX IDX_842E5E3DCDDD8AF (storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_packaging (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_E437E89B5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_product (id INT AUTO_INCREMENT NOT NULL, commodity_id INT NOT NULL, brand_id INT NOT NULL, measure_id INT NOT NULL, packaging_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, quantity NUMERIC(10, 2) NOT NULL, organic_certification TINYINT(1) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, minimum_global_stock INT DEFAULT NULL, INDEX IDX_455464F1B4ACC212 (commodity_id), INDEX IDX_455464F144F5D008 (brand_id), INDEX IDX_455464F15DA37D00 (measure_id), INDEX IDX_455464F14E7B3801 (packaging_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplies_storage_location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_4506B5985E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplies_article ADD CONSTRAINT FK_94246E3A4584665A FOREIGN KEY (product_id) REFERENCES supplies_product (id)');
        $this->addSql('ALTER TABLE supplies_article ADD CONSTRAINT FK_94246E3ACDDD8AF FOREIGN KEY (storage_location_id) REFERENCES supplies_storage_location (id)');
        $this->addSql('ALTER TABLE supplies_commodity ADD CONSTRAINT FK_531CA6A12469DE2 FOREIGN KEY (category_id) REFERENCES supplies_category (id)');
        $this->addSql('ALTER TABLE supplies_identifier_code ADD CONSTRAINT FK_BA56A9564584665A FOREIGN KEY (product_id) REFERENCES supplies_product (id)');
        $this->addSql('ALTER TABLE supplies_minimum_commodity_stock ADD CONSTRAINT FK_BB2A5F21B4ACC212 FOREIGN KEY (commodity_id) REFERENCES supplies_commodity (id)');
        $this->addSql('ALTER TABLE supplies_minimum_commodity_stock ADD CONSTRAINT FK_BB2A5F21CDDD8AF FOREIGN KEY (storage_location_id) REFERENCES supplies_storage_location (id)');
        $this->addSql('ALTER TABLE supplies_minimum_product_stock ADD CONSTRAINT FK_842E5E3D4584665A FOREIGN KEY (product_id) REFERENCES supplies_product (id)');
        $this->addSql('ALTER TABLE supplies_minimum_product_stock ADD CONSTRAINT FK_842E5E3DCDDD8AF FOREIGN KEY (storage_location_id) REFERENCES supplies_storage_location (id)');
        $this->addSql('ALTER TABLE supplies_product ADD CONSTRAINT FK_455464F1B4ACC212 FOREIGN KEY (commodity_id) REFERENCES supplies_commodity (id)');
        $this->addSql('ALTER TABLE supplies_product ADD CONSTRAINT FK_455464F144F5D008 FOREIGN KEY (brand_id) REFERENCES supplies_brand (id)');
        $this->addSql('ALTER TABLE supplies_product ADD CONSTRAINT FK_455464F15DA37D00 FOREIGN KEY (measure_id) REFERENCES supplies_measure (id)');
        $this->addSql('ALTER TABLE supplies_product ADD CONSTRAINT FK_455464F14E7B3801 FOREIGN KEY (packaging_id) REFERENCES supplies_packaging (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplies_article DROP FOREIGN KEY FK_94246E3A4584665A');
        $this->addSql('ALTER TABLE supplies_article DROP FOREIGN KEY FK_94246E3ACDDD8AF');
        $this->addSql('ALTER TABLE supplies_commodity DROP FOREIGN KEY FK_531CA6A12469DE2');
        $this->addSql('ALTER TABLE supplies_identifier_code DROP FOREIGN KEY FK_BA56A9564584665A');
        $this->addSql('ALTER TABLE supplies_minimum_commodity_stock DROP FOREIGN KEY FK_BB2A5F21B4ACC212');
        $this->addSql('ALTER TABLE supplies_minimum_commodity_stock DROP FOREIGN KEY FK_BB2A5F21CDDD8AF');
        $this->addSql('ALTER TABLE supplies_minimum_product_stock DROP FOREIGN KEY FK_842E5E3D4584665A');
        $this->addSql('ALTER TABLE supplies_minimum_product_stock DROP FOREIGN KEY FK_842E5E3DCDDD8AF');
        $this->addSql('ALTER TABLE supplies_product DROP FOREIGN KEY FK_455464F1B4ACC212');
        $this->addSql('ALTER TABLE supplies_product DROP FOREIGN KEY FK_455464F144F5D008');
        $this->addSql('ALTER TABLE supplies_product DROP FOREIGN KEY FK_455464F15DA37D00');
        $this->addSql('ALTER TABLE supplies_product DROP FOREIGN KEY FK_455464F14E7B3801');
        $this->addSql('DROP TABLE supplies_article');
        $this->addSql('DROP TABLE supplies_brand');
        $this->addSql('DROP TABLE supplies_category');
        $this->addSql('DROP TABLE supplies_commodity');
        $this->addSql('DROP TABLE supplies_identifier_code');
        $this->addSql('DROP TABLE supplies_measure');
        $this->addSql('DROP TABLE supplies_minimum_commodity_stock');
        $this->addSql('DROP TABLE supplies_minimum_product_stock');
        $this->addSql('DROP TABLE supplies_packaging');
        $this->addSql('DROP TABLE supplies_product');
        $this->addSql('DROP TABLE supplies_storage_location');
    }
}
