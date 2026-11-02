<?php

namespace Tests\Unit;

use App\Domain\Role;
use PHPUnit\Framework\TestCase;

// plain PHPUnit, no database needed for these
class RoleTest extends TestCase
{
    public function test_only_the_consultant_is_on_the_supplier_side(): void
    {
        $this->assertFalse(Role::Consultant->isClientSide());

        foreach ([Role::ProjectLead, Role::Purchasing, Role::Finance] as $role) {
            $this->assertTrue($role->isClientSide());
        }
    }

    public function test_the_administrator_is_on_neither_side(): void
    {
        $this->assertFalse(Role::Admin->isClientSide());
        $this->assertSame('Administrator', Role::Admin->label());
    }

    public function test_every_role_has_a_label_and_an_intro(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertNotSame('', $role->label());
            $this->assertNotSame('', $role->intro());
        }
    }

    public function test_a_role_can_be_built_from_its_stored_value(): void
    {
        $this->assertSame(Role::ProjectLead, Role::from('project_lead'));
    }
}
