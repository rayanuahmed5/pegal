<?php

namespace App\Domain;

use DomainException;

// the administrator tried to create an account that breaks a rule
class InvalidUser extends DomainException
{
}
