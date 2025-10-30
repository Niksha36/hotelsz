<?php

namespace App\Controller;

use App\dto\BookingRequestDto;
use App\dto\HouseDto;
use App\mappers\BookingMappers;
use App\mappers\HouseMappers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\ServicesCSV;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

#[Route('/booking')]
class BookingController extends AbstractController
{
    private ServicesCSV $csvService;
    private ValidatorInterface $validator;

    public function __construct(ServicesCSV $csvService, ValidatorInterface $validator)
    {
        $this->csvService = $csvService;
        $this->validator = $validator;
    }

    #[Route(methods: ['POST'])]
    public function bookHouse(Request $request): JsonResponse
    {
        $dataMap = json_decode($request->getContent(), true) ?? $request->request->all();
        $dto = BookingMappers::toBookingRequestDto($dataMap);
        // валидация DTO
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->json(
                ['errors' => $this->formatViolations($violations)],
                422
            );
        }
        $csvData = $this->csvService->readCSV("houses");
        foreach ($csvData as $house) {
            if ($house['id'] == $dto->house_id) {
                if ($house['is_booked']) {
                    return $this->json(['error' => 'House is already booked'], 400);
                } else {
                    $house['is_booked'] = 1;
                    $this->csvService->updateCsv("houses", $house['id'], $house);
                    $savedBooking = $this->csvService->writeCSV("bookings", $dataMap);
                    return $this->json(BookingMappers::toBookingRequestDto($savedBooking), 200);
                }
            }
        }
        return $this->json(['error' => 'House not found'], 404);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function updateBooking(Request $request, string $id): JsonResponse
    {
        $dataMap = json_decode($request->getContent(), true) ?? $request->request->all();
        $dataMap['id'] = $id;
        $dto = BookingMappers::toBookingRequestDto($dataMap);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return $this->json(
                ['errors' => $this->formatViolations($violations)],
                422
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

    // Вообще надо центрально вынести валидацию и форматирование ошибок, но я слишком глупый чтобы это сделать правильно :)
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $errors[$path][] = $violation->getMessage();
        }
        return $errors;
    }
}
