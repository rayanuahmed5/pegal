<?php

namespace App\Domain;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

    /**
     * A user changes their own password. They must know the old one, so a laptop left open
     * is not enough to take over the account.
     *
     * @throws InvalidUser when the current password is wrong or the new one is the same
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new InvalidUser('Your current password is not correct.');
        }

        if (Hash::check($newPassword, $user->password)) {
            throw new InvalidUser('The new password must be different from the current one.');
        }

        $user->update(['password' => $newPassword]);
    }
}
