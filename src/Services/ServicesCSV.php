<?php

namespace App\Services;

use App\Repository\CSVRepository;

class ServicesCSV
{
    private CsvRepository $csvRepository;

    public function __construct(CsvRepository $csvRepository)
    {
        $this->csvRepository = $csvRepository;
    }

    public function readCSV(string $filename): array
    {
        return $this->csvRepository->read($filename);
    }

    public function writeCSV(string $filename, array $data): array
    {
        // Если есть заголовок 'id' и id не передан или пуст — назначить следующий id
        if ($this->isAssoc($data) &&
            (!array_key_exists('id', $data) || $data['id'] === '' || $data['id'] === null)) {

            $headers = $this->getCsvHeaders($filename);
            if (in_array('id', $headers, true)) {
                $data['id'] = $this->csvRepository->getNextId($filename);
            }
        }

        $this->csvRepository->write($filename, $data);
        return $data;
    }

    public function updateCsv(string $filename, string $id, array $newData): void
    {
        $this->csvRepository->update($filename, $id, $newData);
    }

    public function deleteById(string $filename, string $id): void
    {
        $this->csvRepository->delete($filename, $id);
    }

    private function isAssoc(array $arr): bool
    {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function getCsvHeaders(string $filename): array
    {
        $data = $this->csvRepository->read($filename);
        return !empty($data) ? array_keys($data[0]) : [];
    }
}
