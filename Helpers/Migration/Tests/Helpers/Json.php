<?php

namespace App\Helpers\Migration\Tests\Helpers;


use App\Helpers\Migration\Tests\MigrationTest;

class Json
{
    protected string $result;
    protected string $standard;
    protected string $original;

    public function __construct($result, $standard, $original)
    {
        $this->result = $result;
        $this->original = $original;
        $this->standard = $standard;
    }

    protected function prepareDiffValue($value): string
    {
        return MigrationTest::prepareValue($value);
    }

    protected function prepareDiffMessage(array $values): string
    {
        return implode(' <info><></info> ', $values);
    }

    protected function getJsonDiff(array $needle, array $haystack, bool $reverse = false): ?array
    {
        $result = [];
        foreach ($needle as $key => $value) {
            if (is_array($value)) {
                $subDiff = $this->compareJson($value, $haystack[$key] ?? []);
                if ($subDiff) {
                    $result[$key] = $subDiff;
                }
                continue;
            }

            if (array_key_exists($key, $haystack) && $haystack[$key] === $value) {
                continue;
            }

            $data = [
                $this->prepareDiffValue($value),
                $this->prepareDiffValue($haystack[$key] ?? $this->undefinedKey($key)),
            ];

            if ($reverse) {
                $data = array_reverse($data);
            }

            $result[$key] = $this->prepareDiffMessage($data);
        }
        return $result;
    }

    protected function compareJson(array $result, array $standard): array
    {
        return array_merge(
            $this->getJsonDiff($result, $standard),
            $this->getJsonDiff($standard, $result, true)
        );
    }

    protected function compareByJson(): ?array
    {
        $result = $this->toJson($this->result);
        if (!$result) {
            return null;
        }

        $standard = $this->toJson($this->standard);
        if (!$standard) {
            return null;
        }

        return $this->compareJson($result, $standard);
    }

    protected function compareJsonKeys(array $result, array $standard): array
    {
        $difference = [];
        foreach ($standard as $k => $v) {
            $item = data_get($result, $k, $this->undefinedKey($k));
            $value = $this->prepareDiffValue($item);
            if ($value === $v) {
                continue;
            }
            if ($value === 'null' && $value === strtolower($v)) {
                continue;
            }
            if ($v === '!undefined' && $item === $this->undefinedKey($k)) {
                continue;
            }
            $difference[$k] = $this->prepareDiffMessage([$value, $v]);
        }
        return $difference;
    }

    protected function compareByJsonKeys(): ?array
    {
        $result = $this->toJson($this->result);
        if (!$result) {
            return null;
        }

        $standard = $this->toJsonKeys($this->original);
        if (!$standard) {
            return null;
        }

        return $this->compareJsonKeys($result, $standard);
    }

    public function compare(): ?array
    {
        $byJson = $this->compareByJson();
        if (is_array($byJson)) {
            return $byJson;
        }

        $byJsonKeys = $this->compareByJsonKeys();
        if (is_array($byJsonKeys)) {
            return $byJsonKeys;
        }

        return null;
    }

    protected function toJson(string $value): ?array
    {
        $json = json_decode($value, true);
        return is_array($json) && json_last_error() === JSON_ERROR_NONE ? $json : null;
    }

    protected function toJsonKeys(string $value): ?array
    {
        $data = [];
        foreach (explode("\n", $value) as $item) {
            $item = trim($item);
            $pair = explode(":", $item, 2);
            if (count($pair) !== 2) {
                dd("Wrong data passed as key-value pair: $item");
            }
            $data[trim($pair[0])] = trim($pair[1]);
        }

        return $data ?: null;
    }

    protected function undefinedKey(string $key): string
    {
        return sprintf("Undefined key: %s", $key);
    }
}