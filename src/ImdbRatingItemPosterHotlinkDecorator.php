<?php

namespace Meltir\ImdbRatingsScraper;


use Meltir\ImdbRatingsScraper\Interface\ImdbRatingItemDecoratorInterface;
use Meltir\ImdbRatingsScraper\Interface\ImdbRatingItemInterface;

class ImdbRatingItemPosterHotlinkDecorator implements ImdbRatingItemDecoratorInterface
{

    public function format(ImdbRatingItemInterface $movie): ImdbRatingItemInterface
    {
        $image = $movie->image;
        // resize image from default thumbnail by changing the url
        $url1 = preg_replace('/UY209_CR[0-9]+,0,140,209/','UY896_CR0,0,600,896',$image);
        $url2 = preg_replace('/UX140_CR[0-9]+,0,140,209/','UX600_CR0,0,600,896',$image);
        if ($image != $url2) {
            $movie->image = $url2;
        } else if ($image != $url1) {
            $movie->image = $url1;
        }
        // get first item that looks like a year from the string
        preg_match('/[0-9]{4}/', $movie->year, $year_arr);
        $movie->year = $year_arr[0].'-01-01';
        $body = $movie->body;
        $body = trim($body);
        $body = str_replace("\r\n",'<br />',$body);
        $body = preg_replace('/ {2,}/', '', $body);
        $movie->body = $body;

        return $movie;
    }
}