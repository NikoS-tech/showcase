<?php

namespace Models;

/** NDA */

abstract class Orm extends Model implements Choices, ActingStatuses
{
    use Times, ChoiceMutators, FilterScopes, MySqlUserExtend, NewEloquentBuilder, Url, Download, MemoryDebugger;

    /** NDA */
}
