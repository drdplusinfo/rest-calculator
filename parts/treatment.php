<?php
namespace DrdPlus\Calculators\Rest;

/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="panel">
  <fieldset class="block">
    <div class="panel">
      <h3>Léčba</h3>
      <div class="block">
        <a href="http://pph.drdplus.loc/#zmirneni_zraneni" class="note">léčení v PPH</a>
        <div>vyléčená běžná zranění <?= $controller->getHealedAmountOfOrdinaryWounds() ?></div>
        <div>vyléčeno z těžkého zranění <?= $controller->getHealedAmountOfSeriousWound() ?></div>
      </div>
    </div>
  </fieldset>
</div>