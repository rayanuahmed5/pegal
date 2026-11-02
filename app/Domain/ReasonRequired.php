<?php

namespace App\Domain;

use DomainException;

// finance tried to reject hours without telling the consultant why
class ReasonRequired extends DomainException
{
}
