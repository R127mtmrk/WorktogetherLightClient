<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260318125759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
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
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, username VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON DEFAULT \'json_array(_utf8mb4\\\\\'\'ROLE_USER\\\\\'\')\' NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX uniq_app_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F19EB6921');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F700047D2');
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
