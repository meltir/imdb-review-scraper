<?php

namespace Meltir\ImdbRatingsScraper\Interface;

use GuzzleHttp\ClientInterface;
use Tmdb\Client;

interface ImdbRatingsScraperInterface
{
    public function __construct(ClientInterface $client, int $user, Client $tmdb);

    public function setUrl(string $url);

    /**
     * @return ImdbRatingItemInterface[]
     */
    public function getMovies(): array;

    public function getNextPage(): string;

}