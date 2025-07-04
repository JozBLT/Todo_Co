<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250526161719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD author_id INT DEFAULT NULL');

        $this->addSql("
        INSERT INTO `user` (`username`, `email`, `roles`, `password`, `created_at`, `updated_at`)
        SELECT 'anonyme', 'anonyme@todo.co', '[]', '', NOW(), NOW()
        WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE username = 'anonyme')
    ");

        $this->addSql("
        UPDATE task
        SET author_id = (SELECT id FROM `user` WHERE username = 'anonyme')
    ");

        $this->addSql('ALTER TABLE task MODIFY author_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_527EDB25F675F31B ON task (author_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F675F31B');
        $this->addSql('DROP INDEX IDX_527EDB25F675F31B ON task');
        $this->addSql('ALTER TABLE task DROP author_id');
        $this->addSql("DELETE FROM `user` WHERE username = 'anonyme'");
    }
}
