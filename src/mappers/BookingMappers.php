<?php

namespace  App\mappers;
use App\dto\BookingRequestDto;
use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use App\Entity\UserEntity;
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
            houseId: $houseId,
            phone: $phone,
            comment: $comment,
            createdAt: $createdAt
        );
    }

    public static function fromBookingRequestDtoToMap(BookingRequestDto $dto): array
    {
        return [
            'id' => isset($dto->id) ? (string)$dto->id : '',
            'house_id' => isset($dto->houseId) ? (string)$dto->houseId : '',
            'phone' => $dto->phone ?? '',
            'comment' => $dto->comment ?? '',
            'created_at' => ($dto->createdAt instanceof DateTimeInterface)
                ? $dto->createdAt->format(DateTimeInterface::ATOM)
                : '',
        ];
    }

    public static function fromDtoToEntity(BookingRequestDto $dto, UserEntity $user, HouseEntity $house): BookingEntity
    {
        $entity = new BookingEntity();
        $entity->setUser($user);
        $entity->setHouse($house);
        $entity->setComment($dto->comment);
        $entity->setDateFrom($dto->dateFrom);
        $entity->setDateTo($dto->dateTo);
        return $entity;
    }

    public static function fromEntityToDto(BookingEntity $entity): BookingRequestDto
    {
        $dto = new BookingRequestDto(
            id: $entity->getId(),
            houseId: $entity->getHouse()?->getId(),
            phone: $entity->getPhone(),
            comment: $entity->getComment(),
            dateFrom: $entity->getDateFrom(),
            dateTo: $entity->getDateTo(),
            createdAt: $entity->getCreatedAt()
        );
        return $dto;
    }
}
