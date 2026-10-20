<?php

namespace App\Models\Tenant;

use App\Converter\Tenant\PrimitiveToTenantSettingsConverter;
use App\Entity\Tenant\TenantSettings;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * `find()` lee la fila del Tenant de TenantContext (rutas de Tenant). `insert()`, `update()` y
 * `findByTenantId()` usan el id explícito de la entidad o de la URL: los llaman el alta y el panel
 * de plataforma, que no tiene Tenant en el contexto. Toda query filtra por `tenant_id`.
 */
final class TenantSettingsModel
{
    private BaseConnection $db;
    private PrimitiveToTenantSettingsConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToTenantSettingsConverter();
    }

    public function insert(TenantSettings $settings): ?TenantSettings
    {
        $query = "INSERT INTO tenant_settings (tenant_id, name, reply_to_email, frontend_url, reception_email, reception_email_enabled, contact_phone, contact_whatsapp, contact_email, address, opening_hours, landing_enabled, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $this->db->query($query, [
            $settings->getTenantId(),
            $settings->getName(),
            $settings->getReplyToEmail(),
            $settings->getFrontendUrl(),
            $settings->getReceptionEmail(),
            $settings->isReceptionEmailEnabled() ? 1 : 0,
            $settings->getContactPhone(),
            $settings->getContactWhatsapp(),
            $settings->getContactEmail(),
            $settings->getAddress(),
            $settings->getOpeningHours(),
            $settings->isLandingEnabled() ? 1 : 0,
            $settings->getCreatedAt()->format('Y-m-d H:i:s'),
            $settings->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $this->findByTenantId($settings->getTenantId());
    }

    /**
     * Persiste la configuración editable. Solo toca la fila del Tenant de la entidad
     * (`WHERE tenant_id = ?`); el panel lo llama sin TenantContext.
     */
    public function update(TenantSettings $settings): ?TenantSettings
    {
        $this->db->query(
            "UPDATE tenant_settings SET name = ?, reply_to_email = ?, frontend_url = ?, reception_email = ?, reception_email_enabled = ?, contact_phone = ?, contact_whatsapp = ?, contact_email = ?, address = ?, opening_hours = ?, landing_enabled = ?, updated_at = NOW() WHERE tenant_id = ?",
            [
                $settings->getName(),
                $settings->getReplyToEmail(),
                $settings->getFrontendUrl(),
                $settings->getReceptionEmail(),
                $settings->isReceptionEmailEnabled() ? 1 : 0,
                $settings->getContactPhone(),
                $settings->getContactWhatsapp(),
                $settings->getContactEmail(),
                $settings->getAddress(),
                $settings->getOpeningHours(),
                $settings->isLandingEnabled() ? 1 : 0,
                $settings->getTenantId(),
            ]
        );

        return $this->findByTenantId($settings->getTenantId());
    }

    /**
     * La configuración del Tenant del contexto actual, o null si no tiene fila.
     */
    public function find(): ?TenantSettings
    {
        return $this->findByTenantId(TenantContext::id());
    }

    /**
     * Solo para el panel de plataforma, que no tiene Tenant en el contexto: el id sale de la URL y
     * el llamador ya verificó que el Tenant existe. Las rutas de Tenant usan find().
     */
    public function findByTenantId(int $tenantId): ?TenantSettings
    {
        $result = $this->db->query("SELECT * FROM tenant_settings WHERE tenant_id = ?", [$tenantId]);
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }
}
