<?php

namespace App\Domain;

// the roles from docs/roles-and-privacy.md: four for the work, one administrator for the accounts
enum Role: string
{
    case Consultant = 'consultant';
    case ProjectLead = 'project_lead';
    case Purchasing = 'purchasing';
    case Finance = 'finance';
    case Admin = 'admin';

    // consultants work for the supplier, the other work roles sit on the client side
    // the administrator looks after accounts and belongs to neither side
    public function isClientSide(): bool
    {
        return $this !== self::Consultant && $this !== self::Admin;
    }

    // what we show next to the user's name
    public function label(): string
    {
        return match ($this) {
            self::Consultant => 'Consultant',
            self::ProjectLead => 'Project lead',
            self::Purchasing => 'Purchasing',
            self::Finance => 'Finance',
            self::Admin => 'Administrator',
        };
    }

    // one sentence on what this person comes here for
    public function intro(): string
    {
        return match ($this) {
            self::Consultant => 'Book your working time on the orders you are assigned to.',
            self::ProjectLead => 'See how much budget your orders have left and when it runs out.',
            self::Purchasing => 'See which orders need a follow-up order, and by when.',
            self::Finance => 'Check the monthly proof of work per order before the invoice is paid.',
            self::Admin => 'See all accounts with their roles and person-days, and add new users.',
        };
    }
}
