<?php

namespace App\Helpers\Migration\Creators;

class FunctionsCreator extends EntityCreator
{
    const ENTITIES_DIR = '/functions';
    const ENTITY_TYPE = 'FUNCTION';

    protected function getTypeStyles(): string
    {
        return 'fg=blue';
    }
}
