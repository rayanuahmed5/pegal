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
}
