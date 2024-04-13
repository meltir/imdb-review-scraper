<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->exclude('tests/samples')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    '@PHP82Migration' => true,
    '@PHP83Migration' => true,
    'single_line_throw' => false, // complex exceptions with labeled params push lines too far
])->setFinder($finder);
