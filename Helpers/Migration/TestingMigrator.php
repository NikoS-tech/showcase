<?php

namespace App\Helpers\Migration;

use App\Helpers\Counter;
use App\Http\Models\Logs;
use Closure;
use App\Console\Migration\MigrateCommand;
use Throwable;

class TestingMigrator
{
    const COUNTER_SUCCESS = 'success';
    const COUNTER_ERRORS = 'errors';
    const COUNTERS = [
        self::COUNTER_ERRORS,
        self::COUNTER_SUCCESS,
    ];

    protected MigrateCommand $command;

    protected Closure $action;
    protected string $default;

    protected ?string $database = null;

    public function __construct(MigrateCommand $command, Closure $action)
    {
        $this->command = $command;
        $this->action = $action;
    }

    public function migrate(): void
    {
        try {
            $this->action->call($this->command);
            $this->test();
        } catch (Throwable $e) {
            abort(500, Logs::getErrorText($e));
        }
    }

    private function test(): void
    {
        $this->command->separator();
        $this->command->tip("Testing");

        $tests = MigrateCommand::getTests();

        if (empty($tests)) {
            $this->command->tip("No tests found");
            $this->command->separator();
            return;
        }

        $counter = new Counter(self::COUNTERS);
        foreach ($tests as $test) {
            if ($test->test()) {
                $counter->up(self::COUNTER_SUCCESS);
                continue;
            }

            $counter->up(self::COUNTER_ERRORS);
            $this->command->attention("\nError occurred:" . $test->error());
            $this->command->separator();
        }

        $tip = "Results: %s error(s) occurred and %s test(s) are complete successfully";
        $tip = vsprintf($tip, $counter->get(self::COUNTERS));

        $this->command->tip($tip);
        $this->command->separator();

        if ($counter->get(self::COUNTER_ERRORS)) {
            $this->command->abort('Errors occurred while testing');
        }
    }
}