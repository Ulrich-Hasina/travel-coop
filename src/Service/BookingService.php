<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Departure;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\DepartureStatus;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BookingService
{
    private const MAX_PERSONS_PER_BOOKING = 10;
    private const CANCELLATION_DEADLINE_HOURS = 2;

    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $bookingRepository,
    ) {
    }

    public function book(User $customer, Departure $departure, int $persons): Booking
    {
        if ($persons < 1 || $persons > self::MAX_PERSONS_PER_BOOKING) {
            throw new UnprocessableEntityHttpException(
                sprintf('Le nombre de personnes doit être entre 1 et %d.', self::MAX_PERSONS_PER_BOOKING)
            );
        }

        return $this->em->wrapInTransaction(function () use ($customer, $departure, $persons): Booking {
            // 1. Verrouiller la ligne du départ et relire son état à jour.
            //    Une autre réservation simultanée sur ce départ attendra ici.
            $this->em->refresh($departure, LockMode::PESSIMISTIC_WRITE);

            // 2. Règles métier
            if (DepartureStatus::Scheduled !== $departure->getStatus()) {
                throw new UnprocessableEntityHttpException('Ce départ n\'est plus ouvert à la réservation.');
            }
            if ($departure->getDepartureAt() <= new \DateTimeImmutable()) {
                throw new UnprocessableEntityHttpException('Ce départ est déjà passé.');
            }

            $taken = $this->bookingRepository->countSeatsTaken($departure);
            $remaining = ($departure->getSeats() ?? 0) - $taken;
            if ($persons > $remaining) {
                throw new ConflictHttpException(
                    sprintf('Il ne reste que %d place(s) sur ce départ.', max(0, $remaining))
                );
            }

            // 3. Prix calculé côté serveur et figé dans la réservation
            $unitPrice = $departure->getEffectivePrice();
            if (null === $unitPrice) {
                throw new UnprocessableEntityHttpException('Aucun tarif disponible pour ce départ.');
            }
            $total = number_format((float) $unitPrice * $persons, 2, '.', '');

            $booking = (new Booking())
                ->setCustomer($customer)
                ->setDeparture($departure)
                ->setNumberOfPersons($persons)
                ->setTotalPrice($total)
                ->setStatus(BookingStatus::Confirmed)
                ->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($booking);

            return $booking; // wrapInTransaction fait le flush puis le commit
        });
    }

    public function cancel(User $user, Booking $booking): Booking
    {
        if ($booking->getCustomer() !== $user) {
            throw new AccessDeniedHttpException();
        }
        if (BookingStatus::Cancelled === $booking->getStatus()) {
            throw new ConflictHttpException('Cette réservation est déjà annulée.');
        }

        $limit = $booking->getDeparture()->getDepartureAt()
            ->modify(sprintf('-%d hours', self::CANCELLATION_DEADLINE_HOURS));
        if (new \DateTimeImmutable() > $limit) {
            throw new UnprocessableEntityHttpException(
                sprintf('Annulation impossible moins de %d heures avant le départ.', self::CANCELLATION_DEADLINE_HOURS)
            );
        }

        $booking->setStatus(BookingStatus::Cancelled);
        $this->em->flush();

        return $booking;
    }
}