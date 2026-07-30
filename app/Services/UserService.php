<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(protected UserRepositoryInterface $users) {}

    public function creer(array $data): User
    {
        return $this->users->create($data);
    }
}
