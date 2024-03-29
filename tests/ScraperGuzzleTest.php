<?php
/**
 * These tests cover an example implementation with the symfony psr 18 implementation
 * To run these tests:
 *
 * docker-compose up -d
 * docker-compose exec php ./vendor/bin/phpunit
 */

namespace Meltir\ImdbRatingsScraper\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Meltir\ImdbRatingsScraper\Exception\ScraperException;
use Meltir\ImdbRatingsScraper\Item;
use Meltir\ImdbRatingsScraper\Scraper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;

class ScraperGuzzleTest extends TestCase
{
    protected ClientInterface $client;

    protected MockHandler $mock;

    /**
     * @var array{array{request: Request, response: Response, error: array, options: array}}
     */
    protected array $container;

    public function setUp(): void
    {
        $this->client = $this->getClient();
        parent::setUp();
    }

    /**
     * Generate a single response to an imdb query, always the same one.
     */
    private function getSingleResponse(): Response
    {
        return new Response(
            status: 200,
            headers: [],
            body: file_get_contents(
                filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response.html'
            )
        );
    }

    private function getRequestFactory(): RequestFactoryInterface
    {
        return new HttpFactory();
    }

    private function getClient(?array $responses = null): Client
    {
        if (is_null($responses)) {
            $responses = [$this->getSingleResponse()];
        }
        $this->container = []; // @phpstan-ignore-line
        $history = Middleware::history($this->container);
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $this->mock = $mock;
        $handlerStack->push($history);

        return new Client([
            'handler' => $handlerStack,
            'debug' => true,
        ]);
    }

    public function testGetNextPage()
    {
        $scraper = new Scraper($this->client, $this->getRequestFactory(), 'foobar');
        $scraper->getMovies();
        $this->assertSame('https://www.imdb.com/NEXTPAGE', $scraper->getNextPage());
    }

    public function testGetAllMovies()
    {
        $r1 = new Response(
            status: 200,
            headers: [],
            body: file_get_contents(
                filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response.html'
            )
        );
        $r2 = new Response(
            status: 200,
            headers: [],
            body: file_get_contents(
                filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response-last.html'
            )
        );
        $user = 'foobar';
        $movie1 = new Item('tt2435850', 7, $user);
        $movie2 = new Item('tt0251282', 8, $user);
        $movie3 = new Item('tt2435850', 7, $user);
        $movie4 = new Item('tt0251282', 8, $user);
        $client = $this->getClient([$r1, $r2]);
        $scraper = new Scraper($client, $this->getRequestFactory(), $user);
        $this->assertEquals([$movie1, $movie2, $movie3, $movie4], $scraper->getAllMovies());
        $this->assertSame(
            'https://www.imdb.com/user/foobar/ratings',
            (string) $this->container[0]['request']->getUri()
        );
        $this->assertSame('https://www.imdb.com/NEXTPAGE', (string) $this->container[1]['request']->getUri());
    }

    public function testSetUrl()
    {
        $user = 'foobar';
        $scraper = new Scraper($this->client, $this->getRequestFactory(), $user);
        $scraper->setUrl('foobar');
        $scraper->getMovies();
        $this->assertSame('foobar', (string) $this->container[0]['request']->getUri());
    }

    public function testGetMovies()
    {
        $user = 'foobar';
        $scraper = new Scraper($this->client, $this->getRequestFactory(), $user);
        $movie1 = new Item('tt2435850', 7, $user);
        $movie2 = new Item('tt0251282', 8, $user);
        $this->assertEquals([$movie1, $movie2], $scraper->getMovies());
    }

    public function testConstructor()
    {
        $scraper = new Scraper($this->client, $this->getRequestFactory(), 'foobar');
        $scraper->getMovies();
        $this->assertSame(
            'https://www.imdb.com/user/foobar/ratings',
            (string) $this->container[0]['request']->getUri()
        );
    }

    public function testCrawlerException()
    {
        $response = new Response(
            status: 200,
            headers: [],
            body: file_get_contents(
                filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response-broken.html'
            )
        );
        $client = $this->getClient([$response]);
        $scraper = new Scraper($client, $this->getRequestFactory(), 'foobar');
        $this->expectException(ScraperException::class);
        $this->expectExceptionCode(ScraperException::CODE_MAP['MOVIE_FAILED']);
        $this->expectExceptionMessage('Could not scrape this movie');
        $scraper->getMovies();
    }

    public function testGuzzleException()
    {
        $client = \Mockery::mock(Client::class);
        $client
            ->expects('sendRequest')
            ->andThrow(
                new RequestException(
                    'Boom no connect !',
                    new Request('GET', 'test')
                )
            );
        $this->expectException(ScraperException::class);
        $this->expectExceptionCode(ScraperException::CODE_MAP['COULD_NOT_CONNECT']);
        $this->expectExceptionMessage('Could not connect to imdb');
        $scraper = new Scraper($client, $this->getRequestFactory(), 'foobar');
        $scraper->getMovies();
    }
}
