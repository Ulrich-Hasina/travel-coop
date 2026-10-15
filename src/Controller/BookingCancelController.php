<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class BookingCancelController extends AbstractController
{
    #[Route('/api/bookings/{id}/cancel', name: 'api_booking_cancel', methods: ['POST'])]
    public function __invoke(Booking $booking, BookingService $bookingService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $booking = $bookingService->cancel($user, $booking);

        return new JsonResponse([
            'id' => $booking->getId(),
            'status' => $booking->getStatus()->value,
        ]);
    }
}
