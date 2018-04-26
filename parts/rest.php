<?php
/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<fieldset class="panel">
  <button type="submit" name="<?= $controller::RESTING ?>" value="1" class="manual" <?php if ($controller->isDead()) { ?>disabled="disabled"<?php } ?>>
    Odpočívat
  </button>
  <span class="note">z neodhojených čerstvých zranění budou stará</span>
    <?php if ($controller->isDead()) { ?>
      <span class="note">postava je <strong>mrtvá ☠️</strong></span>
    <?php } ?>
  <hr>
  <div>
    odbouraná únava <?= $controller->getRestedAmountOfFatigue() ?>,
    <a href="https://pph.drdplus.info/#odpocinek" class="note">odpočinek v PPH</a></div>
  <div>
    zahojená zranění <?= $controller->getRegeneratedAmountOfWounds() ?>
    <a href="https://pph.drdplus.info/#hojeni_zraneni" class="note">hojení v PPH</a>
  </div>
</fieldset>