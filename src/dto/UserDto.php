<?php

namespace App\dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Телефон обязателен')]
        #[Assert\Regex(
            pattern: '/^\+?\d{7,15}$/',
            message: 'Телефон должен содержать от 7 до 15 цифр, опционально с "+" в начале'
        )]
        public string $phone,
        #[Assert\NotBlank(message: 'Имя обязательно')]
        #[Assert\Length(min: 1, max: 100, maxMessage: 'Имя не должно превышать {{ limit }} символов')]
        public string $firstName,
        #[Assert\NotBlank(message: 'Фамилия обязательна')]
        #[Assert\Length(min: 1, max: 100, maxMessage: 'Фамилия не должна превышать {{ limit }} символов')]
        public string $lastName,
        #[Assert\NotBlank(message: 'Email обязателен')]
        #[Assert\Email(message: 'Некорректный email')]
        #[Assert\Length(max: 180, maxMessage: 'Email не должен превышать {{ limit }} символов')]
        public string $email,
    ) {
    }
}
