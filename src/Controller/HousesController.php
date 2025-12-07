<?php

declare(strict_types=1);

namespace App\Controller;

use App\dto\HouseDto;
use App\Services\HouseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/houses')]
class HousesController extends AbstractController
{
    private HouseService $houseService;

    public function __construct(HouseService $houseService)
    {
        $this->houseService = $houseService;
    }

    #[Route(methods: ['GET'])]
    public function getHouses(): JsonResponse
    {
        $houses = $this->houseService->getHouses();
        return $this->json($houses, Response::HTTP_OK);
    }

    #[Route(methods: ['POST'])]
    public function putHouse(#[MapRequestPayload] HouseDto $houseDto): JsonResponse
    {
        $savedHouse = $this->houseService->saveHouse($houseDto);
        return $this->json($savedHouse, Response::HTTP_CREATED);
    }
}
