<?php

declare(strict_types=1);

namespace App\dto;

use DateTimeImmutable;

readonly class AvailableHousesRequestDto
{
    public function __construct(
        public DateTimeImmutable $dateFrom,
        public DateTimeImmutable $dateTo
    ) {
    }
}
