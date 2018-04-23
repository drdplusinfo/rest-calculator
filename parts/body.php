<?php
namespace DrdPlus\Calculators\Rest;

/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<fieldset class="panel">
  <div class="block">
    <h4 id="Vlastnosti">Vlastnosti</h4>
    <div class="panel">
      <div>
        <label>
          síla <span class="note">(ovlivňuje počet životů (odolnost))</span>
          <input type="number" value="<?= $controller->getSelectedStrength()->getValue() ?>" name="<?= $controller::STRENGTH ?>">
        </label>
      </div>
      <div>
        <label>
          vůle <span class="note">(ovlivňuje postihy za zranění a únavu)</span>
          <input type="number" value="<?= $controller->getSelectedWill()->getValue() ?>" name="<?= $controller::WILL ?>">
        </label>
      </div>
      <div>
        <label>
          rasa <span class="note">(ovlivňuje počet životů (odolnost), postihy (vůli) a regeneraci)</span>
          <select name="<?= $controller::RACE ?>">
              <?php foreach ($controller->getRaceCodes() as $raceCode) { ?>
                <option value="<?= $raceCode->getValue() ?>"
                        <?php if ($raceCode->getValue() === $controller->getSelectedRaceCode()->getValue()) { ?>selected<?php } ?>>
                    <?= $raceCode->translateTo('cs') ?>
                </option>
              <?php } ?>
          </select>
        </label>
      </div>
      <div>
        <label>
          podrasa <span class="note">(ovlivňuje počet životů (odolnost), postihy (vůli) a regeneraci)</span>
          <select name="<?= $controller::SUB_RACE ?>">
              <?php foreach ($controller->getSubRaceCodes() as $subRaceCode) {
                  $canNotPickSubRace = !$controller->canPickSubRace($subRaceCode);
                  ?>
                <option value="<?= $subRaceCode->getValue() ?>"
                        <?php if ($subRaceCode->getValue() === $controller->getSelectedSubRaceCode()->getValue()) { ?>selected="selected" <?php }
                        if ($canNotPickSubRace) { ?>disabled="disabled"<?php } ?>
                >
                    <?= ($canNotPickSubRace ? '- ' : '') . $subRaceCode->translateTo('cs') ?>
                </option>
              <?php } ?>
          </select>
        </label>
      </div>
      <div>
        <label>
          pohlaví <span class="note">(ovlivňuje postihy (vůli))</span>
          <select name="<?= $controller::GENDER ?>">
              <?php foreach ($controller->getGenderCodes() as $genderCode) { ?>
                <option value="<?= $genderCode->getValue() ?>"
                        <?php if ($genderCode->getValue() === $controller->getSelectedGenderCode()->getValue()) { ?>selected<?php } ?>>
                    <?= $genderCode->translateTo('cs') ?>
                </option>
              <?php } ?>
          </select>
        </label>
      </div>
      <input value="změnit" type="submit">
      <hr>
    </div>
    <div class="block">
      <span>celkem životů <?= $controller->getTotalHitPoints() ?>,</span>
      <span>zbývá <?= $controller->getRemainingHitPoints() ?></span>
    </div>
  </div>
</fieldset>