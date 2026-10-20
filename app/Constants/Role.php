<?php
namespace App\Constants;

final class Role
{
    public const ADMIN = 1;
    public const MEDICAL = 2;
    public const PATIENT = 3;
    public const RECEPTION = 4;

    /**
     * Lista única de roles: id => roles.name. Es la fuente de RolesSeeder y de isValid().
     * Un rol nuevo se agrega acá, junto con su constante, y se siembra con RolesSeeder.
     */
    public const NAMES = [
        self::ADMIN => 'admin',
        self::MEDICAL => 'medical',
        self::PATIENT => 'patient',
        self::RECEPTION => 'reception',
    ];

    public static function isValid(int $id): bool
    {
        return isset(self::NAMES[$id]);
    }
}
