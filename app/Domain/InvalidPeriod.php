<?php

namespace App\Domain;

use DomainException;

// thrown when a month or year filter makes no sense, the message is meant to be shown as it is
class InvalidPeriod extends DomainException
{
}
