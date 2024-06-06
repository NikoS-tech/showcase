<?php


use App\Helpers\Functions;
use App\Http\Models\Logs;
use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use ReflectionObject;

/** @property array<string, Submodel> $casts */
abstract class Submodel implements CastsAttributes, Arrayable
{
    protected array $casts = [];
    protected array $array_casts = [];

    public function fill(array $data): self
    {
        $reflection = new ReflectionObject($this);
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }

            if (array_key_exists($key, $this->casts)) {
                $value = $this->cast($key, $value, $data);
            }

            if (array_key_exists($key, $this->array_casts)) {
                $value = is_array($value) ? array_map(function ($item) use ($key) {
                    $caster = $this->array_casts[$key];
                    return class_exists($caster) ? (new $caster)->fill($item) : $item;
                }, $value) : $value;
            }

            if (is_null($value) && !$reflection->getProperty($key)->getType()->allowsNull()) {
                continue;
            }

            try {
                $this->{$key} = $value;
            } catch (Throwable $error) {
                Logs::saveError($error);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = Functions::classToArray($this);

        foreach ($this->array_casts as $key => $cast) {
            $data[$key] = is_array($data[$key]) ? array_map(function ($item) {
                return is_subclass_of($item, Submodel::class) ? $item->toArray() : $item;
            }, $data[$key]) : $data[$key];
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toOrigin(): array
    {
        $data = [];
        foreach (Functions::getPublicProps($this) as $prop) {
            $value = $this->$prop ?? null;

            if (array_key_exists($prop, $this->casts)) {
                $value = $this->uncast($prop, $value, $data);
            }

            if (is_string($value) && $json = json_decode($value, true)) {
                $value = $json;
            }

            if (is_null($value)) {
                continue;
            }

            $data[$prop] = $value;
        }

        return $data;
    }

    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param string $key
     * @param string $value
     * @param array $attributes
     * @return Submodel
     * @throws Throwable
     */
    public function get($model, string $key, $value, array $attributes): ?Submodel
    {
        $data = is_array($value) ? $value : json_decode($value, true);
        return (new static)->fill($data ?: []);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param Submodel $value
     * @param array $attributes
     * @return string
     * @throws Exception
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value instanceof static) {
            $data = $value->toOrigin();
            return json_encode($data);
        }

        $className = get_class($model);
        $json = json_encode($value);

        throw new Exception("Incorrect cast value: $json (encoded as json) for key '$key' in $className");
    }

    protected function cast(string $key, $value, array $attributes)
    {
        $caster = $this->casts[$key];
        return class_exists($caster) ? (new $caster)->get($this, $key, $value, $attributes) : $value;
    }

    private function uncast(string $key, $value, array $attributes)
    {
        $caster = $this->casts[$key];
        return class_exists($caster) ? (new $caster)->set($this, $key, $value, $attributes) : $value;
    }
}
