<?php
namespace App\Constants;

final class Role
{
    public const ADMIN = 1;
    public const MEDICAL = 2;
    public const RECEPTION = 4;
    // El id 3 fue del rol "paciente" (eliminado: el paciente ya no es usuario). No reutilizarlo.

    /**
     * Lista única de roles: id => roles.name. Es la fuente de RolesSeeder y de isValid().
     * Un rol nuevo se agrega acá, junto con su constante, y se siembra con RolesSeeder.
     */
    public const NAMES = [
        self::ADMIN => 'admin',
        self::MEDICAL => 'medical',
        self::RECEPTION => 'reception',
    ];

    /**
     * Regla de validación de CI4 con los ids de rol válidos (se arma desde NAMES para no repetirla).
     */
    public static function inListRule(): string
    {
        return 'in_list[' . implode(',', array_keys(self::NAMES)) . ']';
    }

    public static function isValid(int $id): bool
    {
        return isset(self::NAMES[$id]);
    }
}
