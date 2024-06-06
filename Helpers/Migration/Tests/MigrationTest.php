<?php

namespace App\Helpers\Migration\Tests;

use App\Helpers\Migration\Tests\Helpers\Json;
use Illuminate\Support\Facades\DB;

class MigrationTest
{
    /**
     * @var $result mixed
     */
    protected $result;

    /**
     * @var $original mixed
     */
    protected $original;

    /**
     * @var $standard mixed
     */
    protected $standard;

    protected string $query;
    protected string $type;
    protected string $name;

    protected array $difference = [];

    public function __construct(string $name, string $type, string $query, $standard)
    {
        $this->query = $query;
        $this->type = $type;
        $this->original = $standard;
        $this->standard = str_replace("\n", '', $standard);
        $this->name = $name;
    }

    public function test(): bool
    {
        $this->call();
        return $this->check();
    }

    public function error(): string
    {
        $result = self::prepareValue($this->result);
        $naming = "$this->type: $this->name";
        $data = "standard: $this->standard and result: $result are not equal | $this->query";
        return "$naming -> $data" . $this->getDifferenceText($this->difference) . "\n";
    }

    protected function getDifferenceText(array $difference): string
    {
        $difference = $this->getDifference($difference);
        if ($difference) {
            $difference = "\n\nJson Difference: $difference";
        }
        return $difference;
    }

    protected function getDifference(array $difference): string
    {
        if (!$difference) {
            return '';
        }

        $list = '';
        foreach ($difference as $key => $value) {
            $deep = '';
            if (is_array($value)) {
                $value = $this->getDifference($value);
                $deep = "\n";
            }
            $list .= "\n$key <info>=></info> [{$value}{$deep}]";
        }

        return $list;
    }

    protected function call(): void
    {
        $result = DB::selectOne($this->query);
        $vars = get_object_vars($result);
        $key = array_key_first($vars);
        $this->result = $vars[$key];
    }

    protected function check(): bool
    {
        switch (gettype($this->result)) {
            case 'integer':
            case 'double':
                return $this->compareNumbers();
            case 'string':
                return $this->compareJson() ?? $this->compareStrings();
            case 'NULL':
                return strtolower($this->standard) === 'null';
            default:
                return $this->result == $this->standard;
        }
    }

    protected function compareJson(): ?bool
    {
        $comparator = new Json($this->result, $this->standard, $this->original);
        $difference = $comparator->compare();
        if ($difference) {
            $this->difference = $difference;
        }
        return !is_null($difference) ? !$difference : null;
    }

    protected function compareStrings(): bool
    {
        return (string)$this->result === (string)$this->standard;
    }

    protected function compareNumbers(): bool
    {
        return $this->result === +$this->standard;
    }

    public static function prepareValue($value): string
    {
        if ($value === '') {
            return "''";
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return "[\n\t" . implode(",\n\t", $value) . "\n]";
        }

        return (string)$value;
    }
}