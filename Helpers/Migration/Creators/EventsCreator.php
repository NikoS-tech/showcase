<?php

namespace App\Helpers\Migration\Creators;

use Illuminate\Support\Facades\DB;

class EventsCreator extends EntityCreator
{
    const ENTITIES_DIR = '/events';
    const ENTITY_TYPE = 'EVENT';

    protected function getTypeStyles(): string
    {
        return 'fg=magenta';
    }

    protected function getEntitiesQuery(): string
    {
        $table = DB::connection()->getDatabaseName();
        return  "SHOW EVENTS WHERE db = '$table'";
    }
}
