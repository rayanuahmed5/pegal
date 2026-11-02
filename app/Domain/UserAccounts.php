<?php

namespace App\Domain;

use App\Models\Supplier;
use App\Models\User;

// creating accounts, the one place that knows which role needs what
class UserAccounts
{
    /**
     * @throws InvalidUser when a consultant has no (known) supplier
     */
    public function create(string $name, string $email, Role $role, ?int $supplierId, string $password): User
    {
        // a consultant works for a supplier, the client roles and the administrator have none
        if ($role === Role::Consultant) {
            if ($supplierId === null || ! Supplier::whereKey($supplierId)->exists()) {
                throw new InvalidUser('A consultant must belong to a supplier, please choose one.');
            }
        } else {
            $supplierId = null;
        }

        // the password is hashed by the cast on the User model
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'supplier_id' => $supplierId,
        ]);
    }
}
