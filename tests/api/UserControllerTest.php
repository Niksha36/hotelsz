<?php

namespace App\Tests\api;

use App\dto\UserDto;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    public function testCreateUser(): void
    {
        $client = static::createClient();

        $userData = [
            'phone' => '+1234567890',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $userServiceMock = $this->createMock(UserService::class);

        $expectedDto = new UserDto(
            phone: $userData['phone'],
            firstName: $userData['firstName'],
            lastName: $userData['lastName'],
            email: $userData['email']
        );

        $userServiceMock->expects($this->once())
            ->method('createUser')
            ->with($this->callback(function (UserDto $userDto) use ($userData) {
                return $userDto->phone === $userData['phone']
                    && $userDto->firstName === $userData['firstName']
                    && $userDto->lastName === $userData['lastName']
                    && $userDto->email === $userData['email'];
            }))
            ->willReturn($expectedDto);

        static::getContainer()->set(UserService::class, $userServiceMock);

        $client->request(
            'POST',
            '/user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertEquals($userData['email'], $responseData['email']);
        $this->assertEquals($userData['firstName'], $responseData['firstName']);
        $this->assertEquals($userData['lastName'], $responseData['lastName']);
        $this->assertEquals($userData['phone'], $responseData['phone']);
    }
}
