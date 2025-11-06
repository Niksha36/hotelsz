<?php

namespace App\Tests\services;

use App\Services\HouseService;
use App\Infrastructure\Persistence\HouseRepository;
use App\Entity\HouseEntity;
use App\dto\HouseDto;
use PHPUnit\Framework\TestCase;

class HouseServiceTest extends TestCase
{
    private function makeHouseEntity(int $id, string $name = 'Name', string $type='type', int $beds=2, int $row=1, int $price=1000): HouseEntity
    {
        $e = new HouseEntity();
        $e->setName($name)->setType($type)->setBeds($beds)->setRowFromSea($row)->setPricePerNightRub($price);
        $ref = new \ReflectionClass($e);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($e, $id);
        return $e;
    }

    public function testGetHousesReturnsDtos(): void
    {
        $repo = $this->createMock(HouseRepository::class);
        $repo->expects($this->once())
            ->method('findAll')
            ->willReturn([
                $this->makeHouseEntity(1, 'House A', 'villa', 3, 2, 5000),
                $this->makeHouseEntity(2, 'House B', 'flat', 2, 3, 4000),
            ]);

        $service = new HouseService($repo);
        $dtos = $service->getHouses();

        $this->assertCount(2, $dtos);
        $this->assertContainsOnlyInstancesOf(HouseDto::class, $dtos);
        $this->assertEquals(1, $dtos[0]->id);
        $this->assertEquals('House A', $dtos[0]->name);
        $this->assertEquals(5000, $dtos[0]->pricePerNightRub);
    }

    public function testSaveHousePersists(): void
    {
        $repo = $this->createMock(HouseRepository::class);

        $repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($entity) {
                return $entity instanceof HouseEntity && $entity->getName() === 'New House';
            }));

        $service = new HouseService($repo);
        $dto = new HouseDto(
            id: null,
            name: 'New House',
            type: 'villa',
            beds: 4,
            rowFromSea: 1,
            pricePerNightRub: 7777.0,
        );
        $saved = $service->saveHouse($dto);

        $this->assertInstanceOf(HouseDto::class, $saved);
        $this->assertEquals('New House', $saved->name);
        $this->assertEquals(4, $saved->beds);
    }
}
