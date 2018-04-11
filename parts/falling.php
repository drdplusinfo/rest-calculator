<?php
use DrdPlus\Codes\Transport\RidingAnimalCode;

/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="panel">
    <h2 id="pad"><a href="#pad">Pád</a></h2>
    <div class="block">
        <div class="panel">
            <label>
                <input id="onHorseback" type="radio" value="<?= $controller::HORSEBACK ?>"
                       name="<?= $controller::SERIOUS_WOUND_ORIGINS ?>"
                       required
                       <?php if ($controller->isFallingFromHorseback()) { ?>checked="checked" <?php } ?>>
                <strong>padáš z "koně"</strong>
            </label>
            <label>
                <select class="horseRelated" name="<?= $controller::HORSE_HEIGHT ?>">
                    <?php /**
                     * @var float $heightInMeters
                     * @var RidingAnimalCode $ridingAnimal
                     */
                    foreach ($controller->getRidingAnimalsWithHeight() as $heightInMeters => $ridingAnimal) { ?>
                        <option value="<?= $heightInMeters ?>"
                                <?php if ($controller->isRidingAnimalSelected($heightInMeters)) { ?>selected="selected"<?php } ?>>
                            <?= "{$ridingAnimal} ({$heightInMeters} m)" ?>
                        </option>
                    <?php } ?>
                </select>
            </label>
            <label>
                při jeho skoku
                <input type="checkbox" class="horseRelated"
                       name="<?= $controller::HEALING_CONDITIONS_PERCENTS ?>"
                       <?php if ($controller->isHorseJumping()) { ?>checked="checked"<?php } ?>>
            </label>
        </div>
        <div class="block">
            <label>
                pohyb koně
                <select name="<?= $controller::RIDING_MOVEMENT ?>" class="horseRelated">
                    <?php foreach ($controller->getRidingAnimalMovements() as $ridingAnimalMovement) { ?>
                        <option value="<?= $ridingAnimalMovement->getValue() ?>"
                                <?php if ($controller->isRidingAnimalMovementSelected($ridingAnimalMovement)) { ?>selected<?php } ?>>
                            <?= $ridingAnimalMovement->translateTo('cs') . " (+{$controller->getBaseOfWoundsModifierByMovement($ridingAnimalMovement, false)} ZZ)" ?>
                        </option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="panel">
        </div>
        <div class="block">
            <div class="panel">
                <label>
                    <input type="radio" value="<?= $controller::HEIGHT ?>"
                           name="<?= $controller::SERIOUS_WOUND_ORIGINS ?>"
                           required
                           <?php if ($controller->isFallingFromHeight()) { ?>checked="checked" <?php } ?>>
                    <strong>padáš z výšky</strong>
                </label>
                <label>
                    <input type="number" name="<?= $controller::HEIGHT_OF_FALL ?>" class="few-numbers" min="0" max="999"
                           value="<?= $controller->getSelectedHeightOfFall()->getValue() ?>"
                           placeholder="v metrech"> metrů
                </label>
            </div>
        </div>
        <div class="block">
            <label>spadeš na
                <select name="<?= $controller::WOUND_SIZES ?>">
                    <?php foreach ($controller->getSurfaces() as $surface) { ?>
                        <option value="<?= $surface->getValue() ?>"
                                <?php if ($controller->isSurfaceSelected($surface)) { ?>selected<?php } ?>>
                            <?= "{$surface->translateTo('cs')} ({$controller->getWoundsModifierBySurface($surface)->getValue()} ZZ)" ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="block">
            <label>padáš na hlavu <span class="hint">(+2 ZZ)</span>
                <input name="<?= $controller::CONDITIONS_AFFECTING_HEALING ?>" value="1" type="checkbox"
                       <?php if ($controller->isHitToHead()) { ?>checked="checked" <?php } ?>>
            </label>
        </div>
        <div class="block">
            <label>tvoje váha
                <input name="<?= $controller::BODY_WEIGHT ?>" type="number" placeholder="váha v kg" class="few-numbers"
                       min="0" max="250"
                       required
                       value="<?= $controller->getSelectedBodyWeight() ? $controller->getSelectedBodyWeight()->getValue() : '' ?>">
                kg
            </label>
        </div>
        <div class="block">
            <label title="třeba poník váží 240 kg, válečný kůň 700 kg, slon 6 tun, ale nespadnou na tebe celí">váha
                věcí, které spadly na tebe <span class="hint">(zbroj nepočítej)</span>
                <input name="<?= $controller::STRENGTH ?>" type="number" placeholder="váha v kg" class="few-numbers"
                       min="0" max="10000"
                       value="<?= $controller->getSelectedItemsWeight() ? $controller->getSelectedItemsWeight()->getValue() : '' ?>">
                kg
            </label>
        </div>
        <div class="block"><input type="submit" value="Přepočítat"></div>
    </div>
</div>