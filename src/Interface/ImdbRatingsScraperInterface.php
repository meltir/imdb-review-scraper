<?php

namespace Meltir\ImdbRatingsScraper\Interface;

use GuzzleHttp\ClientInterface;
use Meltir\ImdbRatingsScraper\Exception\ImdbRatingsScraperException;
use Meltir\ImdbRatingsScraper\ImdbRatingItem;
use Meltir\ImdbRatingsScraper\ImdbRatingsScraper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Interface for the review scraper
 * @see ImdbRatingsScraper
 */
interface ImdbRatingsScraperInterface
{

    /**
     * @param ClientInterface $client http client to use (guzzle)
     * @param string $user imdb user id
     */
    public function __construct(ClientInterface $client, string $user);


    /**
     * Set the url of the review page to be scanned.
     * This is setup by default as the first page of a users reviews in the constructor
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url): void;


    /**
     * Get all movies from all pages. This can timeout !
     *
     * @return ImdbRatingItemInterface[]
     * @throws ImdbRatingsScraperException
     */
    public function getAllMovies(): array;

    /**
     * Process a single page of reviews
     *
     * @return ImdbRatingItemInterface[]
     * @throws ImdbRatingsScraperException
     */
    public function getMovies(): array;

    /**
     * Get the url of the next page, or exception if not found
     *
     * @return string
     * @throws ImdbRatingsScraperException
     */
    public function getNextPage(): string;

}