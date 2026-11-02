<?php

namespace App\Domain;

use DomainException;

// thrown when a booking breaks one of the rules, the message is written for the person who booked
class BookingRejected extends DomainException
{
}
