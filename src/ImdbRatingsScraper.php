<?php
/**
 * Imdb scraper that looks up a users publicly available reviews and scrapes them - combining them with metadata from TMDB.
 *
 *
 * Licence:
 * This is an experiment/exercise in building a composer package, and setting it up.
 * You can look, but you cannot touch, run, analyse, lick or compile.
 * I don't care enough to chase down bots (or anyone for that matter), but for the record: BAD BOT.
 * This is scraping publicly available page's movie id's and review values, and does not use descriptions/posters/other metadata from IMDB
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

use InvalidArgumentException;
use Meltir\ImdbRatingsScraper\Exception\ImdbRatingsScraperException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Meltir\ImdbRatingsScraper\Interface\ImdbRatingItemInterface;
use Meltir\ImdbRatingsScraper\Interface\ImdbRatingsScraperInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * IMDB user review scraper
 * Only uses publicly available information from non-protected user pages
 */
class ImdbRatingsScraper implements ImdbRatingsScraperInterface
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
     * Where the user profile lives on imdb
     */
    protected const IMDB_RATINGS_URI_PREFIX = 'https://www.imdb.com/user/';

    /**
     * Where the user ratings live in a users profile
     */
    protected const IMDB_RATINGS_URI_SUFFIX = '/ratings';

    /**
     * Where the base url is for parsing urls
     */
    protected const IMDB_BASE_URI = 'https://www.imdb.com';

    /**
     * Css selector to find the next page link
     */
    protected const FILTER_NEXT_PAGE = '#ratings-container > div.footer.filmosearch > div > div > a.flat-button.lister-page-next.next-page';

    /**
     * Css selector to find an individual movie rating
     */
    protected const FILTER_RATING_ITEM = 'div.ipl-rating-star.ipl-rating-star--other-user.small > span.ipl-rating-star__rating';

    /**
     * Css selector to find the ratings list
     */
    protected const FILTER_RATING_CONTAINER = '#ratings-container > div.lister-item';


    /**
     * @param ClientInterface $client http client to use (guzzle)
     * @param string $user imdb user id
     */
    public function __construct(protected ClientInterface $client, protected string $user)
    {
        $this->url = self::IMDB_RATINGS_URI_PREFIX . $user . self::IMDB_RATINGS_URI_SUFFIX;
    }

    /**
     * Set the url of the review page to be scanned.
     * This is setup by default as the first page of a users reviews in the constructor
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return Crawler
     * @throws ImdbRatingsScraperException
     */
    protected function getUrl(): Crawler
    {
        try {
            return new Crawler(
                $this
                    ->client
                    ->request('GET', $this->url)
                    ->getBody()
                    ->getContents(),
                self::IMDB_BASE_URI);
        } catch (GuzzleException $e) {
            throw new ImdbRatingsScraperException(
                "Could not connect to imdb",
                ImdbRatingsScraperException::CODE_MAP['COULD_NOT_CONNECT'],
                $e
            );
        } catch (InvalidArgumentException $e) {
            throw new ImdbRatingsScraperException(
                "Could not scrape page",
                ImdbRatingsScraperException::CODE_MAP['COULD_NOT_SCRAPE'],
                $e
            );
        }
    }

    /**
     * Process an individual entry
     *
     * @param Crawler $item a movie and its rating
     * @return ImdbRatingItem
     * @throws ImdbRatingsScraperException
     */
    protected function processItem(Crawler $item): ImdbRatingItemInterface
    {
        try {
            $link = $item->filter('h3 > a')->link()->getUri();
            try {
                $movie = new ImdbRatingItem();
                $movie->imdb_id = preg_replace('@.*title/(.*)/.*@', '\\1', $link);
                $movie->reviewer = $this->user;
                $movie->rating = (int) $item->filter(self::FILTER_RATING_ITEM)->text();
            } catch (InvalidArgumentException $e) {
                throw new ImdbRatingsScraperException(
                    "Could not scrape this movie",
                    ImdbRatingsScraperException::CODE_MAP['MOVIE_FAILED'],
                    $e
                );
            }

        } catch (InvalidArgumentException $e) {
            throw new ImdbRatingsScraperException(
                "No more movies on this list",
                ImdbRatingsScraperException::CODE_MAP['END_OF_PAGE'],
                $e
            );
        }

        return $movie;
    }

    /**
     * Get all movies from all pages. This can timeout !
     *
     * @todo implement this as a queue
     * @return ImdbRatingItem[]
     * @throws ImdbRatingsScraperException
     */
    public function getAllMovies(): array
    {
        $movies = $this->getMovies();
        try {
            while ($url = $this->getNextPage()) {
                $this->setUrl($url);
                $movies = array_merge($movies, $this->getMovies());
            }
        } catch (ImdbRatingsScraperException $e) {
            if ($e->getCode() === ImdbRatingsScraperException::CODE_MAP['NO_NEXT_PAGE']) return $movies;
            throw $e;
        }
        return $movies;
    }

    /**
     * Process a single page of reviews
     *
     * @return ImdbRatingItem[]
     * @throws ImdbRatingsScraperException
     */
    public function getMovies(): array
    {
        $this->current_page = $this->getUrl();
        try {
            $item = $this->current_page->filter(self::FILTER_RATING_CONTAINER);
        } catch (InvalidArgumentException $e) {
            throw new ImdbRatingsScraperException(
                'Could not find ratings on this page',
                ImdbRatingsScraperException::CODE_MAP['NO_RATINGS'],
                $e
            );
        }

        $movies = [];
        $last_item = false;
        while (!$last_item) {
            try {
                $movies[] = $this->processItem($item);
                $item = $item->nextAll();
            } catch (ImdbRatingsScraperException $e) {
                if ($e->getCode() === ImdbRatingsScraperException::CODE_MAP['END_OF_PAGE']) $last_item = true;
                else throw $e;
            }
        }
        return $movies;
    }

    /**
     * Get the url of the next page, or exception if not found
     *
     * @return string
     * @throws ImdbRatingsScraperException
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
            throw new ImdbRatingsScraperException('Next page not found', ImdbRatingsScraperException::CODE_MAP['NO_NEXT_PAGE'], $e);
        }
    }
}