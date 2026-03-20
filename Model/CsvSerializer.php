<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

class CsvSerializer
{
    public function serialize(array $array): string
    {
        return implode(', ', $array);
    }

    public function unserialize(string $string): array
    {
        $string = trim($string);

        if ($string === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $string)), fn(string $v) => $v !== '');
    }
}
