<?php

namespace App\Helpers\Migration\Creators\Entities\Modifications;

use App\Http\Models\Logs;
use ReflectionClass;
use ReflectionException;
use Throwable;

abstract class Modification
{
    /**
     * @throws ReflectionException
     */
    protected static function instance(string $class, array $args): self
    {
        $reflection = new ReflectionClass($class);
        return $reflection->newInstanceArgs($args);
    }

    public static function modifyWith(array $modifications, string $content): string
    {
        try {
            foreach ($modifications as $modification => $args) {
                if (!is_array($args)) {
                    $args = [];
                }

                $content = self::instance($modification, $args)->modify($content);
            }
        } catch (Throwable $e) {
            $message = Logs::getErrorText($e);
            abort(500, $message);
        }

        return $content;
    }

    abstract public function modify(string $content): string;
}