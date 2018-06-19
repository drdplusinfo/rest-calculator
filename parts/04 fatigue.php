<?php
/** @var \DrdPlus\Calculators\Rest\RestController $controller */
?>
<fieldset class="panel">
  <h3>Únava</h3>
  <div class="block">
    <span class="">bodů únavy <strong><?= $controller->getTotalFatigue() ?></strong></span>,
      <?php if ($controller->isDead()) { ?>
    <span class="">postava je <strong>mrtvá ☠️</strong>
        <?php if (!$controller->isDeadBecauseOfFatigue() /* may be dead "twice", both from fatigue as well as wounds */) { ?>
          <span class="note">(kvůli zranění)</span>
        <?php }
        } elseif (!$controller->isConscious()) { ?>
          <span class="">postava je <strong>v bezvědomí 😴</strong></span>
        <?php } else { ?>
            <?php if ($controller->mayHaveMalusFromWounds() > 0) { ?>
            <label>
            hod na vůli 2k6<span class="upper-index">+</span>
            <input type="number" name="<?= $controller::ROLL_AGAINST_MALUS_FROM_WOUNDS ?>" value="<?= $controller->getSelectedRollAgainstMalusFromWounds()->getValue() ?>">
          </label>
            <button type="submit" name="<?= $controller::USER_ROLL_AGAINST_MALUS_FROM_WOUNDS ?>" value="1" class="manual">
            Hodit 2k6<span class="upper-index">+</span>
          </button>
            <span class="note">+ <?= $controller->getFinalWill()->getValue() ?>
              = <?= $controller->getTotalRollAgainstMalusFromWounds() ?></span>
            <span class="note"><a href="https://pph.drdplus.info/#postih_za_zraneni">(5/10/15)</a> = </span>
            <?php }
            if ($controller->getMalusFromWounds() < 0) { ?>
              <span>postih za zranění <strong><?= $controller->getMalusFromWounds() ?> 🤕</strong></span>
            <?php } else { ?>
              <strong>bez postihu 🙂</strong>
            <?php } ?>
        <?php } ?>
      <a href="https://pph.drdplus.info/#stupne_unavy" class="note">únava v PPH</a>
  </div>
  <div class="block">
    <hr>
    <label>velikost
      <input type="number" class="manual" min="1" name="<?= $controller::FATIGUE ?>[]"></label>
      <?php foreach ($controller->getSelectedFatigues() as $fatigue) { ?>
        <input type="hidden" name="<?= $controller::FATIGUE ?>[]" value="<?= $fatigue->getValue() ?>">
      <?php } ?>
    <input value="unavit" type="submit">
  </div>
</fieldset>