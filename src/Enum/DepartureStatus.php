<?php

namespace App\Enum;

enum DepartureStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}