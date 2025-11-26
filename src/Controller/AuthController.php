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
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/auth')]
#[OA\Tag(name: 'Auth', description: 'Аутентификация и регистрация')]
class AuthController extends AbstractController
{
    private AuthService $userService;

    public function __construct(AuthService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/register', methods: ['POST'])]
    #[
        OA\Post(
            description: 'Регистрирует нового пользователя. Возвращает созданный профиль (или минимальные данные).',
            summary: 'Регистрация пользователя',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: new Model(type: RegisterDto::class))
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: 'Пользователь создан',
                    content: new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'phone', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string')
                            ],
                            type: 'object'
                        )
                    )
                ),
                new OA\Response(response: 400, description: 'Неверные входные данные')
            ]
        )
    ]
    public function register(#[MapRequestPayload] RegisterDto $userDto): JsonResponse
    {
        if ($userDto->password !== $userDto->confirmPassword) {
            throw new BadRequestHttpException('Password and confirmPassword do not match');
        }
        return $this->json($this->userService->register($userDto), Response::HTTP_CREATED);
    }

    #[Route('/login', methods: ['POST'])]
    #[
        OA\Post(
            description: 'Авторизация по телефону и паролю. Возвращает access и refresh токены.',
            summary: 'Логин',
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: new Model(type: LoginDto::class))
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Успешный логин — токены',
                    content: new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            properties: [
                                new OA\Property(property: 'accessToken', type: 'string'),
                                new OA\Property(property: 'refreshToken', type: 'string'),
                                new OA\Property(property: 'expiresIn', type: 'integer')
                            ],
                            type: 'object'
                        )
                    )
                ),
                new OA\Response(response: 401, description: 'Некорректные учетные данные')
            ]
        )
    ]
    public function login(#[MapRequestPayload] LoginDto $loginDto): JsonResponse
    {
        return $this->json($this->userService->login($loginDto), Response::HTTP_OK);
    }

    #[Route('/refresh-tokens', methods: ['POST'])]
    #[
        OA\Post(
            description: 'Обновляет access и refresh токены по refreshToken.',
            summary: 'Обновление токенов',
            requestBody: new OA\RequestBody(
                required: true,
                content: [
                    new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            required: ['refreshToken'],
                            properties: [
                                new OA\Property(property: 'refreshToken', type: 'string')
                            ],
                            type: 'object'
                        )
                    )
                ]
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Новые токены',
                    content: new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            properties: [
                                new OA\Property(property: 'accessToken', type: 'string'),
                                new OA\Property(property: 'refreshToken', type: 'string'),
                                new OA\Property(property: 'expiresIn', type: 'integer')
                            ],
                            type: 'object'
                        )
                    )
                ),
                new OA\Response(response: 400, description: 'refreshToken отсутствует или некорректен')
            ]
        )
    ]
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
