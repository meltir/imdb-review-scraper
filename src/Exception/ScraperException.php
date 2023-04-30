<?php

namespace Meltir\ImdbRatingsScraper\Exception;

use Exception;

class ScraperException extends \Exception
{
    /**
     * This makes exception codes human-readable
     * These should be http'ish.
     */
    public const CODE_MAP = [
        'END_OF_PAGE' => 200,
        'MOVIE_FAILED' => 500,
        'COULD_NOT_CONNECT' => 502,
        'COULD_NOT_SCRAPE' => 503,
        'NO_NEXT_PAGE' => 201,
        'NO_RATINGS' => 404,
    ];
}
