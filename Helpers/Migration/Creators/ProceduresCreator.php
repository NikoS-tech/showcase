<?php

namespace App\Helpers\Migration\Creators;

class ProceduresCreator extends EntityCreator
{
    const ENTITIES_DIR = '/procedures';
    const ENTITY_TYPE = 'PROCEDURE';

    protected function getTypeStyles(): string
    {
        return 'fg=yellow';
    }
}
