<?php
require_once __DIR__ . '/../vendor/autoload.php';

define('PIMCORE_TEST', true);

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::boostrap();
