<?php

namespace Meltir\ImdbRatingsScraper;

use Meltir\ImdbRatingsScraper\Interface\ItemInterface;

/**
 * @inheritDoc
 */
class Item implements ItemInterface
{
    public string $imdb_id;

    public int $rating;

    public string $reviewer;

    public function format(): ItemInterface
    {
        return $this;
    }
}