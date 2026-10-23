<?php

namespace App\Controller;

use App\Dto\RegisterRequest;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RegisterRequest $data,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = (new User())
            ->setEmail(mb_strtolower(trim($data->email)))
            ->setFirstName(trim($data->firstName))
            ->setLastName(trim($data->lastName))
            ->setPhone($data->phone)
            ->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $data->password));

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(['message' => 'Un compte existe déjà avec cet email.'], 409);
        }

        return new JsonResponse(['id' => $user->getId(), 'email' => $user->getEmail()], 201);
    }
}
