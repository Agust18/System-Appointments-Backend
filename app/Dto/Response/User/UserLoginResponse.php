<?php 

namespace App\Dto\Response\User;

final readonly class UserLoginResponse {
    public function __construct(
        public string $token,
        public UserResponse $user
        
    ) {}
}