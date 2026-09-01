<?php
namespace App\Dto\Request\User;
final readonly class UserRequest{
    public function __construct(
        private string $username,
        private string $email,
        private string $password,
        private int $dni,
        private bool $isActive, 
        private ?int $roleId 
       
    ){}

    public function GetUsername():string
    {
        return $this->username;
    }
    
    public function GetEmail():string
    {
        return $this->email;

    }
    public function GetPassword():string
    {
        return $this->password;
    }
    public function GetDni():int
    {
        return $this->dni;
    }

    public function GetRoleId():?int
    {
        return $this->roleId;
    }
    public function getIsActive(): bool
    {
        return $this->isActive;
    }
    
    

}

