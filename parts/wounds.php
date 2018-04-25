<?php
namespace DrdPlus\Calculators\Rest;

/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="panel">
  <fieldset class="block">
    <div class="panel">
      <h3>Zranění</h3>
      <div class="block">
        <span>zranění <strong><?= $controller->getSumOfWounds() ?></strong>,</span>
          <?php if ($controller->isDead()) { ?>
        <span class="">postava je <strong>mrtvá ☠️</strong>
            <?php if (!$controller->isDeadBecauseOfWounds()) { ?>
              <span class="note">(kvůli vyčerpání)</span>
            <?php }
            } elseif (!$controller->isConscious()) { ?>
              <span class="">postava je <strong>v bezvědomí 😴</strong></span>
            <?php } else { ?>
                <?php if ($controller->mayHaveMalusFromWounds() > 0) { ?>
                <label>
                hod na vůli 2k6<span class="upper-index">+</span>
                <input type="number" name="<?= $controller::ROLL_AGAINST_MALUS_FROM_WOUNDS ?>" value="<?= $controller->getSelectedRollAgainstMalusFromWounds()->getValue() ?>">
              </label>
                <button type="submit" name="<?= $controller::SHOULD_ROLL_AGAINST_MALUS_FROM_WOUNDS ?>" value="1" class="manual">
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
      </div>
      <div class="block">
        <label>
          velikost
          <input class="manual" type="number" min="1" value="" name="<?= $controller::WOUND_SIZE ?>[]">
        </label>
        <label>
          typ
          <select name="<?= $controller::SERIOUS_WOUND_ORIGIN ?>[]" class="manual">
              <?php foreach ($controller->getPossibleSeriousWoundOrigins() as $seriousWoundOrigin) { ?>
                <option value="<?= $seriousWoundOrigin->getValue() ?>"><?= $seriousWoundOrigin->translateTo('cs') ?></option>
              <?php } ?>
          </select>
        </label>
        <input type="submit" value="zranit">
      </div>
      <div class="block">
        <hr>
        <div class="panel">
          <h4>Těžká zranění</h4>
            <?php if (!$controller->getSeletedSeriousWounds()) { ?>
              <span class="note">žádné</span>
            <?php } else { ?>
              <ul>
                  <?php foreach ($controller->getSeletedSeriousWounds() as $seriousWound) { ?>
                    <li>
                      velikost <?= $seriousWound->getValue() ?>,
                        <?= $seriousWound->isOld() ? 'staré*' : 'čerstvé**' ?>,
                        <?= $seriousWound->getWoundOriginCode()->translateTo('cs') ?>
                      <input type="hidden" name="<?= $controller::WOUND_SIZE ?>[]" value="<?= $seriousWound->getValue() ?>">
                      <input type="hidden" name="<?= $controller::SERIOUS_WOUND_ORIGIN ?>[]" value="<?= $seriousWound->getWoundOriginCode()->getValue() ?>">
                    </li>
                  <?php } ?>
              </ul>
            <?php } ?>
        </div>
        <div class="panel">
          <h4>Lehká zranění</h4>
          <div>celkem <?= $controller->getSumOfOrdinaryWounds() ?></div>
            <?php foreach ($controller->getSelectedOrdinaryWounds() as $ordinaryWound) { ?>
              <input type="hidden" name="<?= $controller::WOUND_SIZE ?>[]" value="<?= $ordinaryWound->getValue() ?>">
            <?php } ?>
        </div>
          <?php if ($controller->hasFreshWounds()) { ?>
            <div class="block">
              <hr>
              <div class="note">
                <div>* čerstvé zranění lze léčit na bitevním poli</div>
                <div>** staré či už ošetřené zranění musí tělo zvládnout regenerací nebo s pomocí specialisty</div>
              </div>
            </div>
          <?php } ?>
      </div>
    </div>
  </fieldset>
</div>