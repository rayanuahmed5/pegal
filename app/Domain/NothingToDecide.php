<?php

namespace App\Domain;

use DomainException;

// finance tried to approve or reject hours that are not waiting for a decision (any more)
class NothingToDecide extends DomainException
{
}
