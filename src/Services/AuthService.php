<?php

declare(strict_types=1);

namespace App\Services;

use App\dto\LoginDto;
use App\dto\RegisterDto;
use App\dto\UserDto;
use App\Entity\RefreshTokenEntity;
use App\Entity\UserEntity;
use App\mappers\UserMappers;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use App\Security\JwtService;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthService
{
    private UserRepository $userRepository;
    private RefreshTokenRepository $refreshTokenRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JwtService $jwtService;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JwtService $jwtService,
        RefreshTokenRepository $refreshTokenRepository
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtService = $jwtService;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }
    public function register(RegisterDto $userDto): UserDto
    {
        $password = $userDto->password;
        $confirmPassword = $userDto->confirmPassword;
        if ($password == '' || $confirmPassword == '') {
            throw new BadRequestHttpException('Пароль не может быть пустым');
        }
        if ($password != $confirmPassword) {
            throw new BadRequestHttpException('Пароль и подтверждение пароля не совпадают');
        }
        if ($this->userRepository->findOneBy(['phone' => $userDto->phone]) !== null) {
            throw new ConflictHttpException('Пользователь с таким номером телефона уже существует');
        }
        if ($this->userRepository->findOneBy(['email' => $userDto->email]) !== null) {
            throw new ConflictHttpException('Пользователь с таким email уже существует');
        }
        $hashedPassword = password_hash($userDto->password, PASSWORD_ARGON2ID);
        $entity = new UserEntity(
            $userDto->phone,
            $userDto->firstName,
            $userDto->lastName,
            $userDto->email,
            $hashedPassword,
            ['ROLE_USER']
        );
        $this->userRepository->save($entity);
        return UserMappers::fromUserEntityToDto($entity);
    }
    public function login(LoginDto $loginDto): array
    {
        $user = $this->userRepository->findOneBy(['phone' => $loginDto->phone]);

        if (!$user instanceof UserEntity) {
            throw new AuthenticationException('User not found');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $loginDto->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        $userId = $user->getId();
        if ($userId === null) {
            throw new RuntimeException('User ID is null');
        }

        $refreshToken = $this->jwtService->createRefreshToken($userId);
        $accessToken = $this->jwtService->createAccessToken($userId);

        // сохраняем refresh-token в БД сразу после логина
        $this->saveRefreshToken($user, $refreshToken);

        $tokens = [
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
        ];
        return $tokens;
    }

    /**
     * @param string $refreshToken
     * @return array{accessToken: string, refreshToken: string}
     */
    public function refreshTokens(string $refreshToken): array
    {
        if (!$this->jwtService->validateRefreshToken($refreshToken)) {
            throw new AuthenticationException('Invalid refresh token');
        }

        $userId = $this->jwtService->getIdFromToken($refreshToken);
        $refreshToken = str_starts_with($refreshToken, 'Bearer ') ? substr($refreshToken, 7) : $refreshToken;
        $userTokenInDb = $this->refreshTokenRepository->findOneBy(['token' => $refreshToken]);

        if ($userTokenInDb === null) {
            throw new AuthenticationException('Refresh token not found');
        }

        if ($userTokenInDb->getExpiryDate() < new DateTimeImmutable()) {
            throw new AuthenticationException('Refresh token expired');
        }

        if ($userTokenInDb->getUser()->getId() !== $userId) {
            throw new AuthenticationException('Refresh token does not belong to current user');
        }

        $newAccessToken = $this->jwtService->createAccessToken($userId);
        $newRefreshToken = $this->jwtService->createRefreshToken($userId);

        $this->saveRefreshToken($userTokenInDb->getUser(), $newRefreshToken);

        $tokens = [
            'accessToken'  => $newAccessToken,
            'refreshToken' => $newRefreshToken,
        ];

        return $tokens;
    }

    /**
     * Сохранить/обновить refresh token для пользователя.
     */
    public function saveRefreshToken(UserEntity $userEntity, string $token): void
    {
        // ищем существующую запись по пользователю (one-to-one на user)
        $userTokenInDb = $this->refreshTokenRepository->findOneBy(['user' => $userEntity]);

        $ms = $this->jwtService->getRefreshTokenMs();
        $seconds = intdiv($ms, 1000);
        $expiry = (new DateTimeImmutable())->add(new DateInterval('PT' . $seconds . 'S'));

        if ($userTokenInDb instanceof RefreshTokenEntity) {
            $userTokenInDb->setToken($token);
            $userTokenInDb->setExpiryDate($expiry);
            $this->refreshTokenRepository->save($userTokenInDb);
        } else {
            $tokenEntity = new RefreshTokenEntity(
                user: $userEntity,
                token: $token,
                expiryDate: $expiry
            );
            $this->refreshTokenRepository->save($tokenEntity);
        }
    }
}
