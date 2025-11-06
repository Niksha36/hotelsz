<?php

namespace App\Controller;

use App\dto\AvailableHousesRequestDto;
use App\dto\BookingRequestDto;
use App\Services\BookingService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/booking')]
class BookingController extends AbstractController
{
    private BookingService $bookingService;

    public function __construct(
        BookingService $bookingService
    )
    {
        $this->bookingService = $bookingService;
    }

    #[Route(methods: ['POST'])]
    public function bookHouse(#[MapRequestPayload] BookingRequestDto $bookingDto): JsonResponse
    {
        try {
            $savedBookingDto = $this->bookingService->saveBooking($bookingDto);
            return $this->json($savedBookingDto, 201);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }


    #[Route('/{id}', methods: ['PUT'])]
    public function updateBooking(#[MapRequestPayload] BookingRequestDto $bookingDto, int $id): JsonResponse
    {
        if ($bookingDto->id !== $id) {
            throw new HttpException(400, 'ID in the path and payload do not match');
        }
        try{
            $updatedBookingDto = $this->bookingService->saveBooking($bookingDto);
            return $this->json($updatedBookingDto, 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteBooking(int $id): JsonResponse
    {
        try{
            $this->bookingService->deleteBooking($id);
            return $this->json(['message' => 'Booking deleted successfully'], 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }


    #[Route('/booking/available', methods: ['GET'])]
    public function getHousesAvailableForThePeriod(#[MapRequestPayload] AvailableHousesRequestDto $requestDto): JsonResponse
    {
        try {
            $availableHouses = $this->bookingService->getHousesAvailableForThePeriod($requestDto->dateFrom, $requestDto->dateTo);
            return $this->json($availableHouses, 200);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
    }
}


