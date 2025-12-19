<?php

declare(strict_types=1);

namespace App\Tests\services;

use App\dto\LoginDto;
use App\dto\RegisterDto;
use App\dto\UserDto;
use App\Entity\RefreshTokenEntity;
use App\Entity\UserEntity;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use App\Security\JwtService;
use App\Services\AuthService;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class AuthServiceTest extends TestCase
{
    private UserRepository|MockObject $userRepo;
    private UserPasswordHasherInterface|MockObject $passwordHasher;
    private JwtService|MockObject $jwtService;
    private RefreshTokenRepository|MockObject $refreshRepo;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtService = $this->createMock(JwtService::class);
        $this->refreshRepo = $this->createMock(RefreshTokenRepository::class);
    }

    public function testRegisterSuccessSavesEntityAndReturnsDto(): void
    {
        $dto = new RegisterDto(
            phone: '+79995554433',
            firstName: 'Ivan',
            lastName: 'Petrov',
            email: 'ivan@example.com',
            password: 'securePass1!',
            confirmPassword: 'securePass1!'
        );

        // findOneBy должен вернуть null (нет дублей)
        $this->userRepo->method('findOneBy')->willReturn(null);

        // Ожидаем, что save вызовется и переданный объект имеет нужные поля
        $this->userRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserEntity $entity) use ($dto) {
                return $entity->getPhone() === $dto->phone
                    && $entity->getFirstName() === $dto->firstName
                    && $entity->getLastName() === $dto->lastName
                    && $entity->getEmail() === $dto->email
                    // пароль в сущности должен быть захеширован — просто убедимся, что он не равен plain
                    && $entity->getPassword() !== $dto->password;
            }));

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $result = $service->register($dto);

        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertSame($dto->phone, $result->phone);
        $this->assertSame($dto->firstName, $result->firstName);
        $this->assertSame($dto->lastName, $result->lastName);
        $this->assertSame($dto->email, $result->email);
    }

    public function testRegisterPhoneConflictThrowsConflictException(): void
    {
        $dto = new RegisterDto(
            phone: '+70000000000',
            firstName: 'A',
            lastName: 'B',
            email: 'a@b.com',
            password: 'Aa1!aaaa',
            confirmPassword: 'Aa1!aaaa'
        );

        // Если findOneBy вызван с критерием phone — возвращаем не-null (существующий пользователь)
        $this->userRepo->method('findOneBy')->willReturnCallback(function (array $criteria) {
            if (isset($criteria['phone'])) {
                return new UserEntity('+70000000000', 'X', 'Y', 'x@y.com');
            }
            return null;
        });

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $this->expectException(ConflictHttpException::class);
        $service->register($dto);
    }

    public function testLoginSuccessReturnsTokensAndSavesRefreshToken(): void
    {
        $phone = '+71112223344';
        $password = 'Password1!';

        $userMock = $this->createMock(UserEntity::class);
        $userMock->method('getId')->willReturn(42);
        $userMock->method('getPhone')->willReturn($phone);
        // getId used later; getPassword exists but passwordHasher used for check

        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => $phone])
            ->willReturn($userMock);

        // passwordHasher вернёт true
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($userMock, $password)
            ->willReturn(true);

        $this->jwtService->expects($this->once())
            ->method('createRefreshToken')
            ->with(42)
            ->willReturn('refresh-token-xyz');

        $this->jwtService->expects($this->once())
            ->method('createAccessToken')
            ->with(42)
            ->willReturn('access-token-abc');

        // refreshRepo.findOneBy вернёт null -> save будет вызван с RefreshTokenEntity
        $this->refreshRepo->method('findOneBy')->willReturn(null);
        $this->refreshRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($entity) {
                // проверяем тип и наличие токена в сущности
                return $entity instanceof RefreshTokenEntity
                    && method_exists($entity, 'getToken')
                    && str_starts_with($entity->getToken(), 'refresh-token');
            }));

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $result = $service->login(new LoginDto($phone, $password));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accessToken', $result);
        $this->assertArrayHasKey('refreshToken', $result);
        $this->assertSame('access-token-abc', $result['accessToken']);
        $this->assertSame('refresh-token-xyz', $result['refreshToken']);
    }

    public function testLoginUserNotFoundThrowsAuthenticationException(): void
    {
        $phone = '+70001112233';
        $this->userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => $phone])
            ->willReturn(null);

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $this->expectException(AuthenticationException::class);
        $service->login(new LoginDto($phone, 'whatever'));
    }

    public function testLoginInvalidPasswordThrowsAuthenticationException(): void
    {
        $phone = '+79990001122';
        $userMock = $this->createMock(UserEntity::class);
        $userMock->method('getId')->willReturn(1);

        $this->userRepo->method('findOneBy')->willReturn($userMock);
        $this->passwordHasher->method('isPasswordValid')->with($userMock, 'bad')->willReturn(false);

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $this->expectException(AuthenticationException::class);
        $service->login(new LoginDto($phone, 'bad'));
    }

    public function testRefreshTokensSuccess(): void
    {
        $rawToken = 'refresh-123';
        $userId = 7;

        // подготовим user и tokenEntity в БД
        $userMock = $this->createMock(UserEntity::class);
        $userMock->method('getId')->willReturn($userId);

        $tokenEntityMock = $this->createMock(RefreshTokenEntity::class);
        $tokenEntityMock->method('getExpiryDate')->willReturn((new DateTimeImmutable())->add(new DateInterval('PT1H')));
        $tokenEntityMock->method('getUser')->willReturn($userMock);

        // jwtService.validateRefreshToken -> true
        $this->jwtService->expects($this->once())
            ->method('validateRefreshToken')
            ->with($rawToken)
            ->willReturn(true);

        $this->jwtService->expects($this->once())
            ->method('getIdFromToken')
            ->with($rawToken)
            ->willReturn($userId);

        $this->refreshRepo->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($rawToken, $tokenEntityMock, $userMock) {
                if (isset($criteria['token']) && $criteria['token'] === $rawToken) {
                    return $tokenEntityMock;
                }
                if (isset($criteria['user']) && $criteria['user'] === $userMock) {
                    return null;
                }
                return null;
            }
        );

        $this->jwtService->expects($this->once())
            ->method('createAccessToken')
            ->with($userId)
            ->willReturn('new-access');

        $this->jwtService->expects($this->once())
            ->method('createRefreshToken')
            ->with($userId)
            ->willReturn('new-refresh');

        // ожидание сохранения нового refresh-токена
        $this->refreshRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($ent) {
                return $ent instanceof RefreshTokenEntity
                    && method_exists($ent, 'getToken')
                    && str_starts_with($ent->getToken(), 'new-refresh');
            }));

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $result = $service->refreshTokens($rawToken);

        $this->assertIsArray($result);
        $this->assertSame('new-access', $result['accessToken']);
        $this->assertSame('new-refresh', $result['refreshToken']);
    }

    public function testRefreshTokensInvalidThrowsAuthenticationException(): void
    {
        $rawToken = 'invalid-token';

        $this->jwtService->method('validateRefreshToken')->with($rawToken)->willReturn(false);

        $service = new AuthService(
            $this->userRepo,
            $this->passwordHasher,
            $this->jwtService,
            $this->refreshRepo
        );

        $this->expectException(AuthenticationException::class);
        $service->refreshTokens($rawToken);
    }
}
