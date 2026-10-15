<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Departure;
use App\Entity\Line;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\DepartureStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Utilisateurs (mot de passe de dev uniquement) ---
        $admin = (new User())
            ->setEmail('admin@example.com')
            ->setFirstName('Admin')
            ->setLastName('Coopérative')
            ->setPhone('0340000000')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        $traveler = (new User())
            ->setEmail('voyageur@example.com')
            ->setFirstName('Jean')
            ->setLastName('Rakoto')
            ->setPhone('0341111111')
            ->setRoles(['ROLE_USER']);
        $traveler->setPassword($this->hasher->hashPassword($traveler, 'voyageur1234'));
        $manager->persist($traveler);

        // --- Lignes : [origine, destination, prix de base, durée en minutes] ---
        $linesData = [
            ['Ville A', 'Ville B', '25000.00', 240],
            ['Ville A', 'Ville C', '15000.00', 150],
            ['Ville B', 'Ville C', '10000.00', 90],
        ];

        $lines = [];
        foreach ($linesData as [$origin, $destination, $price, $duration]) {
            $line = (new Line())
                ->setOrigin($origin)
                ->setDestination($destination)
                ->setBasePrice($price)
                ->setNormalDuration($duration);
            $manager->persist($line);
            $lines[] = $line;
        }

        // --- Départs : 2 par jour et par ligne, sur 7 jours ---
        $departures = [];
        foreach ($lines as $line) {
            for ($day = 1; $day <= 7; ++$day) {
                foreach (['06:00', '14:00'] as $hour) {
                    $departure = (new Departure())
                        ->setLine($line)
                        ->setDepartureAt(new \DateTimeImmutable("+$day day $hour"))
                        ->setSeats(15)
                        ->setStatus(DepartureStatus::Scheduled);
                    $manager->persist($departure);
                    $departures[] = $departure;
                }
            }
        }

        // Un départ avec un tarif spécial, un départ annulé
        $departures[0]->setPrice('20000.00');
        $departures[1]->setStatus(DepartureStatus::Cancelled);

        // --- Une réservation d'exemple ---
        $departure = $departures[2];
        $persons = 2;
        $unitPrice = $departure->getPrice() ?? $departure->getLine()->getBasePrice();

        $booking = (new Booking())
            ->setCustomer($traveler)
            ->setDeparture($departure)
            ->setNumberOfPersons($persons)
            ->setTotalPrice(number_format((float) $unitPrice * $persons, 2, '.', ''))
            ->setStatus(BookingStatus::Confirmed)
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($booking);

        $manager->flush();
    }
}
