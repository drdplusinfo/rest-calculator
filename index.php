<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$vendorRoot = $vendorRoot ?? $documentRoot . '/vendor';

require_once __DIR__ . '/vendor/autoload.php';

$controller = new \DrdPlus\RestCalculator\RestController(
    'https://github.com/jaroslavtyc/drdplus-calculator-skeleton',
    $documentRoot,
    $vendorRoot
);

require __DIR__ . '/vendor/drd-plus/calculator-skeleton/index.php';