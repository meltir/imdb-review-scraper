<?php

namespace Meltir\ImdbRatingsScraper;


use Meltir\ImdbRatingsScraper\Interface\ImdbRatingItemInterface;

class ImdbRatingItem implements ImdbRatingItemInterface
{
    /**
     * Movie title
     * @var string
     */
    public string $title;

    /**
     * Link to imdb movie listing
     * @var string
     */
    public string $link;

    /**
     * Link to imdb movie poster
     * @var string
     */
    public string $image;

    /**
     * Release date (usually in the format (YYYY) for movies or (YYYY-YYYY) for series)
     * @var string
     */
    public string $year;

    /**
     * Movie description
     *
     * @var string
     */
    public string $body;

    /**
     * Rating the user gave the movie
     * @var int
     */
    public int $rating;

    /**
     * Hook used by decorators
     * @return ImdbRatingItemInterface
     */
    public function format(): ImdbRatingItemInterface
    {
        return $this;
    }
}