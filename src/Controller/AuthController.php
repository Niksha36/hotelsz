<?php

declare(strict_types=1);

namespace App\Controller;

use App\dto\LoginDto;
use App\dto\RegisterDto;
use App\Services\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/auth')]
class AuthController extends AbstractController
{
    private AuthService $userService;
    public function __construct(AuthService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterDto $userDto): JsonResponse
    {
        if ($userDto->password !== $userDto->confirmPassword) {
            throw new BadRequestHttpException('Password and confirmPassword do not match');
        }
        return $this->json($this->userService->register($userDto), Response::HTTP_CREATED);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginDto $loginDto): JsonResponse
    {
        return $this->json($this->userService->login($loginDto), Response::HTTP_OK);
    }

    #[Route('/refresh-tokens', methods: ['POST'])]
    public function refreshTokens(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? null;
        if (!$refreshToken) {
            throw new BadRequestHttpException('refreshToken is required');
        }

        return $this->json($this->userService->refreshTokens($refreshToken), 200);
    }
}
