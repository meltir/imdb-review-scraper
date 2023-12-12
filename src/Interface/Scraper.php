<?php

declare(strict_types=1);

namespace Meltir\ImdbRatingsScraper\Interface;

use GuzzleHttp\ClientInterface;
use Meltir\ImdbRatingsScraper\Exception\Scraper as ScraperException;

/**
 * Interface for the review scraper.
 *
 * @see Scraper
 */
interface Scraper
{
    /**
     * @param ClientInterface $client http client to use (guzzle)
     * @param string          $user   imdb user id
     */
    public function __construct(ClientInterface $client, string $user);

    /**
     * Set the url of the review page to be scanned.
     * This is setup by default as the first page of a users reviews in the constructor.
     */
    public function setUrl(string $url): void;

    /**
     * Get all movies from all pages. This can timeout !
     *
     * @return Item[]
     *
     * @throws ScraperException
     */
    public function getAllMovies(): array;

    /**
     * Process a single page of reviews.
     *
     * @return Item[]
     *
     * @throws ScraperException
     */
    public function getMovies(): array;

    /**
     * Get the url of the next page, or exception if not found.
     */
    public function getNextPage(): string|false;
}
