<?php

namespace App\Helpers\Migration\Creators\Entities\Modifications;

use App\Console\Migration\MigrateCommand;
use App\Helpers\Migration\Tests\MigrationTest;

class Tests extends Modification
{
    const TEST_CASES_IDENTIFIER = '-- TEST CASES --';
    const TEST_CASE_SEPARATOR = '**/';
    const TEST_VALUE_SEPARATOR = '/**';

    protected MigrateCommand $command;

    protected string $type;
    protected string $name;

    public function __construct(MigrateCommand $command, string $type, string $name)
    {
        $this->command = $command;
        $this->type = $type;
        $this->name = $name;
    }

    public function modify(string $content): string
    {
        return $this->explode($content);
    }

    protected function explode(string $content): string
    {
        $data = explode(self::TEST_CASES_IDENTIFIER, $content);
        if ($this->command->isTesting()) {
            $this->setTests($data[1] ?? '');
        }

        return $data[0];
    }

    protected function setTests(string $content): void
    {
        if (!trim($content)) {
            return;
        }

        $cases = explode(self::TEST_CASE_SEPARATOR, $content);
        foreach ($cases as $index => $case) {
            if (!trim($case)) {
                continue;
            }

            $test = $this->prepareTest($case, $index);
            MigrateCommand::addTest($test);
        }
    }

    protected function prepareTest(string $case, int $index): MigrationTest
    {
        $data = explode(self::TEST_VALUE_SEPARATOR, $case);
        if (count($data) !== 2) {
            dd("Test $index in $this->name ($this->type) is broken: $case");
        }
        $data[1] = trim($data[1]);
        return $this->createTest($data[0], $data[1]);
    }

    protected function createTest(string $query, $standard): MigrationTest
    {
        return new MigrationTest($this->name, $this->type, $query, $standard);
    }
}