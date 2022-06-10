<?php
/**
 * Imdb scraper that looks up a users publicly available reviews and scrapes them - combining them with metadata from TMDB.
 *
 * @copyright (C) 2022 Lukasz Andrzejak <spam@meltir.com>
 *
 * Licence:
 * This is an experiment/exercise in building a composer package, and setting it up.
 * You can look, but you cannot touch, run, analyse, lick or compile.
 * I don't care enough to chase down bots (or anyone for that matter), but for the record: BAD BOT.
 * This is scraping publicly available pages movie id's and review values, and does not use descriptions/posters/other metadata from IMDB
 * Please don't sue me.
 *
 * If you want to do this, get the official, commercial (IMDB api)[https://developer.imdb.com/].
 * Consider this an unwholy closed half-MIT licence.
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
 * This product uses the TMDB API but is not endorsed or certified by TMDB.
 * @see https://www.themoviedb.org/documentation/api/terms-of-use
 *
 * @license this is mine and nobody has my permission to use it or republish it in parts or whole anywhere ever.
 */

namespace Meltir\ImdbRatingsScraper;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Meltir\ImdbRatingsScraper\Exception\ImdbRatingsScraperException;
use Meltir\ImdbRatingsScraper\Interface\ImdbRatingsScraperInterface;
use Symfony\Component\DomCrawler\Crawler;
use Tmdb\Client;
use Tmdb\Token\Api\ApiToken;

class ImdbRatingsScraper implements ImdbRatingsScraperInterface
{
    protected ClientInterface $client;

    protected string $url;

    protected Client $tmdb;

    protected Crawler $current_page;

    protected const IMDB_RATINGS_URI_PREFIX = 'https://www.imdb.com/user/';

    protected const IMDB_RATINGS_URI_SUFFIX = '/ratings';

    protected const IMDB_BASE_URI = 'https://www.imdb.com';

    public function __construct(ClientInterface $client, int $user, Client $tmdb)
    {
        $this->client = $client;
        $this->tmdb = $tmdb;
        $user = 'ur'.$user;
        $this->url = self::IMDB_RATINGS_URI_PREFIX.$user.self::IMDB_RATINGS_URI_SUFFIX;
    }

    public function setUrl(string $url)
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
            $response = $this->client->request('GET', $this->url);
            return new Crawler($response->getBody()->getContents(), self::IMDB_BASE_URI);
        } catch (GuzzleException $e) {
            throw new ImdbRatingsScraperException("Scraper error when fetching page", $e->getCode(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new ImdbRatingsScraperException("Scraper error when parsing page", $e->getCode(), $e);
        }
    }

    /**
     * Process an individual entry
     *
     * @todo add cache - an optional repository/collection that can be checked against so the API's dont get hit as often
     * @todo OOOOOO!!!!!! i should do a doctrine&eloquent connector, maybe a symfony&laravel plugins !
     * @param Crawler $item
     * @return ImdbRatingItem
     * @throws \InvalidArgumentException
     */
    protected function processItem(Crawler $item): ImdbRatingItem
    {
        $movie = new ImdbRatingItem();
        $link = $item->filter('h3 > a')->link()->getUri();
        $imdb_id = preg_replace('@.*title/(.*)/.*@', '\\1', $link);
        $results = $this->tmdb->getFindApi()->findBy($imdb_id, ['external_source' => 'imdb_id']);
        $tmovie = $results['movie'][0];
        var_dump($results);
        die();
        $movie->rating = (int) $item->filter('div.ipl-rating-star.ipl-rating-star--other-user.small > span.ipl-rating-star__rating')->text();
        $movie->title = $tmovie['title'];
        $movie->image = $tmovie['poster_path'];
        $movie->year = $tmovie['release_date'];
        $movie->body = $tmovie['overview'];

//        $movie->title = $item->filter('h3 > a')->text();
//        $movie->image = $item->filter('img')->attr('loadlate');
//        $movie->year = $item->filter('h3 > span.lister-item-year.text-muted.unbold')->text();
//        $movie->body = $item->filter('p:nth-child(6)')->text();
        return $movie;
    }

    /**
     * Whats the standard way of breaking up a task like this ?
     * A queue !
     *
     * @return ImdbRatingItem[]
     * @throws ImdbRatingsScraperException
     */
    public function getAllMovies(): array
    {
        $movies = $this->getMovies();
        $url = 'start';
        while ($url = $this->getNextPage()) {
            $this->setUrl($url);
            $movies = array_merge($movies, $this->getMovies());
        }
        return $movies;
    }

    /**
     * Process the whole ratings page
     *
     * @return ImdbRatingItem[]
     * @throws ImdbRatingsScraperException
     */
    public function getMovies(): array
    {
        $this->current_page = $this->getUrl();
        $item = $this->current_page->filter('#ratings-container > div.lister-item');
        $movies = [];
        $last_item = false;
        while (!$last_item) {
            try {
                $movies[] = $this->processItem($item);
                $item = $item->nextAll();
            } catch (\InvalidArgumentException $e) {
                $last_item = true;
            }
        }
        return $movies;
    }

    /**
     * Get the url of the next page, or empty string if not found
     * @return string
     */
    public function getNextPage(): string
    {
        try {
            return $this->
                current_page->
                filter('#ratings-container > div.footer.filmosearch > div > div > a.flat-button.lister-page-next.next-page')->
                link()->
                getUri();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}