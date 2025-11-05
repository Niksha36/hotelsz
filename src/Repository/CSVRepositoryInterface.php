<?php

namespace App\Repository;

interface CSVRepositoryInterface
{
    public function read(string $filename): array;
    public function write(string $filename, array $data): void;
    public function update(string $filename, string $id, array $newData): void;
    public function delete(string $filename, string $id): void;
    public function getCsvHeaders(string $path): array;
    public function getNextId(string $filename): string;
}
