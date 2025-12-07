<?php
namespace App\Services;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ViolationFormatter
{
    public function format(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            $errors[$path][] = $violation->getMessage();
        }

        return $errors;
    }
}
