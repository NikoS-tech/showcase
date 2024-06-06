<?php

namespace App\Helpers\Migration\Creators\Entities\Modifications;

class Variables extends Modification
{
    protected array $variables;
    protected string $path;

    public function __construct(array $variables, string $path)
    {
        $this->variables = $variables;
        $this->path = $path;
    }

    public function modify(string $content): string
    {
        $content = $this->setVariables($content);
        $this->validate($content);
        return $content;
    }

    protected function setVariables(string $content): string
    {
        foreach ($this->variables as $key => $variable) {
            $content = str_replace(["''{{", "{{\$$key}}"], ['{{', $variable], $content);
        }
        return $content;
    }

    protected function validate(string $content): void
    {
        $position = strripos($content, "{{");
        if ($position === false) {
            return;
        }

        $position = $position <= 20 ? 0 : $position - 20;
        $text = substr($content, $position, 200);

        $message = <<<MSG
File: $this->path
Not all variables were declared.

$text
MSG;
        abort(500, $message);
    }
}