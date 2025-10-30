<?php

namespace App\Controller;

use App\mappers\HouseMappers;
use App\Services\ServicesCSV;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/houses')]
class HousesController extends AbstractController
{
    private ServicesCSV $csvService;

    public function __construct(ServicesCSV $csvService)
    {
        $this->csvService = $csvService;
    }

    #[Route('/available', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse
    {
        $csvData = $this->csvService->readCSV("houses");

        $availableHouses = array_map(
            fn($house) => HouseMappers::toHouseDto($house),
            array_filter(
                $csvData,
                fn($house) => ((int)($house['is_booked'] ?? 0)) === 0
            )
        );

        return $this->json(array_values($availableHouses));
    }
    #[Route(methods: ['GET'])]
    public function getHouses(): JsonResponse {
        $csvData = $this->csvService->readCSV("houses");
        $houses = array_map(
            fn($house) => HouseMappers::toHouseDto($house),
            $csvData
        );
        return $this->json($houses);
    }
}
