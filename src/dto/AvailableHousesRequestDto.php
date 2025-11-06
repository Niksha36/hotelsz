<?php

namespace App\dto;

use DateTimeImmutable;

readonly class AvailableHousesRequestDto
{
    public function __construct(
        public DateTimeImmutable $dateFrom,
        public DateTimeImmutable $dateTo
    ) {}
}
