<?php

declare(strict_types=1);

namespace App\Services;

use App\dto\BookingRequestDto;
use App\dto\HouseDto;
use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use App\Entity\UserEntity;
use App\Enum\BookingFilter;
use App\Enum\SortDirection;
use App\mappers\BookingMappers;
use App\mappers\HouseMappers;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class BookingService
{
    private BookingRepository $bookingRepository;
    private UserRepository $userRepository;
    private HouseRepository $houseRepository;
    public function __construct(BookingRepository $bookingRepository, UserRepository $userRepository, HouseRepository $houseRepository)
    {
        $this->bookingRepository = $bookingRepository;
        $this->userRepository = $userRepository;
        $this->houseRepository = $houseRepository;
    }

    public function saveBooking(BookingRequestDto $bookingDto): BookingRequestDto
    {
        if ($this->bookingRepository->isHouseBookedForThePeriod(houseId: $bookingDto->houseId, startDate: $bookingDto->dateFrom, endDate: $bookingDto->dateTo)) {
            throw new RuntimeException('House is already booked for the selected period.');
        }
        $user = $this->userRepository->findOneBy(['phone' => $bookingDto->phone]);
        if (!$user instanceof UserEntity) {
            throw new RuntimeException('UserEntity with the given phone number does not exist. Please register first.');
        }
        $house = $this->houseRepository->find($bookingDto->houseId);
        if (!$house instanceof HouseEntity) {
            throw new RuntimeException('HouseEntity with the given ID does not exist.');
        }
        $entity = BookingMappers::fromDtoToEntity($bookingDto, $user, $house);
        $savedEntity = $this->bookingRepository->save($entity);
        return BookingMappers::fromEntityToDto($savedEntity);
    }

    public function deleteBooking(int $bookingId): void
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking instanceof BookingEntity) {
            throw new RuntimeException('Booking not found.');
        }

        $this->bookingRepository->remove($booking);
    }

    /**
    *
    * @return HouseDto[]
    */
    public function getHousesAvailableForThePeriod(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        if ($endDate <= $startDate) {
            throw new RuntimeException('End date must be after start date.');
        }
        $houses = $this->bookingRepository->getHousesAvailableForThePeriod($startDate, $endDate);
        return array_map(fn (HouseEntity $entity) => HouseMappers::fromEntityToDto($entity), $houses);
    }

    public function getBookingsFilteredBy(?BookingFilter $fieldEnum, ?SortDirection $direction): array
    {
        if ($fieldEnum === null) {
            $allowed = implode(', ', array_map(fn ($case) => $case->value, BookingFilter::cases()));
            throw new InvalidArgumentException(sprintf('Не указано поле для фильтрации. Допустимые значения: %s', $allowed));
        }

        if ($direction === null) {
            $allowed = implode(', ', array_map(fn ($case) => $case->value, SortDirection::cases()));
            throw new InvalidArgumentException(sprintf('Не указано направление сортировки. Допустимые значения: %s', $allowed));
        }

        $orderBy = [
            $fieldEnum->getEntityFieldName() => $direction->name
        ];

        $entities = $this->houseRepository->findBy([], $orderBy);
        return array_map(fn ($entity) => HouseMappers::fromEntityToDto($entity), $entities);
    }
}
