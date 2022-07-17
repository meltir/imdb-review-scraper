# What is this thing ?
Hi, I'm Lukasz. I code. This is a thing I coded.  
This is an experiment/exercise in building a composer package, and setting it up with a full deployment lifecycle.  

It is a scraper that lookups up an IMDB users reviews, scrapes them, transforms them and spits them out as objects.  

Am i still php signed ?


## Usage

Quick and dirty:

```php
<?php

require 'vendor/autoload.php';

use Meltir\ImdbRatingsScraper\Scraper;
$movies = new Scraper(new \GuzzleHttp\Client(), 'ur20552756');
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
