<?php

declare(strict_types=1);

namespace Meltir\ImdbRatingsScraper;

use Meltir\ImdbRatingsScraper\Interface\ItemInterface;

// test test test
/**
 * @infection-ignore-all
 */
class Item implements ItemInterface
{
    public function __construct(public string $imdb_id, public int $rating, public string $reviewer)
    {
    }

    public function format(): ItemInterface
    {
        return $this;
    }
}
