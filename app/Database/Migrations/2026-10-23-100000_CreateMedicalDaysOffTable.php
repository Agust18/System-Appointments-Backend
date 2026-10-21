<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Días no laborables (feriados, vacaciones, licencias): bloqueos de día completo por rango de fechas.
 *
 * - medical_id NULL = toda la clínica (vale para todos los médicos); con valor, solo ese médico.
 * - date_from / date_to son inclusivos; un solo día lleva el mismo valor en ambos.
 * - Sin default de tenant_id, como el resto de las tablas de Tenant.
 */
class CreateMedicalDaysOffTable extends Migration
{
    public function up()
    {
        $this->db->query(
            "CREATE TABLE medical_days_off (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tenant_id INT UNSIGNED NOT NULL,
                medical_id INT NULL,
                date_from DATE NOT NULL,
                date_to DATE NOT NULL,
                reason VARCHAR(255) NULL,
                created_by INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_days_off_scope (tenant_id, medical_id, date_from, date_to),
                CONSTRAINT fk_days_off_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
                CONSTRAINT fk_days_off_medical FOREIGN KEY (medical_id) REFERENCES medicals (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS medical_days_off");
    }
}
