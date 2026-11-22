<?php declare(strict_types=1);

namespace App\Support;

final class Validate
{
    /**
     * @param array<string, mixed> $data
     */
    public static function requiredString(array $data, string $key, string $label, int $max = 255): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new HttpException(422, "O campo {$label} é obrigatório.", ['campo' => $key]);
        }

        $value = trim($value);

        if (mb_strlen($value, 'UTF-8') > $max) {
            throw new HttpException(422, "O campo {$label} deve ter no máximo {$max} caracteres.", ['campo' => $key]);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function optionalString(array $data, string $key, string $label, int $max = 65535): ?string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (!is_string($data[$key])) {
            throw new HttpException(422, "O campo {$label} deve ser um texto.", ['campo' => $key]);
        }

        $value = trim($data[$key]);

        if (mb_strlen($value, 'UTF-8') > $max) {
            throw new HttpException(422, "O campo {$label} deve ter no máximo {$max} caracteres.", ['campo' => $key]);
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key, string $label, bool $required = true): ?int
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            if ($required) {
                throw new HttpException(422, "O campo {$label} é obrigatório.", ['campo' => $key]);
            }

            return null;
        }

        $id = self::toPositiveInt($value);

        if ($id === null) {
            throw new HttpException(422, "O campo {$label} deve ser um número inteiro positivo.", ['campo' => $key]);
        }

        return $id;
    }

    /**
     * aceita uma lista de ids ou uma lista de obj com "codigo_componente".
     *
     * @param array<string, mixed> $data
     * @return list<int>
     */
    public static function componentIdList(array $data, string $key, string $label): array
    {
        $value = $data[$key] ?? null;

        if (!is_array($value)) {
            throw new HttpException(422, "O campo {$label} deve ser uma lista de códigos de componentes.", ['campo' => $key]);
        }

        $ids = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['codigo_componente'] ?? $item['id'] ?? null;
            }

            $id = self::toPositiveInt($item);

            if ($id === null) {
                throw new HttpException(422, "Cada item de {$label} deve ser um código de componente válido.", ['campo' => $key]);
            }

            $ids[$id] = $id;
        }

        if ($ids === []) {
            throw new HttpException(422, 'O equipamento deve possuir pelo menos um componente.', ['campo' => $key]);
        }

        return array_values($ids);
    }

    private static function toPositiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $id = (int) trim($value);

            return $id > 0 ? $id : null;
        }

        if (is_float($value) && floor($value) === $value) {
            return $value > 0 ? (int) $value : null;
        }

        return null;
    }
}
