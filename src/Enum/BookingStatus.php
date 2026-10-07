<?php

namespace App\Enum;

enum BookingStatus: string
{
    case Pending = 'pending';       // créée, pas encore confirmée
    case Confirmed = 'confirmed';   // validée
    case Cancelled = 'cancelled';   // annulée par le voyageur ou l'admin
}