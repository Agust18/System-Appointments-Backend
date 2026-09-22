<?php

namespace App\Dto\Request\Auth;

final class ResetPasswordRequest
{
    public function __construct(
        private string $token,
        private string $password
    ) {}

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    // public static function rules(): array
    // {
    //     return [
    //         'token'    => 'required|string',
    //         'password' => 'required|min_length[8]',
    //     ];
    // }

    // public static function messages(): array
    // {
    //     return [
    //         'token' => [
    //             'required' => 'El token es obligatorio.',
    //         ],
    //         'password' => [
    //             'required'   => 'La contraseña es obligatoria.',
    //             'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
    //         ],
    //     ];
    // }
}