<?php
namespace DrdPlus\Calculators\Rest;

include_once __DIR__ . '/vendor/autoload.php';

error_reporting(-1);
ini_set('display_errors', '1');

/** @noinspection PhpUnusedLocalVariableInspection */
$controller = new Controller();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/generic/graphics.css" rel="stylesheet" type="text/css">
    <link href="css/generic/skeleton.css" rel="stylesheet" type="text/css">
    <link href="css/generic/issues.css" rel="stylesheet" type="text/css">
    <link href="css/rest.css" rel="stylesheet" type="text/css">
    <noscript>
      <link href="css/generic/no_script.css" rel="stylesheet" type="text/css">
    </noscript>
  </head>
  <body>
    <div class="background"></div>
      <?php include __DIR__ . '/vendor/drd-plus/calculator-skeleton/history_deletion.php' ?>
    <form method="get" action="" class="block" id="configurator">
        <?php include __DIR__ . '/vendor/drd-plus/calculator-skeleton/history_remember.php' ?>
      <div class="block"><?php include __DIR__ . '/parts/body.php'; ?></div>
      <div class="block"><?php include __DIR__ . '/parts/wounds.php'; ?></div>
      <div class="block"><?php include __DIR__ . '/parts/treatment.php'; ?></div>
      <div class="block"><?php include __DIR__ . '/parts/fatigue.php'; ?></div>
    </form>
    <form method="post" action="" class="block" id="configuratorRest">
      <div class="block"><?php include __DIR__ . '/parts/rest.php'; ?></div>
    </form>
      <?php
      /** @noinspection PhpUnusedLocalVariableInspection */
      $sourceCodeUrl = 'https://github.com/jaroslavtyc/drdplus-calculator-skeleton';
      include __DIR__ . '/vendor/drd-plus/calculator-skeleton/issues.php' ?>
    <script type="text/javascript" src="js/rest.js"></script>
    <script type="text/javascript" src="js/generic/skeleton.js"></script>
  </body>
</html>
