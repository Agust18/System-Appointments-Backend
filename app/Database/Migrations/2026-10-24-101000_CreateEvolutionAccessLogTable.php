<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registro de lecturas de evoluciones: quién accedió, a qué paciente y cuándo.
 *
 * - evolution_id NULL = lectura de un listado; con valor, de una evolución puntual.
 * - No guarda contenido clínico, solo el rastro del acceso.
 * - Sin default de tenant_id, como el resto de las tablas de Tenant.
 */
class CreateEvolutionAccessLogTable extends Migration
{
    public function up()
    {
        $this->db->query(
            "CREATE TABLE evolution_access_log (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tenant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                patient_id INT NOT NULL,
                evolution_id INT UNSIGNED NULL,
                action VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_evolution_access_patient (tenant_id, patient_id, created_at),
                CONSTRAINT fk_evolution_access_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
                CONSTRAINT fk_evolution_access_user FOREIGN KEY (user_id) REFERENCES users (id),
                CONSTRAINT fk_evolution_access_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
                CONSTRAINT fk_evolution_access_evolution FOREIGN KEY (evolution_id) REFERENCES evolutions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS evolution_access_log");
    }
}
