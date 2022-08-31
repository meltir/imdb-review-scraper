<?php

/**
 * Imdb scraper that looks up a users publicly available reviews and scrapes them.
 *
 * Licence:
 * This is an experiment/exercise in building a composer package, and setting it up.
 * You can look, but you cannot touch, run, analyse, lick or compile.
 * I don't care enough to chase down bots (or anyone for that matter), but for the record: BAD BOT.
 * This is scraping publicly available page's movie id's and review values, and does not use
 * descriptions/posters/other metadata from IMDB
 * Please don't sue me.
 *
 * If you want to do this, get the official, commercial (IMDB api)[https://developer.imdb.com/].
 * Consider this an unholy closed half-MIT licence.
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @license this is mine and nobody has my permission to use it or republish it in parts or whole anywhere ever.
 * @author Lukasz Andrzejak <spam@meltir.com>
 * @copyright (C) 2022 Lukasz Andrzejak <spam@meltir.com>
 */

namespace Meltir\ImdbRatingsScraper;

use Meltir\ImdbRatingsScraper\Interface\ItemInterface;
use Meltir\ImdbRatingsScraper\Interface\ScraperInterface;
use Meltir\ImdbRatingsScraper\Exception\ScraperException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;
use InvalidArgumentException;

/**
 * IMDB user review scraper
 * Only uses publicly available information from non-protected user pages.
 */
class Scraper implements ScraperInterface
{
    /**
     * @var string url currently scraped
     */
    protected string $url;

    /**
     * @var Crawler page currently parsed
     */
    protected Crawler $current_page;

    /**
     * Where the user profile lives on imdb.
     */
    protected const IMDB_RATINGS_URI_PREFIX = 'https://www.imdb.com/user/';

    /**
     * Where the user ratings live in a users profile.
     */
    protected const IMDB_RATINGS_URI_SUFFIX = '/ratings';

    /**
     * Where the base url is for parsing urls.
     */
    protected const IMDB_BASE_URI = 'https://www.imdb.com';

    /**
     * Css selector to find the next page link.
     */
    protected const FILTER_NEXT_PAGE = /* @lang CSS */
        '#ratings-container > div.footer.filmosearch > div > div > a.flat-button.lister-page-next.next-page';

    /**
     * Css selector to find an individual movie rating.
     */
    protected const FILTER_RATING_ITEM = /* @lang CSS */
        'div.ipl-rating-star.ipl-rating-star--other-user.small > span.ipl-rating-star__rating';

    /**
     * Css selector to find the ratings list.
     */
    protected const FILTER_RATING_CONTAINER = /* @lang CSS */
        '#ratings-container > div.lister-item';

    protected const FILTER_RATING_LINK = /* @lang CSS */
        'h3 > a';

    protected const REGEX_TITLE = /* @lang RegExp */
        '@.*title/(.*)/.*@';

    /**
     * @param ClientInterface $client http client to use (guzzle)
     * @param string          $user   imdb user id
     */
    public function __construct(protected ClientInterface $client, protected string $user)
    {
        $this->url = self::IMDB_RATINGS_URI_PREFIX.$user.self::IMDB_RATINGS_URI_SUFFIX;
    }

    /**
     * Set the url of the review page to be scanned.
     * This is setup by default as the first page of a users reviews in the constructor.
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Fetch page via a http client and return out a web crawler that parsed it.
     *
     * @throws ScraperException
     */
    private function getUrl(): Crawler
    {
        try {
            return new Crawler(
                node: $this
                    ->client
                    ->request('GET', $this->url)
                    ->getBody()
                    ->getContents(),
                uri: self::IMDB_BASE_URI
            );
        } catch (GuzzleException $e) {
            throw new ScraperException(message: 'Could not connect to imdb', code: ScraperException::CODE_MAP['COULD_NOT_CONNECT'], previous: $e);
        }
    }

    /**
     * Process an individual entry.
     *
     * @param Crawler $item a movie and its rating
     *
     * @return Item
     *
     * @throws ScraperException
     */
    private function processItem(Crawler $item): ItemInterface
    {
        try {
            $link = $item->filter(self::FILTER_RATING_LINK)->link()->getUri();
            try {
                $movie = new Item(
                    preg_replace(self::REGEX_TITLE, '\\1', $link),
                    (int) $item->filter(self::FILTER_RATING_ITEM)->text(),
                    $this->user
                );
            } catch (InvalidArgumentException $e) {
                throw new ScraperException(message: 'Could not scrape this movie', code: ScraperException::CODE_MAP['MOVIE_FAILED'], previous: $e);
            }
        } catch (InvalidArgumentException $e) {
            throw new ScraperException(message: 'No more movies on this list', code: ScraperException::CODE_MAP['END_OF_PAGE'], previous: $e);
        }

        return $movie;
    }

    /**
     * Get all movies from all pages. This can timeout !
     *
     * @return Item[]
     *
     * @throws ScraperException
     */
    public function getAllMovies(): array
    {
        $movies = $this->getMovies();
        try {
            while ($url = $this->getNextPage()) {
                $this->setUrl($url);
                $movies = array_merge($movies, $this->getMovies());
            }
        } catch (ScraperException $e) {
            if ($e->getCode() === ScraperException::CODE_MAP['NO_NEXT_PAGE']) {
                return $movies;
            }
            throw $e;
        }
    }

    /**
     * Process a single page of reviews.
     *
     * @return Item[]
     *
     * @throws ScraperException
     */
    public function getMovies(): array
    {
        $this->current_page = $this->getUrl();
        $item = $this->current_page->filter(self::FILTER_RATING_CONTAINER);

        $movies = [];
        try {
            while ($movies[] = $this->processItem($item)) {
                $item = $item->nextAll();
            }
        } catch (ScraperException $e) {
            if ($e->getCode() !== ScraperException::CODE_MAP['END_OF_PAGE']) {
                throw $e;
            }
        }
        return $movies;
    }

    /**
     * Get the url of the next page, or exception if not found.
     *
     * @throws ScraperException
     */
    public function getNextPage(): string
    {
        try {
            return $this
                ->current_page
                ->filter(self::FILTER_NEXT_PAGE)
                ->link()
                ->getUri();
        } catch (InvalidArgumentException $e) {
            throw new ScraperException('Next page not found', ScraperException::CODE_MAP['NO_NEXT_PAGE'], $e);
        }
    }
}
