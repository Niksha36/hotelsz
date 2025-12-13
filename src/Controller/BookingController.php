<?php

declare(strict_types=1);

namespace App\Controller;

use App\dto\AvailableHousesRequestDto;
use App\dto\BookingRequestDto;
use App\Entity\UserEntity;
use App\Enum\BookingFilter;
use App\Enum\SortDirection;
use App\Services\BookingService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/booking')]
#[OA\Tag(name: 'Booking', description: 'Бронирования домов: создание, обновление, удаление и проверка доступности')]
class BookingController extends AbstractController
{
    private BookingService $bookingService;
    private LoggerInterface $logger;

    public function __construct(
        BookingService $bookingService,
        LoggerInterface $logger
    ) {
        $this->bookingService = $bookingService;
        $this->logger = $logger;
    }

    #[Route(methods: ['POST'])]
    #[
        OA\Post(
            description: 'Создает новое бронирование. Требуется авторизация (см. компонент Bearer).',
            summary: 'Создать бронирование',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: new Model(type: BookingRequestDto::class))
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: 'Бронирование создано',
                    content: new OA\JsonContent(ref: new Model(type: BookingRequestDto::class))
                ),
                new OA\Response(response: 400, description: 'Неверные данные / бизнес-ошибка'),
                new OA\Response(response: 401, description: 'Неавторизован'),
                new OA\Response(response: 500, description: 'Внутренняя ошибка сервера')
            ]
            // не используем per-operation security, т.к. ваша версия не поддерживает SecurityRequirement;
            // компонент securityScheme уже объявлен выше — можно включить глобально в nelmio config при желании
        )
    ]
    public function bookHouse(#[MapRequestPayload] BookingRequestDto $bookingDto): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof UserEntity) {
            $this->logger->error('Unauthenticated user tried to book a house');
        } else {
            $userPhone = $user->getPhone();
            $this->logger->info("User with phone {$userPhone} is booking a house");
        }

        try {
            $savedBookingDto = $this->bookingService->saveBooking($bookingDto);
            return $this->json($savedBookingDto, 201);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[
        OA\Put(
            description: 'Обновляет существующее бронирование по id. id в пути и в теле должны совпадать.',
            summary: 'Обновить бронирование',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: new Model(type: BookingRequestDto::class))
            ),
            parameters: [
                new OA\Parameter(
                    name: 'id',
                    description: 'ID бронирования',
                    in: 'path',
                    required: true,
                    schema: new OA\Schema(type: 'integer')
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Бронирование обновлено (возвращается DTO обновлённого бронирования)',
                    content: new OA\JsonContent(ref: new Model(type: BookingRequestDto::class))
                ),
                new OA\Response(response: 400, description: 'ID не совпадает или неверные данные'),
                new OA\Response(response: 401, description: 'Неавторизован'),
                new OA\Response(response: 404, description: 'Бронирование не найдено')
            ]
        )
    ]
    public function updateBooking(#[MapRequestPayload] BookingRequestDto $bookingDto, int $id): JsonResponse
    {
        if ($bookingDto->id !== $id) {
            throw new HttpException(400, 'ID in the path and payload do not match');
        }
        try {
            $updatedBookingDto = $this->bookingService->saveBooking($bookingDto);
            return $this->json($updatedBookingDto, 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[
        OA\Delete(
            description: 'Удаляет бронирование по id.',
            summary: 'Удалить бронирование',
            parameters: [
                new OA\Parameter(
                    name: 'id',
                    description: 'ID бронирования для удаления',
                    in: 'path',
                    required: true,
                    schema: new OA\Schema(type: 'integer')
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Удаление успешно',
                    content: new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string')
                            ],
                            type: 'object'
                        )
                    )
                ),
                new OA\Response(response: 400, description: 'Невозможно удалить (бизнес-логика)'),
                new OA\Response(response: 401, description: 'Неавторизован'),
                new OA\Response(response: 404, description: 'Бронирование не найдено')
            ]
        )
    ]
    public function deleteBooking(int $id): JsonResponse
    {
        try {
            $this->bookingService->deleteBooking($id);
            return $this->json(['message' => 'Booking deleted successfully'], 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }

    #[Route('/available', methods: ['GET'])]
    #[
        OA\Get(
            description: 'Возвращает список домов, доступных в указанном периоде. DTO принимается в теле (см. замечание).',
            summary: 'Доступные дома за период',
            // Для тела запроса используем JsonContent(ref: new Model(...)) — так Nelmio корректно подставит схему из DTO
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    ref: new Model(type: AvailableHousesRequestDto::class)
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Список доступных домов',
                    content: new OA\MediaType(
                        mediaType: 'application/json',
                        // Т.к. тут мы строим inline-схему массива, даём настоящий OA\Schema / OA\Items, а не Model
                        schema: new OA\Schema(
                            type: 'array',
                            items: new OA\Items(
                                // items может принимать inline-описание
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'title', type: 'string'),
                                    new OA\Property(property: 'address', type: 'string'),
                                    new OA\Property(property: 'availableFrom', type: 'string', format: 'date'),
                                    new OA\Property(property: 'availableTo', type: 'string', format: 'date'),
                                ],
                                type: 'object'
                            )
                        )
                    )
                ),
                new OA\Response(response: 400, description: 'Неверные данные'),
                new OA\Response(response: 500, description: 'Внутренняя ошибка сервера')
            ]
        )
    ]
    public function getHousesAvailableForThePeriod(#[MapRequestPayload] AvailableHousesRequestDto $requestDto): JsonResponse
    {
        try {
            $availableHouses = $this->bookingService->getHousesAvailableForThePeriod($requestDto->dateFrom, $requestDto->dateTo);
            return $this->json($availableHouses, 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }

    #[Route('admin/filter', methods: ['GET'])]
    public function getHousesByFilter(string $field, string $direction): JsonResponse
    {
        $fieldEnum = BookingFilter::tryFrom($field);
        $directionEnum = SortDirection::tryFrom(strtolower($direction));
        return $this->json($this->bookingService->getBookingsFilteredBy($fieldEnum, $directionEnum), Response::HTTP_CREATED);
    }
}
