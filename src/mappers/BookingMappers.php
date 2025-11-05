<?php

namespace  App\mappers;
use App\dto\BookingRequestDto;
use DateTimeImmutable;
use DateTimeInterface;

final class BookingMappers
{
    public static function toBookingRequestDto(array $data): BookingRequestDto
    {
        $houseId = isset($data['house_id']) && $data['house_id'] !== '' ? (int)$data['house_id'] : null;
        $phone = isset($data['phone']) ? (string)$data['phone'] : '';
        $comment = isset($data['comment']) ? (string)$data['comment'] : null;

        if (!empty($data['created_at'])) {
            try {
                $createdAt = new DateTimeImmutable($data['created_at']);
            } catch (\Exception $e) {
                // лучше не скрывать ошибки — здесь делаем fallback, но можно и оставить null и позволить валидатору/логике обработать
                $createdAt = new DateTimeImmutable();
            }
        } else {
            $createdAt = new DateTimeImmutable();
        }

        return new BookingRequestDto(
            id: isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null,
            house_id: $houseId,
            phone: $phone,
            comment: $comment,
            created_at: $createdAt
        );
    }

    public static function fromBookingRequestDtoToMap(BookingRequestDto $dto): array
    {
        return [
            'id' => isset($dto->id) ? (string)$dto->id : '',
            'house_id' => isset($dto->house_id) ? (string)$dto->house_id : '',
            'phone' => $dto->phone ?? '',
            'comment' => $dto->comment ?? '',
            'created_at' => ($dto->created_at instanceof DateTimeInterface)
                ? $dto->created_at->format(DateTimeInterface::ATOM)
                : '',
        ];
    }
}
