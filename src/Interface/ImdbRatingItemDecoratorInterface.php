<?php

namespace Meltir\ImdbRatingsScraper\Interface;

interface ImdbRatingItemDecoratorInterface
{
    public function format(ImdbRatingItemInterface $movie): ImdbRatingItemInterface;
}