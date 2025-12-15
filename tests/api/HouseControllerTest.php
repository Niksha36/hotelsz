<?php

declare(strict_types=1);

namespace App\Tests\api;

use App\dto\HouseDto;
use App\Security\UserRole;
use App\Services\HouseService;
use App\Tests\api\TestHelpers\AuthenticatedClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseControllerTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    public function testPutHouse(): void
    {
        $client = $this->createAuthenticatedClient(UserRole::ROLE_ADMIN);

        $houseData = [
            'name' => 'Villa on the beach',
            'type' => 'Villa',
            'beds' => 4,
            'rowFromSea' => 1,
            'pricePerNightRub' => 15000.0,
        ];

        $houseServiceMock = $this->createMock(HouseService::class);

        // Ожидаем, что сервис вернет DTO с присвоенным ID
        $expectedDto = new HouseDto(
            id: 1,
            name: $houseData['name'],
            type: $houseData['type'],
            beds: $houseData['beds'],
            rowFromSea: $houseData['rowFromSea'],
            pricePerNightRub: $houseData['pricePerNightRub']
        );

        $houseServiceMock->expects($this->once())
            ->method('saveHouse')
            // Проверяем, что в сервис передается корректный DTO
            ->with($this->callback(function (HouseDto $houseDto) use ($houseData) {
                return $houseDto->name === $houseData['name']
                    && $houseDto->type === $houseData['type']
                    && $houseDto->beds === $houseData['beds']
                    && $houseDto->rowFromSea === $houseData['rowFromSea']
                    && $houseDto->pricePerNightRub === $houseData['pricePerNightRub'];
            }))
            ->willReturn($expectedDto);

        // Заменяем реальный сервис на mock в контейнере зависимостей
        static::getContainer()->set(HouseService::class, $houseServiceMock);

        $client->request(
            'POST',
            'api/admin/houses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($houseData)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        // Проверяем, что ответ соответствует тому, что вернул mock-сервис
        $this->assertEquals($expectedDto->id, $responseData['id']);
        $this->assertEquals($expectedDto->name, $responseData['name']);
        $this->assertEquals($expectedDto->type, $responseData['type']);
        $this->assertEquals($expectedDto->beds, $responseData['beds']);
        $this->assertEquals($expectedDto->rowFromSea, $responseData['rowFromSea']);
        $this->assertEquals($expectedDto->pricePerNightRub, $responseData['pricePerNightRub']);
    }

    public function testGetHouses(): void
    {
        $client = $this->createAuthenticatedClient();

        $houseServiceMock = $this->createMock(HouseService::class);

        $housesDtoArray = [
            new HouseDto(1, 'House 1', 'Bungalow', 2, 2, 5000.0),
            new HouseDto(2, 'House 2', 'Villa', 6, 1, 20000.0),
        ];

        $houseServiceMock->expects($this->once())
            ->method('getHouses')
            ->willReturn($housesDtoArray);

        static::getContainer()->set(HouseService::class, $houseServiceMock);

        $client->request('GET', 'api/houses');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(2, $responseData);
        $this->assertEquals($housesDtoArray[0]->id, $responseData[0]['id']);
        $this->assertEquals($housesDtoArray[0]->name, $responseData[0]['name']);
        $this->assertEquals($housesDtoArray[1]->id, $responseData[1]['id']);
        $this->assertEquals($housesDtoArray[1]->name, $responseData[1]['name']);
    }
}
