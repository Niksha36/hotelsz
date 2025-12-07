<?php

namespace App\Tests\services;

use App\Services\BookingService;
use App\Infrastructure\Persistence\BookingRepository;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\HouseRepository;
use App\dto\BookingRequestDto;
use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use App\Entity\UserEntity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BookingServiceTest extends TestCase
{
    private function makeHouse(int $id): HouseEntity
    {
        $h = new HouseEntity();
        $h->setName('H')->setType('villa')->setBeds(2)->setRowFromSea(1)->setPricePerNightRub(1000);
        $ref = new \ReflectionClass($h);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($h, $id);
        return $h;
    }

    private function makeUser(string $phone = '+70000000000'): UserEntity
    {
        return new UserEntity($phone, 'F', 'L', 'f@example.com');
    }

    private function makeBookingEntity(int $id, HouseEntity $house, UserEntity $user): BookingEntity
    {
        $b = new BookingEntity();
        $b->setHouse($house)->setUser($user)->setComment('c');
        $ref = new \ReflectionClass($b);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($b, $id);
        return $b;
    }

    public function testSaveBookingSuccess(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingRepo->expects($this->once())
            ->method('isHouseBookedForThePeriod')
            ->willReturn(false);

        $userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+7999'])
            ->willReturn($this->makeUser('+7999'));

        $houseRepo->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->makeHouse(10));

        $bookingRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($entity) {
                return $entity instanceof BookingEntity && $entity->getComment() === 'wow';
            }))
            ->willReturnCallback(function (BookingEntity $e) {
                $ref = new \ReflectionClass($e);
                $prop = $ref->getProperty('id');
                $prop->setAccessible(true);
                $prop->setValue($e, 55);
                return $e;
            });

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $dto = new BookingRequestDto(
            id: null,
            houseId: 10,
            phone: '+7999',
            comment: 'wow',
            dateFrom: new DateTimeImmutable('2025-06-01'),
            dateTo: new DateTimeImmutable('2025-06-10'),
            createdAt: new DateTimeImmutable()
        );

        $saved = $service->saveBooking($dto);
        $this->assertInstanceOf(BookingRequestDto::class, $saved);
        $this->assertEquals(55, $saved->id);
        $this->assertEquals(10, $saved->houseId);
        $this->assertEquals('wow', $saved->comment);
    }

    public function testSaveBookingThrowsWhenHouseBooked(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingRepo->method('isHouseBookedForThePeriod')->willReturn(true);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $dto = new BookingRequestDto(id: null, houseId: 1, phone: '+7', comment: null, dateFrom: new DateTimeImmutable('2025-01-01'), dateTo: new DateTimeImmutable('2025-01-02'), createdAt: new DateTimeImmutable());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('House is already booked for the selected period.');
        $service->saveBooking($dto);
    }

    public function testSaveBookingThrowsWhenUserNotFound(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingRepo->method('isHouseBookedForThePeriod')->willReturn(false);
        $userRepo->method('findOneBy')->willReturn(null);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $dto = new BookingRequestDto(id: null, houseId: 1, phone: '+7', comment: null, dateFrom: new DateTimeImmutable('2025-01-01'), dateTo: new DateTimeImmutable('2025-01-02'), createdAt: new DateTimeImmutable());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UserEntity with the given phone number does not exist. Please register first.');
        $service->saveBooking($dto);
    }

    public function testSaveBookingThrowsWhenHouseNotFound(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingRepo->method('isHouseBookedForThePeriod')->willReturn(false);
        $userRepo->method('findOneBy')->willReturn($this->makeUser('+7'));
        $houseRepo->method('find')->willReturn(null);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $dto = new BookingRequestDto(id: null, houseId: 1, phone: '+7', comment: null, dateFrom: new DateTimeImmutable('2025-01-01'), dateTo: new DateTimeImmutable('2025-01-02'), createdAt: new DateTimeImmutable());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HouseEntity with the given ID does not exist.');
        $service->saveBooking($dto);
    }

    public function testDeleteBookingSuccess(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingEntity = $this->makeBookingEntity(9, $this->makeHouse(2), $this->makeUser('+71'));

        $bookingRepo->method('find')->with(9)->willReturn($bookingEntity);
        $bookingRepo->expects($this->once())->method('remove')->with($bookingEntity);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $service->deleteBooking(9);
        $this->assertTrue(true); // reached without exception
    }

    public function testDeleteBookingThrowsWhenNotFound(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $bookingRepo->method('find')->willReturn(null);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Booking not found.');
        $service->deleteBooking(123);
    }

    public function testGetHousesAvailableForThePeriodSuccess(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $house1 = $this->makeHouse(1);
        $house2 = $this->makeHouse(2);

        $bookingRepo->expects($this->once())
            ->method('getHousesAvailableForThePeriod')
            ->willReturn([$house1, $house2]);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $start = new DateTimeImmutable('2025-07-01');
        $end = new DateTimeImmutable('2025-07-10');
        $houses = $service->getHousesAvailableForThePeriod($start, $end);
        $this->assertCount(2, $houses);
        $this->assertEquals(1, $houses[0]->id);
    }

    public function testGetHousesAvailableForThePeriodThrowsOnInvalidDates(): void
    {
        $bookingRepo = $this->createMock(BookingRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $houseRepo = $this->createMock(HouseRepository::class);

        $service = new BookingService($bookingRepo, $userRepo, $houseRepo);
        $start = new DateTimeImmutable('2025-08-01');
        $end = new DateTimeImmutable('2025-08-01'); // not after start

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('End date must be after start date.');
        $service->getHousesAvailableForThePeriod($start, $end);
    }
}
