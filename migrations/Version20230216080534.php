<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230216080534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplies_article (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, storage_location_id INT NOT NULL, purchase_date DATE DEFAULT NULL, best_before_date DATE DEFAULT NULL, withdrawal_date DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_94246E3A4584665A (product_id), INDEX IDX_94246E3ACDDD8AF (storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplies_article ADD CONSTRAINT FK_94246E3A4584665A FOREIGN KEY (product_id) REFERENCES supplies_product (id)');
        $this->addSql('ALTER TABLE supplies_article ADD CONSTRAINT FK_94246E3ACDDD8AF FOREIGN KEY (storage_location_id) REFERENCES supplies_storage_location (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplies_article DROP FOREIGN KEY FK_94246E3A4584665A');
        $this->addSql('ALTER TABLE supplies_article DROP FOREIGN KEY FK_94246E3ACDDD8AF');
        $this->addSql('DROP TABLE supplies_article');
    }
}
