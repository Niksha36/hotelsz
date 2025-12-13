<?php

declare(strict_types=1);

namespace App\dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterDto
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
        #[Assert\NotBlank(message: 'Пароль обязателен')]
        #[Assert\Regex(
            pattern: '/^[A-Za-z\d\W_]+$/',
            message: 'Пароль должен содержать только английские буквы, цифры и специальные символы'
        )]
        #[Assert\Length(
            min: 8,
            minMessage: 'Пароль должен быть не менее {{ limit }} символов'
        )]
        #[Assert\Regex(
            pattern: '/[A-Z]/',
            message: 'Пароль должен содержать хотя бы одну заглавную букву'
        )]
        #[Assert\Regex(
            pattern: '/[a-z]/',
            message: 'Пароль должен содержать хотя бы одну строчную букву'
        )]
        #[Assert\Regex(
            pattern: '/\d/',
            message: 'Пароль должен содержать хотя бы одну цифру'
        )]
        #[Assert\Regex(
            pattern: '/[\W_]/',
            message: 'Пароль должен содержать хотя бы один специальный символ'
        )]
        public string $password,
        #[Assert\Expression(
            'this.password === this.confirmPassword',
            message: 'Пароли не совпадают :('
        )]
        public string $confirmPassword
    ) {
    }
}
