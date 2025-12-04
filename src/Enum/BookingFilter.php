<?php

declare(strict_types=1);

namespace App\Enum;

enum BookingFilter: string
{
    case CREATED_AT = 'created_at';
    case DATE_FROM = 'date_from';
    case DATE_TO = 'date_to';

    public function getEntityFieldName()
    {
        return match ($this) {
            BookingFilter::CREATED_AT => 'createdAt',
            BookingFilter::DATE_FROM => 'dateFrom',
            BookingFilter::DATE_TO => 'dateTo',
        };
    }
}
