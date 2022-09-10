<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->exclude('tests/samples')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@Symfony' => true,
    '@PHP81Migration' => true,
])->setFinder($finder);