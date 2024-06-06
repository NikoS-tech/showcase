<?php

namespace App\Helpers\Migration\Creators;

use App\Console\Migration\MigrateCommand;
use App\Helpers\Migration\Creators\Entities\Entity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use SplFileInfo;

abstract class EntityCreator extends Migration
{
    protected string $path;
    protected MigrateCommand $command;

    protected array $entities = [];

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return static::ENTITY_TYPE;
    }

    protected function getTypeStyles(): string
    {
        return 'fg=red';
    }

    protected function getStyledType(): string
    {
        $type = $this->getType();
        $styles = $this->getTypeStyles();
        return "<$styles>$type</>";
    }

    public function getVariables(Entity $entity): array
    {
        return config('migrations');
    }

    public function getCommand(): MigrateCommand
    {
        return $this->command;
    }

    public function __construct(MigrateCommand $command)
    {
        $this->validate();

        $this->command = $command;
        $this->path = database_path('migrations') . static::ENTITIES_DIR;

        $this->prepareEntities();
    }

    protected function prepareEntities(): void
    {
        $query = $this->getEntitiesQuery();
        $entities = DB::select($query);

        $this->entities = collect($entities)->keyBy($this->setEntitiesKeys())->toArray();
    }

    protected function getEntitiesQuery(): string
    {
        $type = $this->getType();
        $table = DB::connection()->getDatabaseName();

        return "SHOW $type STATUS WHERE db = '$table'";
    }

    protected function setEntitiesKeys(): callable
    {
        return function ($item) {
            return strtolower($item->Name);
        };
    }

    public function up(): void
    {
        $files = File::allFiles($this->path);
        foreach ($files as $file) {
            $this->create($file);
        }
        $this->finish();
    }

    protected function create(SplFileInfo $file): void
    {
        $entity = new Entity($this, $file);
        $name = $entity->getName();

        if (!$this->command->isNotInLimit($name)) {
            return;
        }

        $content = $entity->getContent();
        if ($this->command->isOnlyTesting()) {
            return;
        }

        if ($this->isNotDirty($name, $content)) {
            return;
        }

        $this->command::addChanged([$this->getStyledType(), $name]);
        $this->drop($name);
        if ($content) {
            $this->call($content);
        }
    }

    protected function isNotDirty(string $entityName, string $content): bool
    {
        $type = $this->getType();

        if (!isset($this->entities[strtolower($entityName)])) {
            return false;
        }

        $current = DB::selectOne("SHOW CREATE $type $entityName");
        $key = $type !== TriggersCreator::ENTITY_TYPE ? 'Create ' . ucfirst(strtolower($type)) : 'SQL Original Statement';

        if (!property_exists($current, $key)) {
            $this->abort("Wrong way to check changes for $type entity type");
        }

        $content = $this->prepareEntityContent($content);
        $currentContent = $this->prepareEntityContent($current->{$key});

        return $content === $currentContent;
    }

    protected function prepareEntityContent(string $content): string
    {
        $content = str_replace([
            'returns tinyint(1)',
            'returns int(11)',
            'definer=`root`@`localhost`',
        ], [
            'returns boolean',
            'returns int',
            '',
        ], strtolower($content));

        if (strpos($content, '#') !== false) {
            $content = preg_replace('/#.*$/m', '', $content);
        }

        if ($this->getType() === EventsCreator::ENTITY_TYPE) {
            $content = preg_replace('/starts \'.*\' on/m', 'on', $content);
        }

        return utf8_encode(trim(trim(str_replace(["\n", ' '], '', $content)), ';'));
    }

    protected function drop($entityName): void
    {
        DB::unprepared('DROP ' . $this->getType() . ' IF EXISTS ' . $entityName);
    }

    protected function call(string $content): void
    {
        DB::unprepared($content);
    }

    protected function validate(): void
    {
        $creator = static::class;
        if (!defined("$creator::ENTITIES_DIR")) {
            $this->abort("Entities dir path error: $creator");
        }

        if (!defined("$creator::ENTITY_TYPE")) {
            $this->abort('Entities type error: ' . $creator);
        }
    }

    protected function finish(): void
    {
        if ($this->command->isNotOnlyTesting()) {
            $this->command->warning("All entities type (" . $this->getType() . ") successfully called");
        }
    }

    public function abort(string $message): void
    {
        $this->command->abort($message);
    }
}
