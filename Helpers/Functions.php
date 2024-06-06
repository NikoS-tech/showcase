<?php

namespace Helpers;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

class Functions
{
    public static function getTimeInterval(float $startMicrotime): string
    {
        $period = microtime(true) - $startMicrotime;
        return CarbonInterval::millisecond($period * 1000)->cascade()->forHumans(['minimumUnit' => 'ms']);
    }

    public static function callByAttempts(int $times, callable $callable, int $delay = 60, $useTransactions = false): bool
    {
        return Collection::times($times)->some(function ($attempt) use ($callable, $delay, $times, $useTransactions) {
            try {
                $useTransactions && DB::beginTransaction();

                $callable($attempt);

                $useTransactions && DB::commit();

                return true;
            } catch (Throwable $e) {

                $useTransactions && DB::rollBack();

                if ($attempt === $times) {
                    throw $e;
                }

                sleep($delay);
                return false;
            }
        });
    }

    public static function toPublicProps(object $object): array
    {
        $props = self::getPublicProps($object);

        $result = [];
        foreach ($props as $prop) {
            $result[$prop] = $object->$prop;
        }

        return $result;
    }

    /**
     * @param object $object
     * @return array<string>
     */
    public static function getPublicProps(object $object): array
    {
        $reflect = new ReflectionClass($object);
        return array_map(function (ReflectionProperty $prop) {
            return $prop->name;
        }, $reflect->getProperties(ReflectionProperty::IS_PUBLIC));
    }

    public static function classToArray(Arrayable $object): array
    {
        $class = get_class($object);
        $attrs = get_class_vars($class);
        $values = get_object_vars($object);

        $result = [];
        $data = array_merge($attrs, $values);

        foreach (self::getPublicProps($object) as $prop) {
            $result[$prop] = method_exists($data[$prop], 'toArray') ? $data[$prop]->toArray() : $data[$prop];
        }

        return $result;
    }
}
