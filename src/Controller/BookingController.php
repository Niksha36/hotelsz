<?php

namespace App\Controller;

use App\mappers\BookingMappers;
use App\Services\ServicesCSV;
use App\Services\ViolationFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/booking')]
class BookingController extends AbstractController
{
    private ServicesCSV $csvService;
    private ValidatorInterface $validator;
    private ViolationFormatter $violationFormatter;
    public function __construct(ServicesCSV $csvService, ValidatorInterface $validator, ViolationFormatter $violationFormatter)
    {
        $this->violationFormatter = $violationFormatter;
        $this->csvService = $csvService;
        $this->validator = $validator;
    }

    #[Route(methods: ['POST'])]
    public function bookHouse(Request $request): JsonResponse
    {
        $dataMap = json_decode($request->getContent(), true) ?? $request->request->all();
        $dto = BookingMappers::toBookingRequestDto($dataMap);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new HttpException(
                422,
                json_encode(['errors' => $this->violationFormatter->format($violations)]),
                null,
                ['Content-Type' => 'application/json']
            );
        }
        $csvData = $this->csvService->readCSV("houses");
        $savedBooking = null;

        foreach ($csvData as &$house) {
            if ($house['id'] == $dto->house_id) {
                if ($house['is_booked']) {
                    throw new HttpException(
                        400,
                        json_encode(['error' => 'House is already booked']),
                        null,
                        ['Content-Type' => 'application/json']
                    );
                }

                $house['is_booked'] = 1;
                $this->csvService->updateCsv("houses", $house['id'], $house);
                $savedBooking = $this->csvService->writeCSV("bookings", $dataMap);
                break;
            }
        }

        if ($savedBooking === null) {
            throw new HttpException(404, json_encode(['error' => 'House not found']), null, ['Content-Type' => 'application/json']);
        }

        return $this->json(BookingMappers::toBookingRequestDto($savedBooking), 200);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function updateBooking(Request $request, string $id): JsonResponse
    {
        $dataMap = json_decode($request->getContent(), true) ?? $request->request->all();
        $dataMap['id'] = $id;
        $dto = BookingMappers::toBookingRequestDto($dataMap);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new HttpException(
                422,
                json_encode(['errors' => $this->violationFormatter->format($violations)]),
                null,
                ['Content-Type' => 'application/json']
            );
        }
        // Сначала в таблице houses снимаем бронь с дома, связанного с этим бронированием
        $houses = $this->csvService->readCSV("houses");
        foreach ($houses as $house) {
            if ($house['id'] == $dto->house_id) {
                $house['is_booked'] = 0;
                $this->csvService->updateCsv("houses", $house['id'], $house);
                break;
            }
        }
        // Затем обновляем само бронирование
        $this->csvService->updateCsv("bookings", $id, BookingMappers::fromBookingRequestDtoToMap($dto));
        return $this->json($dto, 200);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteBooking(string $id): JsonResponse
    {
        $this->csvService->deleteById('bookings', $id);
        return $this->json(['message' => 'Booking deleted successfully'], 200);
    }
}
