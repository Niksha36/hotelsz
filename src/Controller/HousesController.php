<?php

declare(strict_types=1);

namespace App\Controller;

use App\dto\HouseDto;
use App\Enum\HouseFilters;
use App\Enum\SortDirection;
use App\Services\HouseService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
#[OA\Tag(name: 'Houses', description: 'Операции с домами')]
class HousesController extends AbstractController
{
    private HouseService $houseService;
    private SerializerInterface $serializer;
    public function __construct(HouseService $houseService, SerializerInterface $serializer)
    {
        $this->houseService = $houseService;
        $this->serializer = $serializer;
    }

    #[Route('/houses', methods: ['GET'])]
    #[
        OA\Get(
            description: 'Возвращает массив объектов домов (HouseDto).',
            summary: 'Список домов',
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Успешный ответ — массив домов',
                    content: new OA\JsonContent(
                        type: 'array',
                        items: new OA\Items(
                            ref: new Model(type: HouseDto::class)
                        )
                    )
                ),
                new OA\Response(response: 500, description: 'Внутренняя ошибка сервера')
            ]
        )
    ]
    public function getHouses(): JsonResponse
    {
        $houses = $this->houseService->getHouses();
        return $this->json($houses, Response::HTTP_OK);
    }

    #[Route('/admin/houses', methods: ['POST'])]
    #[
        OA\Post(
            description: 'Создает новый дом или обновляет существующий. В теле — HouseDto.',
            summary: 'Создать дом (админ)',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: new Model(type: HouseDto::class))
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: 'Дом успешно создан / сохранён — возвращается сохранённый HouseDto',
                    content: new OA\JsonContent(ref: new Model(type: HouseDto::class))
                ),
                new OA\Response(response: 400, description: 'Неверные данные'),
                new OA\Response(response: 401, description: 'Неавторизован'),
                new OA\Response(response: 500, description: 'Внутренняя ошибка сервера')
            ]
        )
    ]
    public function putHouse(#[MapRequestPayload] HouseDto $houseDto): JsonResponse
    {
        $savedHouse = $this->houseService->saveHouse($houseDto);
        return $this->json($savedHouse, Response::HTTP_CREATED);
    }

    #[Route('/houses/filter', methods: ['POST'])]
    public function fromStrings(string $field, string $direction): JsonResponse
    {
        $fieldEnum = HouseFilters::tryFrom($field);
        $directionEnum = SortDirection::tryFrom(strtolower($direction));
        return $this->json($this->houseService->filterHousesBy($fieldEnum, $directionEnum), Response::HTTP_CREATED);
    }
}
