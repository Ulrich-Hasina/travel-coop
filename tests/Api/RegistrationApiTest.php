<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationApiTest extends ApiTestCase
{
    public function testRegistrationNeverGrantsAdminRole(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', ['json' => [
            'email' => 'new@test.com',
            'password' => 'secret1234',
            'firstName' => 'Aina',
            'lastName' => 'Rabe',
            'phone' => '0342222222',
            'roles' => ['ROLE_ADMIN'],
        ]]);

        $this->assertResponseStatusCodeSame(201);

        $user = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => 'new@test.com']);
        $this->assertNotNull($user);
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testRegistrationRefusesDuplicateEmail(): void
    {
        $client = static::createClient();
        $payload = [
            'email' => 'dup@test.com',
            'password' => 'secret1234',
            'firstName' => 'A',
            'lastName' => 'B',
            'phone' => '0342222222',
        ];

        $client->request('POST', '/api/register', ['json' => $payload]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/register', ['json' => $payload]);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegistrationValidatesInput(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', ['json' => [
            'email' => 'pas-un-email',
            'password' => 'abc',
            'firstName' => 'A',
            'lastName' => 'B',
            'phone' => '0342222222',
        ]]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegistrationRequiresPhone(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', ['json' => [
            'email' => 'nophone@test.com',
            'password' => 'secret1234',
            'firstName' => 'A',
            'lastName' => 'B',
        ]]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisteredUserCanLogIn(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', ['json' => [
            'email' => 'login@test.com',
            'password' => 'secret1234',
            'firstName' => 'A',
            'lastName' => 'B',
            'phone' => '0342222222',
        ]]);
        $this->assertResponseStatusCodeSame(201);

        $response = $client->request('POST', '/api/login_check', [
            'json' => ['email' => 'login@test.com', 'password' => 'secret1234'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $response->toArray());
    }
}