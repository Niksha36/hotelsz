<?php

declare(strict_types=1);

namespace App\dto;

readonly class LoginDto
{
    public function __construct(
        public string $phone,
        public string $password,
    ) {
    }
}
