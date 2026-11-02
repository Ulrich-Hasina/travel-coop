<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Entity\Line;
use Doctrine\ORM\EntityManagerInterface;

class LineApiTest extends ApiTestCase
{
    public function testLinesArePublic(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $line = (new Line())
            ->setOrigin('Test A')
            ->setDestination('Test B')
            ->setBasePrice('1000.00')
            ->setNormalDuration(60);
        $em->persist($line);
        $em->flush();

        $client->request('GET', '/api/lines', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['totalItems' => 1]);
    }
}
