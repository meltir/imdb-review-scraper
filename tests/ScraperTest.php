<?php

namespace Meltir\ImdbRatingsScraper\test;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Meltir\ImdbRatingsScraper\Item;
use Meltir\ImdbRatingsScraper\Scraper;
use Mockery;
use PHPUnit\Framework\TestCase;


/**
 * @todo add failures and exceptions
 * @todo refactor and clean up
 * @todo add infection coverage
 * @todo check other assertions related to request
 * @todo do i need mocker here ? im not using it atm, maybe after i add the exceptions checks
 * @todo add phpunit.xml
 *
 * run tests and container with
 * docker run -ti -v `pwd`:/var/www/html --entrypoint /bin/sh scrapercontainer
 */


class ScraperTest extends TestCase
{

    protected ClientInterface $client;

    protected MockHandler $mock;

    /**
     * @todo figure out how to do this with attributes (array shapes)
     * Well, the below doesn't work with phpstorm
     * @var array{request: Request, response: Response, error: array, options: array}[]
     */
//    #[ArrayShape(ARRAY_SHAPE_GUZZLE_HISTORY)]
    protected array $container;

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        /**
         * @todo find a nicer way to load the file
         */
        $response = new Response(200, [], file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imdb-response.html'));
        $this->container = [];
        $history = Middleware::history($this->container);
        $mock = new MockHandler([$response]);
        $handlerStack = HandlerStack::create($mock);
        $this->mock = $mock;
        $handlerStack->push($history);
        return new Client([
            'handler' => $handlerStack,
            'debug' => true
        ]);
    }

    public function setUp(): void
    {

        parent::setUp();
    }

    public function testGetNextPage()
    {
        $this->client = $this->getClient();
        $scraper = new Scraper($this->client, 'foobar');
        $scraper->getMovies();
        $this->assertEquals('https://www.imdb.com/NEXTPAGE', $scraper->getNextPage());
    }

    public function testGetAllMovies()
    {

        $this->testGetMovies();
    }



    public function testSetUrl()
    {
        $this->client = $this->getClient();
        $user = 'foobar';
        $scraper = new Scraper($this->client, $user);
        $scraper->setUrl('foobar');
        $scraper->getMovies();
        /** @var Request $request */
        $request = $this->container[0]['request'];
        $this->assertEquals('foobar', $request->getUri());
    }

    public function testGetMovies()
    {
        $this->client = $this->getClient();
        $user = 'foobar';
        $scraper = new Scraper($this->client, $user);
        $movie1 = new Item();
        $movie1->imdb_id = 'tt2435850';
        $movie1->reviewer = $user;
        $movie1->rating = 7;
        $movie2 = new Item();
        $movie2->imdb_id = 'tt0251282';
        $movie2->reviewer = $user;
        $movie2->rating = 8;
        $this->assertEquals([$movie1, $movie2], $scraper->getMovies());
    }

}
