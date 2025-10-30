<?php

namespace App\dto;

// src/Dto/BookingRequestDto.php
namespace App\dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class BookingRequestDto
{
    public function __construct(

        public ?int $id = null,

        #[Assert\NotNull(message: "house_id is required")]
        #[Assert\Positive(message: "house_id must be a positive integer")]
        public ?int $house_id,

        #[Assert\NotBlank(message: "phone is required")]
        #[Assert\Length(
            max: 13,
            maxMessage: "phone must be at most {{ limit }} characters long"
        )]

        #[Assert\Regex(
            pattern: '/^\+?\d{1,15}$/',
            message: "phone may contain only digits and optional leading +, up to 15 chars"
        )]
        public string $phone,

        #[Assert\Length(
            max: 9,
            maxMessage: "comment must be at most {{ limit }} characters long"
        )]
        public ?string $comment = null,

        public ?\DateTimeImmutable $created_at = null,
    ) {}
}
