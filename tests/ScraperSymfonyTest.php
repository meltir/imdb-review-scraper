<?php
/**
 * These tests cover an example implementation with the symfony psr 18 implementation
 * To run these tests:
 *
 * docker-compose up -d
 * docker-compose exec php ./vendor/bin/phpunit
 */

namespace Meltir\ImdbRatingsScraper\Tests;

use Meltir\ImdbRatingsScraper\Exception\ScraperException;
use Meltir\ImdbRatingsScraper\Item;
use Meltir\ImdbRatingsScraper\Scraper;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Psr18RequestException;
use Symfony\Component\HttpClient\Response\MockResponse;

class ScraperSymfonyTest extends TestCase
{
    protected Psr18Client $client;

    protected MockHttpClient $mockClient;

    public function setUp(): void
    {
        $responses = [new MockResponse(
            (string) file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response.html'),
            ['http_code' => 200]
        )];
        $this->mockClient = new MockHttpClient($responses);
        $this->client = new Psr18Client($this->mockClient);
        parent::setUp();
    }

    private function getRequestFactory(): RequestFactoryInterface|ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    private function getClient(array $responses = null): ClientInterface
    {
        if (is_null($responses)) {
            $responseBody = (string) file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response.html');
            $responses = [
                new MockResponse($responseBody, ['http_code' => 200]),
            ];
        } else {
            $responses = array_map(function ($responseContent) {
                return new MockResponse($responseContent, ['http_code' => 200]);
            }, $responses);
        }
        $this->mockClient = new MockHttpClient($responses);

        return new Psr18Client($this->mockClient, $this->getRequestFactory());
    }

    public function testGetNextPage()
    {
        $scraper = new Scraper($this->client, $this->getRequestFactory(), 'foobar');
        $scraper->getMovies();
        $this->assertSame('https://www.imdb.com/NEXTPAGE', $scraper->getNextPage());
    }

    public function testGetAllMovies()
    {
        $r1 = file_get_contents(
            filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response.html'
        );
        $r2 = file_get_contents(
            filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response-last.html'
        );
        $user = 'foobar';
        $movie1 = new Item('tt2435850', 7, $user);
        $movie2 = new Item('tt0251282', 8, $user);
        $movie3 = new Item('tt2435850', 7, $user);
        $movie4 = new Item('tt0251282', 8, $user);
        $client = $this->getClient([$r1, $r2]);
        $scraper = new Scraper($client, $this->getRequestFactory(), $user);
        $this->assertEquals([$movie1, $movie2, $movie3, $movie4], $scraper->getAllMovies());
    }

    public function testGetMovies()
    {
        $user = 'foobar';
        $scraper = new Scraper($this->client, $this->getRequestFactory(), $user);
        $movie1 = new Item('tt2435850', 7, $user);
        $movie2 = new Item('tt0251282', 8, $user);
        $this->assertEquals([$movie1, $movie2], $scraper->getMovies());
    }

    public function testCrawlerException()
    {
        $request = file_get_contents(
            filename: __DIR__.DIRECTORY_SEPARATOR.'samples'.DIRECTORY_SEPARATOR.'imdb-response-broken.html'
        );
        $client = $this->getClient([$request]);
        $scraper = new Scraper($client, $this->getRequestFactory(), 'foobar');
        $this->expectException(ScraperException::class);
        $this->expectExceptionCode(ScraperException::CODE_MAP['MOVIE_FAILED']);
        $this->expectExceptionMessage('Could not scrape this movie');
        $scraper->getMovies();
    }

    public function testGuzzleException()
    {
        $client = \Mockery::mock(ClientInterface::class);
        $client
            ->expects('sendRequest')
            ->andThrow(
                new Psr18RequestException(
                    new TransportException('Boom no connect!'),
                    $this->getRequestFactory()->createRequest('GET', '')
                )
            );
        $this->expectException(ScraperException::class);
        $this->expectExceptionCode(ScraperException::CODE_MAP['COULD_NOT_CONNECT']);
        $this->expectExceptionMessage('Could not connect to imdb');
        $scraper = new Scraper($client, $this->getRequestFactory(), 'foobar');
        $scraper->getMovies();
    }
}
