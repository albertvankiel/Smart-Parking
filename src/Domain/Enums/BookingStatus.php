<?php

namespace App\Domain\Enums;

enum BookingStatus: string 
{
    case BOOKED = 'booked';
    case COMPLETED = 'completed';
}