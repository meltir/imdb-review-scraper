<?php

namespace Meltir\ImdbRatingsScraper\Interface;

/**
 * Interface ImdbRatingItemInterface.
 *
 * @property string $imdb_id  identifier
 * @property int    $rating   user rating
 * @property string $reviewer review author
 */
interface ItemInterface
{
    /**
     * Hook for decorators.
     */
    public function format(): ItemInterface;
}
