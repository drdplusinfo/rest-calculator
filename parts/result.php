<?php
/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="block">
    <h2 id="zraneni"><a href="#zraneni">Zranění</a></h2>
    <strong id="result"><?= $controller->getWoundsByFall() ?? '?'; ?></strong>
</div>