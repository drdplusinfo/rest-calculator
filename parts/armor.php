<?php
/** @var \DrdPlus\Calculators\Rest\Controller $controller */
?>
<div class="block">
    <h2 id="zbroj"><a href="#zbroj" class="inner">Zbroj</a></h2>
    <div class="block">
        <label><select name="<?= $controller::HEALING_POWER ?>">
                <?php foreach ($controller->getPossibleBodyArmors() as $bodyArmor) { ?>
                    <option value="<?= $bodyArmor->getValue() ?>"
                            <?php if ($controller->isBodyArmorSelected($bodyArmor)){ ?>selected<?php } ?>>
                        <?= $bodyArmor->translateTo('cs') . ' ' . ($controller->getProtectionOfBodyArmor($bodyArmor) > 0 ? ('+' . $controller->getProtectionOfBodyArmor($bodyArmor)) : '') ?>
                    </option>
                <?php } ?>
            </select>
            <span class="hint">(může snížit zranění z dopadu na ostrý povrch)</span>
        </label>
    </div>
    <div class="block">
        <label>
            <select name="<?= $controller::HEALED_BY_REGENERATION ?>">
                <?php foreach ($controller->getPossibleHelms() as $helm) { ?>
                    <option value="<?= $helm->getValue() ?>"
                            <?php if ($controller->isHelmSelected($helm)){ ?>selected<?php } ?>>
                        <?= $helm->translateTo('cs') . ' ' . ($controller->getProtectionOfHelm($helm) > 0 ? ('+' . $controller->getProtectionOfHelm($helm)) : '') ?>
                    </option>
                <?php } ?>
            </select>
            <span class="hint">(při zranění hlavy)</span>
        </label>
    </div>
</div>
<div class="block"><input type="submit" value="Přepočítat"></div>
