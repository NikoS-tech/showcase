<?php

namespace App\Http\Models\Submodels\Feed;

use App\Http\Models\Submodels\Submodel;

class FeedSubstitutions extends Submodel
{
    public bool $isSubstitutionOn = false;
    public array $substitutionProductFields = [];
    public bool $substituteIfFeedUpdated = false;
}