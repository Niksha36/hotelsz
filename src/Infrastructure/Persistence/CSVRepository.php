<?php

namespace App\Infrastructure\Persistence;

use App\Repository\CSVRepositoryInterface;

class CSVRepository implements CSVRepositoryInterface
{
    private string $csvDirectory;

    public function __construct(string $csvDirectory)
    {
        $this->csvDirectory = $csvDirectory;
    }
    private function buildPath(string $filename): string
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.csv';
        }
        return $this->csvDirectory . '/' . $filename;
    }

    public function read(string $filename): array
    {
        $path = $this->buildPath($filename);

        if (!file_exists($path)) {
            throw new \RuntimeException("Файл '{$filename}' не существует или не найден");
        }

        $data = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }

        return $data;
    }
    public function write(string $filename, array $data): void
    {
        $path = $this->buildPath($filename);

        if (!file_exists($path)) {
            throw new \RuntimeException("Файл '{$filename}' не существует или не найден");
        }

        if (empty($data)) {
            throw new \InvalidArgumentException("Данные не могут быть пустыми");
        }

        $expectedHeaders = $this->getCsvHeaders($path);

        if ($this->isAssoc($data)) {
            $row = [];
            foreach ($expectedHeaders as $header) {
                $row[] = array_key_exists($header, $data) ? (string)$data[$header] : '';
            }
        } else {
            if (count($data) !== count($expectedHeaders)) {
                throw new \InvalidArgumentException(
                    sprintf("Ожидалось %d полей, получено %d", count($expectedHeaders), count($data))
                );
            }
            $row = array_values($data);
        }

        if (($handle = fopen($path, 'a')) !== false) {
            fputcsv($handle, $row);
            fclose($handle);
        }
    }

    public function update(string $filename, string $id, array $newData): void
    {
        $path = $this->buildPath($filename);

        $data = $this->read($filename);
        $updated = false;

        foreach ($data as $index => $row) {
            if ((string)$row['id'] === (string)$id) {
                $data[$index] = array_merge($row, $newData);
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            throw new \RuntimeException("Запись с id - '$id' не была найдена");
        }

        if (($handle = fopen($path, 'w')) !== false) {
            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }
    }

    public function delete(string $filename, string $id): void
    {
        $path = $this->buildPath($filename);

        $data = $this->read($filename);
        $deleted = false;

        foreach ($data as $index => $row) {
            if (isset($row['id']) && $row['id'] === $id) {
                unset($data[$index]);
                $deleted = true;
                break;
            }
        }

        if (!$deleted) {
            throw new \RuntimeException("Запись с id - '$id' не была найдена");
        }

        $data = array_values($data);

        if (($handle = fopen($path, 'w')) !== false) {
            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }
    }

    public function getCsvHeaders(string $path): array
    {
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            fclose($handle);

            if ($headers === false || empty($headers)) {
                throw new \RuntimeException("Файл не содержит заголовков");
            }

            return $headers;
        }

        throw new \RuntimeException("Не удалось прочитать файл при получении заголовков");
    }

    private function isAssoc(array $arr): bool
    {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    public function getNextId(string $filename): string
    {
        $existing = $this->read($filename);
        $maxId = 0;

        foreach ($existing as $row) {
            if (isset($row['id'])) {
                $id = (int)$row['id'];
                if ($id > $maxId) $maxId = $id;
            }
        }

        return (string)($maxId + 1);
    }
}
