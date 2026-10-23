<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use App\Entity\Departure;
use App\Entity\Line;
use App\Entity\User;
use App\Enum\DepartureStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BookingApiTest extends ApiTestCase
{
    private const JSONLD = 'application/ld+json';

    public function testBookingSucceedsWithServerComputedPrice(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 15);

        $this->book($client, $this->token($client, 'a@test.com'), $departure, 2);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['totalPrice' => '50000.00', 'status' => 'confirmed']);
    }

    public function testBookingRefusedWhenDepartureIsFull(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 3);
        $token = $this->token($client, 'a@test.com');

        $this->book($client, $token, $departure, 2);
        $this->assertResponseStatusCodeSame(201);

        $this->book($client, $token, $departure, 2);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testBookingRefusedWhenDepartureIsCancelled(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 15, status: DepartureStatus::Cancelled);

        $this->book($client, $this->token($client, 'a@test.com'), $departure, 1);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testBookingRequiresAuthentication(): void
    {
        $client = static::createClient();
        $departure = $this->createDeparture(seats: 15);

        $client->request('POST', '/api/bookings', [
            'headers' => ['Content-Type' => self::JSONLD, 'Accept' => self::JSONLD],
            'json' => ['departure' => '/api/departures/'.$departure->getId(), 'numberOfPersons' => 1],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTravelerCannotReadAnotherTravelersBooking(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $this->createUser('b@test.com');
        $departure = $this->createDeparture(seats: 15);

        $created = $this->book($client, $this->token($client, 'a@test.com'), $departure, 1)->toArray();

        $client->request('GET', $created['@id'], [
            'auth_bearer' => $this->token($client, 'b@test.com'),
            'headers' => ['Accept' => self::JSONLD],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createUser(string $email): User
    {
        $container = static::getContainer();
        $user = (new User())
            ->setEmail($email)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setPhone('0340000000')
            ->setRoles(['ROLE_USER']);
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'secret1234'));

        $em = $container->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createDeparture(
        int $seats,
        DepartureStatus $status = DepartureStatus::Scheduled,
        ?\DateTimeImmutable $departureAt = null,
    ): Departure {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $line = (new Line())
            ->setOrigin('Test A')
            ->setDestination('Test B')
            ->setBasePrice('25000.00')
            ->setNormalDuration(120);
        $em->persist($line);

        $departure = (new Departure())
            ->setLine($line)
            ->setDepartureAt($departureAt ?? new \DateTimeImmutable('+2 days'))
            ->setSeats($seats)
            ->setStatus($status);
        $em->persist($departure);
        $em->flush();

        return $departure;
    }

    private function token(Client $client, string $email): string
    {
        return $client->request('POST', '/api/login_check', [
            'json' => ['email' => $email, 'password' => 'secret1234'],
        ])->toArray()['token'];
    }

    private function book(Client $client, string $token, Departure $departure, int $persons): ResponseInterface
    {
        return $client->request('POST', '/api/bookings', [
            'auth_bearer' => $token,
            'headers' => ['Content-Type' => self::JSONLD, 'Accept' => self::JSONLD],
            'json' => ['departure' => '/api/departures/'.$departure->getId(), 'numberOfPersons' => $persons],
        ]);
    }

    private function cancel(Client $client, string $token, string $bookingIri): ResponseInterface
    {
        return $client->request('POST', $bookingIri.'/cancel', [
            'auth_bearer' => $token,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    public function testBookingRefusedWhenDepartureIsInThePast(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 15, departureAt: new \DateTimeImmutable('-1 hour'));

        $this->book($client, $this->token($client, 'a@test.com'), $departure, 1);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testBookingRefusedWithInvalidNumberOfPersons(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 15);
        $token = $this->token($client, 'a@test.com');

        $this->book($client, $token, $departure, 0);
        $this->assertResponseStatusCodeSame(422);

        $this->book($client, $token, $departure, -3);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testTravelerOnlySeesOwnBookings(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $this->createUser('b@test.com');
        $departure = $this->createDeparture(seats: 15);

        $this->book($client, $this->token($client, 'a@test.com'), $departure, 1);
        $this->book($client, $this->token($client, 'b@test.com'), $departure, 1);

        $client->request('GET', '/api/bookings', [
            'auth_bearer' => $this->token($client, 'a@test.com'),
            'headers' => ['Accept' => self::JSONLD],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 1]);
    }

    public function testTravelerCanReadOwnBooking(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 15);
        $token = $this->token($client, 'a@test.com');

        $created = $this->book($client, $token, $departure, 1)->toArray();

        $client->request('GET', $created['@id'], [
            'auth_bearer' => $token,
            'headers' => ['Accept' => self::JSONLD],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');

        $client->request('POST', '/api/login_check', [
            'json' => ['email' => 'a@test.com', 'password' => 'mauvais'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testOwnerCanCancelAndSeatsAreReleased(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 5);
        $token = $this->token($client, 'a@test.com');
        $created = $this->book($client, $token, $departure, 3)->toArray();

        $this->cancel($client, $token, $created['@id']);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'cancelled']);

        $client->request('GET', '/api/departures/'.$departure->getId(), [
            'headers' => ['Accept' => self::JSONLD],
        ]);
        $this->assertJsonContains(['availableSeats' => 5]);
    }

    public function testOtherTravelerCannotCancel(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $this->createUser('b@test.com');
        $departure = $this->createDeparture(seats: 5);
        $created = $this->book($client, $this->token($client, 'a@test.com'), $departure, 1)->toArray();

        $this->cancel($client, $this->token($client, 'b@test.com'), $created['@id']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCancellingTwiceIsRefused(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 5);
        $token = $this->token($client, 'a@test.com');
        $created = $this->book($client, $token, $departure, 1)->toArray();

        $this->cancel($client, $token, $created['@id']);
        $this->assertResponseIsSuccessful();

        $this->cancel($client, $token, $created['@id']);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testCancellationTooCloseToDepartureIsRefused(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 5, departureAt: new \DateTimeImmutable('+1 hour'));
        $token = $this->token($client, 'a@test.com');
        $created = $this->book($client, $token, $departure, 1)->toArray();

        $this->cancel($client, $token, $created['@id']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCancellationRequiresAuthentication(): void
    {
        $client = static::createClient();
        $this->createUser('a@test.com');
        $departure = $this->createDeparture(seats: 5);
        $created = $this->book($client, $this->token($client, 'a@test.com'), $departure, 1)->toArray();

        $client->request('POST', $created['@id'].'/cancel', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
