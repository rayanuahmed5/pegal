<?php

namespace App\Domain;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// creating, changing and removing accounts, the one place that knows which role needs what
class UserAccounts
{
    /**
     * @throws InvalidUser when a consultant has no (known) supplier
     */
    public function create(string $name, string $email, Role $role, ?int $supplierId, string $password): User
    {
        // the password is hashed by the cast on the User model
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'supplier_id' => $this->supplierFor($role, $supplierId),
        ]);
    }

    /**
     * The administrator changes somebody's name, email, role or supplier.
     *
     * @throws InvalidUser when a rule below is broken
     */
    public function update(User $user, string $name, string $email, Role $role, ?int $supplierId, User $admin): User
    {
        // otherwise an admin could lock themselves out, and maybe leave nobody to look after accounts
        if ($user->is($admin) && $role !== $user->role) {
            throw new InvalidUser('You cannot change your own role. Ask another administrator.');
        }

        // bookings are proof of work for a consultant, a different role would make them meaningless
        if ($user->role === Role::Consultant && $role !== Role::Consultant && $user->bookings()->exists()) {
            throw new InvalidUser(sprintf('%s already has bookings, so the role must stay Consultant.', $user->name));
        }

        $user->update([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'supplier_id' => $this->supplierFor($role, $supplierId),
        ]);

        return $user;
    }

    /**
     * The administrator sets a new password, for example when somebody forgot theirs.
     * No old password needed here, that is the point of a reset.
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);
    }

    /**
     * The administrator removes an account. Assignments and planned absences go with it.
     *
     * @throws InvalidUser when the admin deletes themselves or the user has bookings
     */
    public function delete(User $user, User $admin): void
    {
        if ($user->is($admin)) {
            throw new InvalidUser('You cannot delete your own account.');
        }

        // bookings are proof of work behind invoices and must be kept (see roles-and-privacy.md)
        $bookings = $user->bookings()->count();
        if ($bookings > 0) {
            throw new InvalidUser(sprintf(
                '%s has %d %s. They are proof of work for invoices, so the account cannot be deleted.',
                $user->name,
                $bookings,
                $bookings === 1 ? 'booking' : 'bookings',
            ));
        }

        $user->delete();
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

    /**
     * A consultant works for a supplier, the client roles and the administrator have none.
     *
     * @throws InvalidUser when a consultant has no (known) supplier
     */
    private function supplierFor(Role $role, ?int $supplierId): ?int
    {
        if ($role !== Role::Consultant) {
            return null;
        }

        if ($supplierId === null || ! Supplier::whereKey($supplierId)->exists()) {
            throw new InvalidUser('A consultant must belong to a supplier, please choose one.');
        }

        return $supplierId;
    }
}
