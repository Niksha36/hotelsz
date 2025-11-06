<?php

namespace App\Controller;

use App\dto\UserDto;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

/*
 * Думаю в скоре прийдется подключать сервисы для аутентификации и регистрации пользователей c помощью JWT токенов.
 * Это пока на будущее.
*/
#[Route('/user')]
class UserController extends AbstractController
{
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route(methods: ['POST'])]
    public function createUser(#[MapRequestPayload] UserDto $userDto): JsonResponse
    {
        return $this->json($this->userService->createUser($userDto), Response::HTTP_CREATED);
    }
}
