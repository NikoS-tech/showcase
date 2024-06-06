<?php

namespace App\Http\Models\Submodels\Feed;

use App\Http\Models\Casts\Choice;
use App\Http\Models\Feeds;
use App\Http\Models\Geotargets;
use App\Http\Models\Submodels\Submodel;

/**
 * @property FeedOptions $options
 */
class FeedOptions extends Submodel
{
    protected array $casts = [
        'useItemGroupId'    => Choice::class,
        'substitutions'     => FeedSubstitutions::class,
        'customCurlOptions' => 'array'
    ];

    public string $siteUrl = '';
    public ?string $imageUrl = '';
    public ?string $userAgent = '';
    public bool $feedUpdateErrorNotifications = true;
    public bool $convertNonUTF8Characters = false;
    public bool $useItemGroupId = false;
    public ?string $httpUsername = '';
    public ?string $httpPassword = '';
    public string $importType = Feeds::IMPORT_TYPE_XML;
    public ?string $delimiter = Feeds::DELIMITER_AUTO;
    public ?string $attachment = '';
    public ?int $maxCountImportedProduct = null;
    public ?int $pseudoLimitImportedProduct = null;
    public float $productVat = Geotargets::DEFAULT_VAT;
    public float $limitOfDeletedMatches = Feeds::PERCENTAGE_LIMIT_OF_DELETED_MATCHES;
    public float $limitOfDeletedProducts = Feeds::PERCENTAGE_LIMIT_OF_DELETED_PRODUCTS;
    public bool $enableRuleBlockDatafeed = true;
    public ?string $productFieldName = '';
    public ?array $customCurlOptions = null;

    public FeedSubstitutions $substitutions;

    public function __construct()
    {
        $this->substitutions = new FeedSubstitutions();
    }
}
