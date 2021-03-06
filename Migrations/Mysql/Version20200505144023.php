<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbortMigrationException;

/**
 * Auto-generated Migration: Please modify to your needs! This block will be used as the migration description if getDescription() is not used.
 */
class Version20200505144023 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        
        $this->addSql('ALTER TABLE mapseven_gpx_domain_model_gpx CHANGE startcoords startcoords JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE endcoords endcoords JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE mincoords mincoords JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE maxcoords maxcoords JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE geojson geojson JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        
        $this->addSql('ALTER TABLE mapseven_gpx_domain_model_gpx CHANGE startcoords startcoords JSON NOT NULL COMMENT \'(DC2Type:json_array)\', CHANGE endcoords endcoords JSON NOT NULL COMMENT \'(DC2Type:json_array)\', CHANGE mincoords mincoords JSON NOT NULL COMMENT \'(DC2Type:json_array)\', CHANGE maxcoords maxcoords JSON NOT NULL COMMENT \'(DC2Type:json_array)\', CHANGE geojson geojson JSON NOT NULL COMMENT \'(DC2Type:json_array)\'');
    }
}