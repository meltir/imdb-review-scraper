<?php

namespace Meltir\ImdbRatingsScraper;

use Meltir\ImdbRatingsScraper\Interface\ImdbRatingItemInterface;

/**
 * @inheritDoc
 */
class ImdbRatingItem implements ImdbRatingItemInterface
{
    public string $imdb_id;

    public int $rating;

    public string $reviewer;

    public function format(): ImdbRatingItemInterface
    {
        return $this;
    }
}