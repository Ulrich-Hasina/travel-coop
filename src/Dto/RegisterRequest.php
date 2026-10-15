<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
        public string $email = '',

        #[Assert\NotBlank, Assert\Length(min: 8, max: 255)]
        public string $password = '',

        #[Assert\NotBlank, Assert\Length(max: 100)]
        public string $firstName = '',

        #[Assert\NotBlank, Assert\Length(max: 100)]
        public string $lastName = '',

        #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
        #[Assert\Regex(
            pattern: '/^\+?[0-9 ]{8,20}$/',
            message: 'Numéro de téléphone invalide.'
        )]
        public string $phone = '',
    ) {
    }
}
