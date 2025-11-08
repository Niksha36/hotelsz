<?php

namespace App\Tests\api;

use App\Services\BookingService;
use App\dto\BookingRequestDto;
use App\dto\HouseDto;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingControllerTest extends WebTestCase
{
    public function testBookHouseSuccess(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $requestData = [
            'houseId' => 10,
            'phone' => '+79998887766',
            'comment' => 'test',
            'dateFrom' => '2025-11-10T00:00:00+00:00',
            'dateTo' => '2025-11-12T00:00:00+00:00'
        ];

        $expectedDto = new BookingRequestDto(
            id: 1,
            houseId: $requestData['houseId'],
            phone: $requestData['phone'],
            comment: $requestData['comment'],
            dateFrom: new DateTimeImmutable($requestData['dateFrom']),
            dateTo: new DateTimeImmutable($requestData['dateTo']),
            createdAt: new DateTimeImmutable()
        );

        $bookingServiceMock->expects($this->once())
            ->method('saveBooking')
            ->with($this->callback(function (BookingRequestDto $dto) use ($requestData) {
                return $dto->houseId === $requestData['houseId']
                    && $dto->phone === $requestData['phone']
                    && $dto->comment === $requestData['comment']
                    && $dto->dateFrom instanceof DateTimeImmutable
                    && $dto->dateTo instanceof DateTimeImmutable;
            }))
            ->willReturn($expectedDto);

        $client->request('POST', '/booking', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($expectedDto->id, $responseData['id']);
        $this->assertEquals($expectedDto->houseId, $responseData['houseId']);
        $this->assertEquals($expectedDto->phone, $responseData['phone']);
        $this->assertEquals($expectedDto->comment, $responseData['comment']);
    }

    public function testBookHouseRuntimeException(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $requestData = [
            'houseId' => 10,
            'phone' => '+79998887766',
            'comment' => 'test',
            'dateFrom' => '2025-11-10T00:00:00+00:00',
            'dateTo' => '2025-11-12T00:00:00+00:00'
        ];

        $bookingServiceMock->expects($this->once())
            ->method('saveBooking')
            ->willThrowException(new RuntimeException('House is already booked for the selected period.'));

        $client->request('POST', '/booking', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('House is already booked', $content);
    }

    public function testUpdateBookingSuccess(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $requestData = [
            'id' => 5,
            'houseId' => 22,
            'phone' => '+78889990011',
            'comment' => 'upd',
            'dateFrom' => '2025-12-01T00:00:00+00:00',
            'dateTo' => '2025-12-05T00:00:00+00:00'
        ];

        $expectedDto = new BookingRequestDto(
            id: $requestData['id'],
            houseId: $requestData['houseId'],
            phone: $requestData['phone'],
            comment: $requestData['comment'],
            dateFrom: new DateTimeImmutable($requestData['dateFrom']),
            dateTo: new DateTimeImmutable($requestData['dateTo']),
            createdAt: new DateTimeImmutable()
        );

        $bookingServiceMock->expects($this->once())
            ->method('saveBooking')
            ->with($this->callback(function (BookingRequestDto $dto) use ($requestData) {
                return $dto->id === $requestData['id']
                    && $dto->houseId === $requestData['houseId']
                    && $dto->phone === $requestData['phone'];
            }))
            ->willReturn($expectedDto);

        $client->request('PUT', '/booking/' . $requestData['id'], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($expectedDto->id, $responseData['id']);
        $this->assertEquals($expectedDto->houseId, $responseData['houseId']);
    }

    public function testUpdateBookingIdMismatch(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $requestData = [
            'id' => 10,
            'houseId' => 22,
            'phone' => '+74445556677',
            'comment' => 'mismatch',
            'dateFrom' => '2025-12-01T00:00:00+00:00',
            'dateTo' => '2025-12-05T00:00:00+00:00'
        ];

        $bookingServiceMock->expects($this->never())->method('saveBooking');

        $client->request('PUT', '/booking/9', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('ID in the path and payload do not match', $content);
    }

    public function testDeleteBookingSuccess(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $bookingServiceMock->expects($this->once())
            ->method('deleteBooking')
            ->with(15);

        $client->request('DELETE', '/booking/15');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Booking deleted successfully', $responseData['message']);
    }

    public function testDeleteBookingRuntimeException(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $bookingServiceMock->expects($this->once())
            ->method('deleteBooking')
            ->with(99)
            ->willThrowException(new RuntimeException('Booking not found.'));

        $client->request('DELETE', '/booking/99');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Booking not found', $content);
    }

    public function testGetAvailableHousesSuccess(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $dateFrom = new DateTimeImmutable('2025-07-01T00:00:00+00:00');
        $dateTo = new DateTimeImmutable('2025-07-10T00:00:00+00:00');

        $houses = [
            new HouseDto(1, 'House 1', 'Type A', 2, 3, 5000.0),
            new HouseDto(2, 'House 2', 'Type B', 4, 1, 12000.0),
        ];

        $bookingServiceMock->expects($this->once())
            ->method('getHousesAvailableForThePeriod')
            ->with($this->callback(fn(DateTimeImmutable $d) => $d == $dateFrom), $this->callback(fn(DateTimeImmutable $d) => $d == $dateTo))
            ->willReturn($houses);

        $client->request(
            'GET',
            '/booking/booking/available',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dateFrom' => $dateFrom->format(DateTimeImmutable::ATOM),
                'dateTo' => $dateTo->format(DateTimeImmutable::ATOM)
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals($houses[0]->id, $responseData[0]['id']);
        $this->assertEquals($houses[1]->id, $responseData[1]['id']);
    }

    public function testGetAvailableHousesRuntimeException(): void
    {
        $client = static::createClient();

        $bookingServiceMock = $this->createMock(BookingService::class);
        static::getContainer()->set(BookingService::class, $bookingServiceMock);

        $dateFrom = '2025-07-10T00:00:00+00:00';
        $dateTo = '2025-07-01T00:00:00+00:00'; // end < start -> ошибка из сервиса

        $bookingServiceMock->expects($this->once())
            ->method('getHousesAvailableForThePeriod')
            ->willThrowException(new RuntimeException('End date must be after start date.'));

        $client->request(
            'GET',
            '/booking/booking/available',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('End date must be after start date', $content);
    }
}
