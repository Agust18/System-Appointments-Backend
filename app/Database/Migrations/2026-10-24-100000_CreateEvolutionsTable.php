<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Evoluciones de consulta: lo que el médico registra de una atención.
 *
 * - Una evolución nace de un turno (appointment_id) y la escribe el médico dueño de ese turno.
 * - signed_at NULL = borrador editable por su autor; con fecha = cerrada (solo se corrige con otra
 *   evolución que apunta a esta en corrects_id).
 * - No hay borrado: sin ON DELETE CASCADE hacia patients/appointments, para no perder datos clínicos.
 * - Sin default de tenant_id, como el resto de las tablas de Tenant.
 */
class CreateEvolutionsTable extends Migration
{
    public function up()
    {
        $this->db->query(
            "CREATE TABLE evolutions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tenant_id INT UNSIGNED NOT NULL,
                patient_id INT NOT NULL,
                medical_id INT NOT NULL,
                appointment_id INT NOT NULL,
                diagnosis TEXT NULL,
                observations TEXT NULL,
                treatment TEXT NULL,
                indications TEXT NULL,
                corrects_id INT UNSIGNED NULL,
                signed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_evolutions_patient (tenant_id, patient_id, created_at),
                KEY idx_evolutions_appointment (tenant_id, appointment_id),
                CONSTRAINT fk_evolutions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
                CONSTRAINT fk_evolutions_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
                CONSTRAINT fk_evolutions_medical FOREIGN KEY (medical_id) REFERENCES medicals (id),
                CONSTRAINT fk_evolutions_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
                CONSTRAINT fk_evolutions_corrects FOREIGN KEY (corrects_id) REFERENCES evolutions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS evolutions");
    }
}
