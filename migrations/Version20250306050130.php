<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306050130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clothing_item (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image_url VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, partner_link VARCHAR(255) DEFAULT NULL, color VARCHAR(100) DEFAULT NULL, style VARCHAR(100) DEFAULT NULL, INDEX IDX_CFE0A4E912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE outfit (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, description LONGTEXT DEFAULT NULL, style VARCHAR(50) DEFAULT NULL, INDEX IDX_32029601A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE outfit_clothing_item (outfit_id INT NOT NULL, clothing_item_id INT NOT NULL, INDEX IDX_D50A35E3AE96E385 (outfit_id), INDEX IDX_D50A35E3AA13B545 (clothing_item_id), PRIMARY KEY(outfit_id, clothing_item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE outfit_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, outfit_items JSON DEFAULT NULL, is_shared TINYINT(1) NOT NULL, image_url VARCHAR(255) DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, title VARCHAR(255) DEFAULT NULL, style VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_85699843A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_wardrobe (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, clothing_item_id INT NOT NULL, added_at DATETIME NOT NULL, usage_count INT NOT NULL, is_favorite TINYINT(1) NOT NULL, INDEX IDX_CC0DE10EA76ED395 (user_id), INDEX IDX_CC0DE10EAA13B545 (clothing_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clothing_item ADD CONSTRAINT FK_CFE0A4E912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE outfit ADD CONSTRAINT FK_32029601A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE outfit_clothing_item ADD CONSTRAINT FK_D50A35E3AE96E385 FOREIGN KEY (outfit_id) REFERENCES outfit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE outfit_clothing_item ADD CONSTRAINT FK_D50A35E3AA13B545 FOREIGN KEY (clothing_item_id) REFERENCES clothing_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE outfit_history ADD CONSTRAINT FK_85699843A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_wardrobe ADD CONSTRAINT FK_CC0DE10EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_wardrobe ADD CONSTRAINT FK_CC0DE10EAA13B545 FOREIGN KEY (clothing_item_id) REFERENCES clothing_item (id)');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP username');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothing_item DROP FOREIGN KEY FK_CFE0A4E912469DE2');
        $this->addSql('ALTER TABLE outfit DROP FOREIGN KEY FK_32029601A76ED395');
        $this->addSql('ALTER TABLE outfit_clothing_item DROP FOREIGN KEY FK_D50A35E3AE96E385');
        $this->addSql('ALTER TABLE outfit_clothing_item DROP FOREIGN KEY FK_D50A35E3AA13B545');
        $this->addSql('ALTER TABLE outfit_history DROP FOREIGN KEY FK_85699843A76ED395');
        $this->addSql('ALTER TABLE user_wardrobe DROP FOREIGN KEY FK_CC0DE10EA76ED395');
        $this->addSql('ALTER TABLE user_wardrobe DROP FOREIGN KEY FK_CC0DE10EAA13B545');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE clothing_item');
        $this->addSql('DROP TABLE outfit');
        $this->addSql('DROP TABLE outfit_clothing_item');
        $this->addSql('DROP TABLE outfit_history');
        $this->addSql('DROP TABLE user_wardrobe');
        $this->addSql('ALTER TABLE user ADD username VARCHAR(50) NOT NULL, DROP nom, DROP created_at, DROP updated_at');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }
}
