<?php

namespace App\Services;

use App\Events\UserCreated;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(protected UserRepositoryInterface $users) {}

    public function creer(array $data): User
    {
        $user = $this->users->create($data);

        UserCreated::dispatch($user);

        return $user;
    }

    public function modifier(User $user, array $data): User
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $this->users->update($user, $data);
    }

    public function desactiver(User $admin, User $cible): User
    {
        if ($admin->id === $cible->id) {
            throw ValidationException::withMessages([
                'utilisateur' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ]);
        }

        return $this->users->update($cible, ['is_active' => false]);
    }
}
