<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309085236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, client_id INT NOT NULL, ticket_id INT NOT NULL, INDEX IDX_B6BD307F19EB6921 (client_id), INDEX IDX_B6BD307F700047D2 (ticket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE offer ADD max_units INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939819EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939853C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA319EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_technician ADD CONSTRAINT FK_64D77B1C700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_technician ADD CONSTRAINT FK_64D77B1CE6C5D496 FOREIGN KEY (technician_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit ADD CONSTRAINT FK_DCBB0C538D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE unit ADD CONSTRAINT FK_DCBB0C53700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE unit ADD CONSTRAINT FK_DCBB0C53DF9BA23B FOREIGN KEY (bay_id) REFERENCES bay (id)');
        $this->addSql('ALTER TABLE unit ADD CONSTRAINT FK_DCBB0C535D83CC1 FOREIGN KEY (state_id) REFERENCES state (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F19EB6921');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F700047D2');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE offer DROP max_units');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939819EB6921');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939853C674EE');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA319EB6921');
        $this->addSql('ALTER TABLE ticket_technician DROP FOREIGN KEY FK_64D77B1C700047D2');
        $this->addSql('ALTER TABLE ticket_technician DROP FOREIGN KEY FK_64D77B1CE6C5D496');
        $this->addSql('ALTER TABLE unit DROP FOREIGN KEY FK_DCBB0C538D9F6D38');
        $this->addSql('ALTER TABLE unit DROP FOREIGN KEY FK_DCBB0C53700047D2');
        $this->addSql('ALTER TABLE unit DROP FOREIGN KEY FK_DCBB0C53DF9BA23B');
        $this->addSql('ALTER TABLE unit DROP FOREIGN KEY FK_DCBB0C535D83CC1');
    }
}
