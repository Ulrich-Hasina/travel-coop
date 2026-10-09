<?php

namespace App\Tests\Entity;

use App\Entity\Booking;
use App\Entity\Departure;
use App\Enum\BookingStatus;
use PHPUnit\Framework\TestCase;

class DepartureTest extends TestCase
{
    public function testAvailableSeatsIgnoreCancelledBookings(): void
    {
        $departure = (new Departure())->setSeats(15);
        $departure->addBooking((new Booking())->setNumberOfPersons(2)->setStatus(BookingStatus::Confirmed));
        $departure->addBooking((new Booking())->setNumberOfPersons(5)->setStatus(BookingStatus::Cancelled));

        $this->assertSame(13, $departure->getAvailableSeats());
    }

    public function testAvailableSeatsNeverNegative(): void
    {
        $departure = (new Departure())->setSeats(3);
        $departure->addBooking((new Booking())->setNumberOfPersons(5)->setStatus(BookingStatus::Confirmed));

        $this->assertSame(0, $departure->getAvailableSeats());
    }
}