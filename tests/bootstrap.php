<?php

use App\Entity\Tenant\Tenant;
use App\Tenancy\TenantContext;

require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

/**
 * Los tests de Services y Models no pasan por el filtro 'tenant', así que arrancan con el
 * Tenant 1 ('demo', el que siembra la migración AddTenantIdToUsersAndPatients) ya fijado.
 * Los tests de aislamiento entre Tenants fijan otro explícitamente y deben restaurar este.
 */
TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
