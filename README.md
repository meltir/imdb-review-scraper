# Christmas tree

 | Checks                                                                     | Badge                                                                                                                                                                                             | 
 |----------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------| 
 | Composer validation                                                        | [![Composer checks](https://github.com/meltir/imdb-review-scraper/actions/workflows/php.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/php.yml)                  | 
 | PHP Cs Fixer ([@Symfony, @PHP82Migration](.php-cs-fixer.dist.php#L11))     | [![PHP cs fixer check](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpcsfixer.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpcsfixer.yml) |
 | Infection Mutation tests ([Min MSI>95%, Min C MSI>95%](composer.json#L60)) | [![Infection tests](https://github.com/meltir/imdb-review-scraper/actions/workflows/infection.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/infection.yml)      |
 | PHPinsights ([Q>=95,C>=70,A>=80,S>=80](composer.json#L52))                 | [![PHPinsights](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpinsights.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpinsights.yml)      |
 | PHPStan ([L9](phpstan.neon#L2))                                            | [![PHPStan](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpstan.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpstan.yml)                  |
 | PHPUnit                                                                    |  [![PHPUnit](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpunit.yml/badge.svg)](https://github.com/meltir/imdb-review-scraper/actions/workflows/phpunit.yml) |

# What is this thing ?
Hi, I'm Lukasz. I code. This is a thing I coded.  
This is an experiment/exercise in building a composer package, and setting it up with a full deployment lifecycle.  

It is a scraper that lookups up an IMDB users reviews, scrapes them, transforms them and spits them out as objects.  

You should be using [the official IMDB api](https://developer.imdb.com/).

## Usage

Quick and dirty:

```php
<?php

require 'vendor/autoload.php';

$movies = new Meltir\ImdbRatingsScraper\Scraper(new \GuzzleHttp\Client(), 'ur20552756');
var_dump($movies->getMovies());
```

## Licence

### Short version:
This is mine and nobody has my permission to use it or republish it in parts or whole anywhere ever.    

### Long and snarky version:  
You can look, but you cannot touch, run, analyse, lick or compile.  
I don't care enough to chase down bots (or anyone else for that matter), but for the record:   
  
*BAD BOT*  

This is scraping publicly available page's movie id's and review values, and does not use descriptions/posters/other metadata from IMDB  
_Please_ don't sue me.  
  
If you want to do this or anything like it for any purpose, get the official, commercial [IMDB api](https://developer.imdb.com/ "also, expensive for just messing around").    
Consider this an unholy closed half-MIT licence.      
  
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.    

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
