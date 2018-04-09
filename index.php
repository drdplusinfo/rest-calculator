<?php
namespace DrdPlus\Calculators\Rest;

include_once __DIR__ . '/vendor/autoload.php';

error_reporting(-1);
ini_set('display_errors', '1');

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
<div id="fb-root"></div>
<div class="background"></div>
<form class="block delete" action="/" method="post" onsubmit="return window.confirm('Opravdu smazat včetně historie?')">
    <label>
        <input type="submit" value="Smazat" name="<?= $controller::DELETE_HISTORY ?>" class="manual">
        <span class="hint">(včetně dlouhodobé paměti)</span>
    </label>
</form>
<form class="block" action="" method="get" id="configurator">
    <div class="block remember">
        <label><input type="checkbox" name="<?= $controller::REMEMBER_CURRENT ?>" value="1"
                      <?php if ($controller->shouldRemember()) { ?>checked="checked"<?php } ?>>
            Pamatovat <span class="hint">(i při zavření prohlížeče)</span></label>
    </div>
    <div class="block">
        <div class="panel"><?php include __DIR__ . '/parts/falling.php'; ?></div>
        <div class="panel"><?php include __DIR__ . '/parts/reaction.php'; ?></div>
        <div class="panel"><?php include __DIR__ . '/parts/armor.php'; ?></div>
    </div>
    <div class="block"><?php include __DIR__ . '/parts/result.php'; ?></div>
</form>
<div class="block issues">
    <a href="https://rpgforum.cz/forum/viewtopic.php?f=238&t=14870">
        <img src="images/generic/rpgforum-ico.png">
        Máš nápad 😀? Vidíš chybu 😱?️ Sem s tím!
    </a>
    <a class="float-right" href="https://github.com/jaroslavtyc/drd-plus-fall/"
       title="Fork me on GitHub"><img class="github" src="/images/generic/GitHub-Mark-64px.png"></a>
</div>
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript" src="js/generic/main.js"></script>
</body>
</html>
