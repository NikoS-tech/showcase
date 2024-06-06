<?php

namespace App\Helpers\Migration\Creators\Entities;

use App\Helpers\Migration\Creators\Entities\Modifications\Debugger;
use App\Helpers\Migration\Creators\Entities\Modifications\Modification;
use App\Helpers\Migration\Creators\Entities\Modifications\Tests;
use App\Helpers\Migration\Creators\Entities\Modifications\Variables;
use App\Helpers\Migration\Creators\EntityCreator;
use SplFileInfo;

class Entity
{
    protected SplFileInfo $file;
    protected EntityCreator $creator;

    protected string $name;
    protected string $filename;

    protected string $path;
    protected string $relativePath;

    protected ?string $content = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        if (is_null($this->content)) {
            $this->setContent();
        }

        return $this->content;
    }

    protected function getVariables(): array
    {
        return $this->creator->getVariables($this);
    }

    public function __construct(EntityCreator $creator, SplFileInfo $file)
    {
        $this->file = $file;
        $this->creator = $creator;

        $this->filename = $this->file->getFilename();
        $this->relativePath = $this->file->getRelativePath();

        $this->setup();
    }

    protected function setup(): void
    {
        $this->setName();
        $this->setPath();
    }

    protected function setName(): void
    {
        $this->name = str_replace('.sql', '', $this->filename);
    }

    protected function setPath(): void
    {
        $path = $this->creator->getPath();
        if ($this->relativePath) {
            $path .= "/$this->relativePath";
        }
        $this->path = "$path/$this->name.sql";
    }

    protected function setContent(): void
    {
        $content = file_get_contents($this->path) ?: '';
        if (!$content) {
            $this->creator->abort("Could not get $this->filename content.");
        }
        $this->content = $this->modifyContent($content);
    }

    protected function modifiers(): array
    {
        $variables = $this->getVariables();
        $type = $this->creator->getType();
        $command = $this->creator->getCommand();

        return [
            Variables::class => [$variables, $this->path],
            Debugger::class  => [$type, $this->name],
            Tests::class     => [$command, $type, $this->name],
        ];
    }

    protected function modifyContent(string $content): string
    {
        $modifiers = $this->modifiers();
        return Modification::modifyWith($modifiers, $content);
    }
}
