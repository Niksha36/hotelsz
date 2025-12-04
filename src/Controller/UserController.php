<?php

declare(strict_types=1);

namespace App\Controller;

use App\dto\UserDto;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    #[Route('admin/users', methods: ['GET'])]
    public function getUsers()
    {
        $users = $this->userService->getUsers();
        return $this->json($users, 200);
    }

    #[Route('admin/users/{id}', methods: ['PUT'])]
    public function updateUser(int $id, #[MapRequestPayload] UserDto $data): JsonResponse
    {
        return $this->json($this->userService->updateUser($id, $data), 200);
    }

    #[Route('admin/users/{id}', methods: ['DELETE'])]
    public function deleteUser(int $id): Response
    {
        $this->userService->deleteUser($id);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
