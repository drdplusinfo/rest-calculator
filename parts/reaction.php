<?php
/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="block">
    <h2 id="akce_a_reakce"><a href="#akce_a_reakce" class="inner">Akce a reakce</a></h2>
    <div class="block">
        <div class="panel">
            <label>
                skočils <span class="hint">(pád tě nepřekvapil => výška -2 metry)</span>
                <input type="checkbox" name="<?= $controller::JUMP_IS_CONTROLLED ?>"
                       <?php if ($controller->isJumpControlled()) { ?>checked="checked" <?php } ?>>
            </label>
        </div>
    </div>
    <div class="block">
        <div class="panel">
            <label>neovládáš tělo <span class="hint">(výsledná obratnost -6)</span>
                <input id="withoutReaction" type="checkbox" name="<?= $controller::WITHOUT_REACTION ?>" value="1"
                       <?php if ($controller->isWithoutReaction()) { ?>checked="checked"<?php } ?>>
            </label>
        </div>
    </div>
    <div class="block">
        <div class="panel">
            <label>obratnost
                <input id="agility" type="number" class="single-number" name="<?= $controller::AGILITY ?>" min="-40" max="40"
                       required value="<?= $controller->getSelectedAgility()->getValue() ?>">
            </label>
        </div>
    </div>
    <div class="block">
        <div class="panel">
            <label>smůla
                <select name="<?= $controller::ROLL_1D6 ?>">
                    <?php foreach (range(1, 6) as $roll) { ?>
                        <option value="<?= $roll ?>"
                            <?php if ($controller->getSelected1d6Roll()->getValue() === $roll) { ?>
                                selected
                            <?php } ?>>
                            <?= $roll ?>
                        </option>
                    <?php } ?>
                </select>
                <span class="hint">(1k6)</span>
            </label>
        </div>
    </div>
    <div class="block">
        <div class="panel">
            <label>atletika
                <select name="<?= $controller::ATHLETICS ?>">
                    <?php foreach (['-', 'I', 'II', 'III'] as $rankValue => $rankName) { ?>
                        <option value="<?= $rankValue ?>"
                            <?php if ($controller->getSelectedAthletics()->getCurrentSkillRank()->getValue() === $rankValue) { ?>
                                selected
                            <?php } ?>>
                            <?= $rankName ?>
                        </option>
                    <?php } ?>
                </select>
            </label>
        </div>
    </div>
</div>
<div class="block"><input type="submit" value="Přepočítat"></div>
