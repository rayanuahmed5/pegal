<?php

namespace App\Domain;

use DomainException;

// someone asked for a follow-up proposal for an order that does not need one
class NoProposalNeeded extends DomainException
{
}
