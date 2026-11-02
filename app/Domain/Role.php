<?php

namespace App\Domain;

// the four roles from docs/roles-and-privacy.md
enum Role: string
{
    case Consultant = 'consultant';
    case ProjectLead = 'project_lead';
    case Purchasing = 'purchasing';
    case Finance = 'finance';

    // consultants work for the supplier, everyone else sits on the client side
    public function isClientSide(): bool
    {
        return $this !== self::Consultant;
    }

    // what we show next to the user's name
    public function label(): string
    {
        return match ($this) {
            self::Consultant => 'Consultant',
            self::ProjectLead => 'Project lead',
            self::Purchasing => 'Purchasing',
            self::Finance => 'Finance',
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
        };
    }
}
