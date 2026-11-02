<?php

namespace App\Domain;

// where a booking is in the approval flow
enum BookingStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
