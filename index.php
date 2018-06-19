<?php
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$vendorRoot = $vendorRoot ?? $documentRoot . '/vendor';
/** @noinspection PhpIncludeInspection */
require_once $vendorRoot . '/autoload.php';

$controller = new \DrdPlus\RestCalculator\RestController(
    'https://github.com/jaroslavtyc/drdplus-calculator-skeleton',
    $documentRoot,
    $vendorRoot
);

require __DIR__ . '/vendor/drd-plus/calculator-skeleton/index.php';