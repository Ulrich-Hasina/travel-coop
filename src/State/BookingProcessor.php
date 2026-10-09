<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Entity\User;
use App\Service\BookingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<Booking, Booking>
 */
final class BookingProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private BookingService $bookingService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Booking
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        // $data est le Booking désérialisé : on n'en garde que departure et numberOfPersons
        return $this->bookingService->book($user, $data->getDeparture(), $data->getNumberOfPersons());
    }
}