<?php

declare(strict_types=1);

namespace App\Tests\api;

use App\dto\LoginDto;
use App\dto\RegisterDto;
use App\dto\UserDto;
use App\Services\AuthService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    public function testRegisterUserSuccess(): void
    {
        $client = static::createClient();

        $userData = [
            'phone' => '+1234567890',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'securePassword1!$',
            'confirmPassword' => 'securePassword1!$',
        ];

        $returnedDto = new UserDto(
            $userData['phone'],
            $userData['firstName'],
            $userData['lastName'],
            $userData['email']
        );

        $serviceMock = $this->createMock(AuthService::class);

        $serviceMock->expects($this->once())
            ->method('register')
            ->with($this->callback(function (RegisterDto $dto) use ($userData) {
                return $dto->phone === $userData['phone']
                    && $dto->firstName === $userData['firstName']
                    && $dto->lastName === $userData['lastName']
                    && $dto->email === $userData['email']
                    && $dto->password === $userData['password']
                    && $dto->confirmPassword === $userData['confirmPassword'];
            }))
            // Контроллер просто возвращает то, что вернул сервис — возвращаем массив (json-friendly)
            ->willReturn($returnedDto);

        // Зарегистрируем мок в тестовом контейнере
        static::getContainer()->set(AuthService::class, $serviceMock);

        $client->request(
            'POST',
            '/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame($userData['email'], $data['email']);
        $this->assertSame($userData['firstName'], $data['firstName']);
        $this->assertSame($userData['lastName'], $data['lastName']);
        $this->assertSame($userData['phone'], $data['phone']);
    }

    public function testRegisterPasswordsMismatchReturnsBadRequest(): void
    {
        $client = static::createClient();

        $badData = [
            'phone' => '+1234567890',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'passwordA1!',
            'confirmPassword' => 'passwordB1!',
        ];

        // Сервис НЕ должен вызываться при несоответствии паролей
        $serviceMock = $this->createMock(AuthService::class);
        $serviceMock->expects($this->never())->method('register');

        static::getContainer()->set(AuthService::class, $serviceMock);

        $client->request(
            'POST',
            '/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($badData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginUserSuccess(): void
    {
        $client = static::createClient();

        $loginData = [
            'phone' => '+1234567890',
            'password' => 'securePassword1!$',
        ];

        $serviceMock = $this->createMock(AuthService::class);

        $serviceMock->expects($this->once())
            ->method('login')
            ->with($this->callback(function (LoginDto $dto) use ($loginData) {
                return $dto->phone === $loginData['phone'] && $dto->password === $loginData['password'];
            }))
            ->willReturn([
                'accessToken' => 'access.token.example',
                'refreshToken' => 'refresh.token.example',
                'expiresIn' => 3600,
            ]);

        static::getContainer()->set(AuthService::class, $serviceMock);

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('accessToken', $data);
        $this->assertArrayHasKey('refreshToken', $data);
        $this->assertArrayHasKey('expiresIn', $data);
    }

    public function testRefreshTokensSuccess(): void
    {
        $client = static::createClient();

        $payload = ['refreshToken' => 'refresh.token.example'];

        $serviceMock = $this->createMock(AuthService::class);

        $serviceMock->expects($this->once())
            ->method('refreshTokens')
            ->with('refresh.token.example')
            ->willReturn([
                'accessToken' => 'new.access.token',
                'refreshToken' => 'new.refresh.token',
            ]);

        static::getContainer()->set(AuthService::class, $serviceMock);

        $client->request(
            'POST',
            '/auth/refresh-tokens',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('accessToken', $data);
        $this->assertArrayHasKey('refreshToken', $data);
    }

    public function testRefreshTokensIntegrationFlow(): void
    {
        $client = static::createClient();

        $unique = (string) time();
        $phone = '+7900' . substr($unique, -7);
        $email = 'user' . $unique . '@example.test';
        $password = 'Aa1!strongPass';

        $registerPayload = [
            'phone' => $phone,
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => $email,
            'password' => $password,
            'confirmPassword' => $password,
        ];

        $client->request(
            'POST',
            '/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($registerPayload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Регистрация должна пройти успешно');

        // 2) Login -> получить refreshToken
        $loginPayload = [
            'phone' => $phone,
            'password' => $password,
        ];

        $client->request(
            'POST',
            '/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginPayload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK, 'Логин должен вернуть 200');

        $loginResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($loginResponse, 'Ожидается JSON в ответе логина');
        $this->assertArrayHasKey('refreshToken', $loginResponse, 'Ответ логина должен содержать refreshToken');

        $refreshToken = $loginResponse['refreshToken'];

        $client->request(
            'POST',
            '/auth/refresh-tokens',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['refreshToken' => $refreshToken])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK, 'Refresh должен вернуть 200');

        $refreshResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($refreshResponse);
        $this->assertArrayHasKey('accessToken', $refreshResponse);
        $this->assertArrayHasKey('refreshToken', $refreshResponse);
    }
}
