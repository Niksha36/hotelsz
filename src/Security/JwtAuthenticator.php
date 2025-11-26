<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Exception;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator extends AbstractAuthenticator
{
    private JwtService $jwtService;
    private UserRepository $userRepository;

    public function __construct(JwtService $jwtService, UserRepository $userRepository)
    {
        $this->jwtService = $jwtService;
        $this->userRepository = $userRepository;
    }

    /**
     * Поддерживаем ли аутентификацию для данного запроса.
     * Проверяем наличие заголовка Authorization: Bearer ...
     */
    #[Override]
    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization', '');
        return str_starts_with($authHeader, 'Bearer ');
    }

    /**
     * Попытка аутентифицировать запрос — вернуть Passport с UserBadge.
     *
     * @throws AuthenticationException при любых проблемах аутентификации
     */
    #[Override]
    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7);

        if ($token === '' || $token === false) {
            throw new CustomUserMessageAuthenticationException('No Bearer token provided');
        }

        if (!$this->jwtService->validateAccessToken($token)) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired access token');
        }

        try {
            $userId = $this->jwtService->getIdFromToken($token);
        } catch (Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid token payload');
        }

        // UserBadge с callback'ом, который загружает User по id
        return new SelfValidatingPassport(
            new UserBadge((string)$userId, function (string $id) {
                $user = $this->userRepository->find((int)$id);
                if ($user === null) {
                    throw new CustomUserMessageAuthenticationException('User not found');
                }
                return $user;
            })
        );
    }

    /**
     * Успешная аутентификация — продолжаем обработку запроса.
     */
    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * Неуспешная аутентификация — возвращаем 401 и JSON-ошибку.
     */
    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception instanceof CustomUserMessageAuthenticationException
            ? $exception->getMessage()
            : 'Authentication failed';

        $data = [
            'error' => $message,
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
