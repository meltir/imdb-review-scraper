<?php

namespace Meltir\ImdbRatingsScraper;

class ImdbCrawler
{
    protected const IMDB_RATINGS_URI_PREFIX = 'https://www.imdb.com/user/';
    protected const IMDB_RATINGS_URI_SUFFIX = '/ratings';
    protected const IMDB_BASE_URI = 'https://www.imdb.com';
    protected const FILTER_NEXT_PAGE = /* @lang CSS */
        '#ratings-container > div.footer.filmosearch > div > div > a.flat-button.lister-page-next.next-page';
    protected const FILTER_RATING_ITEM = /* @lang CSS */
        'div.ipl-rating-star.ipl-rating-star--other-user.small > span.ipl-rating-star__rating';
    protected const FILTER_RATING_CONTAINER = /* @lang CSS */
        '#ratings-container > div.lister-item';
    protected const FILTER_RATING_LINK = /* @lang CSS */
        'h3 > a';
    protected const REGEX_IMDB_ID = /* @lang RegExp */
        '@.*title/(.*)/.*@';

    public function getNextPage(ImdbCrawler $current_page): string|false
    {
        try {
            return $current_page
                ->filter(self::FILTER_NEXT_PAGE)
                ->link()
                ->getUri();
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    public function getUrl(ClientInterface $client, RequestFactoryInterface $requestFactory, string $url): ImdbCrawler
    {
        try {
            $request = $requestFactory->createRequest('GET', $url);
            return new ImdbCrawler(
                node: $client->sendRequest($request)->getBody()->getContents(),
                uri: self::IMDB_BASE_URI
            );
        } catch (ClientExceptionInterface $e) {
            throw new ScraperException(
                message: 'Could not connect to imdb',
                code: ScraperException::CODE_MAP['COULD_NOT_CONNECT'],
                previous: $e
            );
        }
    }

    public function processItem(ImdbCrawler $item, string $user): ItemInterface|false
    {
        try {
            $link = $item->filter(self::FILTER_RATING_LINK)->link()->getUri();
            try {
                $id = (string)preg_replace(self::REGEX_IMDB_ID, '\\1', $link);
                $movie = new Item(
                    imdb_id: $id,
                    rating: (int)$item->filter(self::FILTER_RATING_ITEM)->text(),
                    reviewer: $user
                );
            } catch (\InvalidArgumentException $e) {
                throw new ScraperException(
                    message: 'Could not scrape this movie',
                    code: ScraperException::CODE_MAP['MOVIE_FAILED'],
                    previous: $e
                );
            }
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return $movie;
    }
}
