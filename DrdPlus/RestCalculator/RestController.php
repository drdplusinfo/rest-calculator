<?php
namespace DrdPlus\RestCalculator;

use DrdPlus\Codes\Body\ActivityAffectingHealingCode;
use DrdPlus\Codes\Body\ConditionsAffectingHealingCode;
use DrdPlus\Codes\Body\SeriousWoundOriginCode;
use DrdPlus\Codes\GenderCode;
use DrdPlus\Codes\RaceCode;
use DrdPlus\Codes\SubRaceCode;
use DrdPlus\DiceRolls\Templates\Rollers\Roller2d6DrdPlus;
use DrdPlus\DiceRolls\Templates\Rolls\Roll2d6DrdPlus;
use DrdPlus\Health\Afflictions\Affliction;
use DrdPlus\Health\HealingPower;
use DrdPlus\Health\Health;
use DrdPlus\Health\OrdinaryWound;
use DrdPlus\Health\SeriousWound;
use DrdPlus\Health\Wound;
use DrdPlus\Health\WoundSize;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Properties\Derived\Toughness;
use DrdPlus\Properties\Derived\WoundBoundary;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\RestPower;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Tables\Body\Healing\HealingConditionsPercents;
use DrdPlus\Tables\Tables;
use Granam\Integer\Tools\ToInteger;
use Granam\Tools\ValueDescriber;

class RestController extends \DrdPlus\CalculatorSkeleton\CalculatorController
{
    public const RACE = 'race';
    public const SUB_RACE = 'sub_race';
    public const GENDER = 'gender';
    public const ACTIVITY_AFFECTING_HEALING = 'activity_affecting_healing';
    public const ROLL_ON_HEALING = 'roll_on_healing';
    public const HEALING_POWER = 'healing_power';
    public const REST_POWER = 'rest_power';
    public const HEALED_BY_REGENERATION = 'healing_by_regeneration';
    public const HEALED_BY_TREATMENT = 'healing_by_treatment';
    public const SERIOUS_WOUND_ORIGIN = 'serious_wound_origin';
    public const ROLL_AGAINST_MALUS_FROM_WOUNDS = 'roll_against_malus_from_wounds';
    public const USER_ROLL_AGAINST_MALUS_FROM_WOUNDS = 'should_roll_against_malus_from_wounds';
    public const FRESH_WOUND_SIZE = 'fresh_wound_size';
    public const OLD_WOUND_SIZE = 'old_wound_size';
    public const STRENGTH = 'strength';
    public const WILL = 'will';
    public const FATIGUE = 'fatigue';
    public const ROLL_AGAINST_MALUS_FROM_FATIGUE = 'roll_against_malus_from_fatigue';
    public const SHOULD_ROLL_AGAINST_MALUS_FROM_FATIGUE = 'should_roll_against_malus_from_fatigue';
    public const TREATMENT_HEALING_POWER = 'treatment_healing_power';
    public const HEALING_CONDITIONS_PERCENTS = 'healing_conditions_percents';
    public const CONDITIONS_AFFECTING_HEALING = 'conditions_affecting_healing';
    public const WOUND_SIZE_OF_SERIOUS_WOUND_TO_HEAL = 'wound_size_of_serious_wound_to_heal';
    public const ORIGIN_OF_SERIOUS_WOUND_TO_HEAL = 'origin_of_serious_wound_to_heal';
    public const RESTING = 'resting';

    /** @var Health */
    private $health;
    /** @var Wound[] */
    private $wounds = [];
    /** @var int */
    private $regeneratedAmountOfWounds = 0;
    /** @var int */
    private $healedAmountOfOrdinaryWounds = 0;
    /** @var int */
    private $healedAmountOfSeriousWound = 0;
    /** @var int */
    private $restedAmountOfFatigue = 0;
    /** @var int|null */
    private $newRollAgainstMalusFromWounds;
    /** @var Stamina */
    private $stamina;
    /** @var int|null */
    private $newRollAgainstMalusFromFatigue;

    /**
     * @param string $sourceCodeUrl
     * @param string $documentRoot
     * @param string $vendorRoot
     * @param string|null $partsRoot
     * @param string|null $genericPartsRoot
     * @param int|null $cookiesTtl
     * @param array|null $selectedValues
     * @throws \DrdPlus\Health\Exceptions\UnknownAfflictionOriginatingWound
     * @throws \DrdPlus\Health\Exceptions\AfflictionIsAlreadyRegistered
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     * @throws \DrdPlus\RestCalculator\Exceptions\UnknownSeriousWoundToHeal
     */
    public function __construct(
        string $sourceCodeUrl,
        string $documentRoot,
        string $vendorRoot,
        string $partsRoot = null,
        string $genericPartsRoot = null,
        int $cookiesTtl = null,
        array $selectedValues = null
    )
    {
        parent::__construct($sourceCodeUrl, 'rest' /* cookies postfix */, $documentRoot, $vendorRoot, $partsRoot, $genericPartsRoot, $cookiesTtl, $selectedValues);
        $health = new Health();
        $this->addWounds($health)
            ->addAfflictions($health)
            ->healWounds($health)
            ->regenerateFromWounds($health);
        $this->health = $health;
        $stamina = new Stamina();
        $this->addFatigues($stamina)
            ->rest($stamina);
        $this->stamina = $stamina;
    }

    /**
     * @param Stamina $stamina
     * @return RestController
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     */
    private function addFatigues(Stamina $stamina): RestController
    {
        foreach ($this->getSelectedFatigues() as $fatigue) {
            $stamina->addFatigue($fatigue, $this->getCalculatedFatigueBoundary());
            $this->rollAgainstMalusFromFatigueIfNeeded($stamina);
        }

        return $this;
    }

    private function rollAgainstMalusFromFatigueIfNeeded(Stamina $stamina): void
    {
        if ($stamina->needsToRollAgainstMalusFromFatigue()) {
            $stamina->rollAgainstMalusFromFatigue(
                $this->getFinalWill(),
                $this->getSelectedRollAgainstMalusFromFatigue(),
                $this->getCalculatedFatigueBoundary()
            );
        }
    }

    /**
     * @return array|Fatigue[]
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     */
    public function getSelectedFatigues(): array
    {
        $fatigueValues = (array)$this->getCurrentValues()->getCurrentValue(self::FATIGUE);
        $fatigueValues = \array_map(function (string $value) {
            return \trim($value);
        }, $fatigueValues);
        $fatigueValues = \array_filter($fatigueValues, function (string $value) {
            return $value !== '';
        });
        $fatigueValues = \array_map(function (string $fatigueValue) {
            return ToInteger::toPositiveInteger($fatigueValue);
        }, $fatigueValues);

        return \array_map(function (int $fatigueValue) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return Fatigue::getIt($fatigueValue);
        }, $fatigueValues);
    }

    private function getCalculatedFatigueBoundary(): FatigueBoundary
    {
        return FatigueBoundary::getIt($this->getCalculatedEndurance(), Tables::getIt());
    }

    private function getCalculatedEndurance(): Endurance
    {
        return Endurance::getIt($this->getSelectedStrength(), $this->getFinalWill());
    }

    /**
     * @param Stamina $stamina
     * @return RestController
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function rest(Stamina $stamina): RestController
    {
        if ($this->isResting()) {
            $this->restedAmountOfFatigue = $stamina->rest(
                $this->getSelectedRestPower(),
                $this->getCalculatedFatigueBoundary(),
                $this->getCalculatedEndurance(),
                Tables::getIt()
            );
            if ($this->restedAmountOfFatigue > 0) {
                $this->lowerMemorizedFatigue($this->restedAmountOfFatigue);
            }
        }

        return $this;
    }

    private function lowerMemorizedFatigue(int $restedFatigue): void
    {
        $remainingRestedFatigue = $restedFatigue;
        $selectedFatigues = $this->getSelectedFatigues();
        $updatedFatigueValues = [];
        foreach ($selectedFatigues as $selectedFatigue) {
            $currentlyRestedAmountOfFatigue = \min($selectedFatigue->getValue(), $remainingRestedFatigue);
            $updatedFatigueValues[] = $selectedFatigue->getValue() - $currentlyRestedAmountOfFatigue;
            $remainingRestedFatigue -= $currentlyRestedAmountOfFatigue;
        }
        // has to rewrite it as we use 'request' as current values
        // TODO ? $this->rewriteValueFromRequest(self::FATIGUE, $updatedFatigueValues);
    }

    public function getSelectedRestPower(): RestPower
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new RestPower((int)$this->getCurrentValues()->getCurrentValue(self::REST_POWER));
    }

    /**
     * @return array|SeriousWound[]
     */
    public function getSelectedSeriousWounds(): array
    {
        return \array_filter($this->wounds, function (Wound $wound) {
            return $wound->isSerious();
        });
    }

    /**
     * @return array|OrdinaryWound[]
     */
    public function getSelectedOrdinaryWounds(): array
    {
        return \array_filter($this->wounds, function (Wound $wound) {
            return $wound->isOrdinary();
        });
    }

    /**
     * @param Health $health
     * @return RestController
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     */
    private function addWounds(Health $health): RestController
    {
        foreach ($this->getWoundsDetails() as $woundDetails) {
            [
                'woundSize' => $woundSize,
                'seriousWoundOrigin' => $seriousWoundOriginCode,
                'woundBoundary' => $woundBoundary,
            ] = $woundDetails;
            /**
             * @var WoundSize $woundSize
             * @var SeriousWoundOriginCode $seriousWoundOriginCode
             * @var WoundBoundary $woundBoundary
             */
            $this->wounds[] = $health->addWound($woundSize, $seriousWoundOriginCode, $woundBoundary);
            $this->rollAgainstMalusFromWoundsIfNeeded($health);
        }

        return $this;
    }

    private function rollAgainstMalusFromWoundsIfNeeded(Health $health): void
    {
        if ($health->needsToRollAgainstMalusFromWounds()) {
            $health->rollAgainstMalusFromWounds(
                $this->getFinalWill(),
                $this->getSelectedRollAgainstMalusFromWounds(),
                $this->getCalculatedWoundBoundary()
            );
        }
    }

    public function getSelectedRollAgainstMalusFromWounds(): Roll2d6DrdPlus
    {
        if (($this->newRollAgainstMalusFromWounds === null && $this->userRollAgainstMalusFromWounds()) // player demands it
            || $this->haveToRollAgainstMalusFromWounds() // new wound or heal happens, we have to re-roll
        ) {
            $this->newRollAgainstMalusFromWounds = Roller2d6DrdPlus::getIt()->roll()->getValue();
        }

        return Roller2d6DrdPlus::getIt()->generateRoll(
            $this->newRollAgainstMalusFromWounds
            ?? $this->getCurrentValues()->getCurrentValue(self::ROLL_AGAINST_MALUS_FROM_WOUNDS)
            ?? 6
        );
    }

    public function getSelectedRollAgainstMalusFromFatigue(): Roll2d6DrdPlus
    {
        if (($this->newRollAgainstMalusFromFatigue === null && $this->shouldRollAgainstMalusFromFatigue()) // player demands it
            || $this->haveToRollAgainstMalusFromFatigue() // new wound happens, we have to re-roll
        ) {
            $this->newRollAgainstMalusFromFatigue = Roller2d6DrdPlus::getIt()->roll()->getValue();
        }

        return Roller2d6DrdPlus::getIt()->generateRoll(
            $this->newRollAgainstMalusFromFatigue
            ?? $this->getCurrentValues()->getCurrentValue(self::ROLL_AGAINST_MALUS_FROM_FATIGUE)
            ?? 6
        );
    }

    public function shouldRollAgainstMalusFromFatigue(): bool
    {
        return (bool)$this->getCurrentValues()->getCurrentValue(self::SHOULD_ROLL_AGAINST_MALUS_FROM_FATIGUE);
    }

    public function userRollAgainstMalusFromWounds(): bool
    {
        return (bool)$this->getCurrentValues()->getCurrentValue(self::USER_ROLL_AGAINST_MALUS_FROM_WOUNDS);
    }

    private function getWoundsDetails(): array
    {
        $woundsDetails = [];
        $woundBoundary = $this->getCalculatedWoundBoundary();
        $selectedSeriousWoundOrigins = $this->selectedSeriousWoundOrigins();
        foreach ($this->getSelectedFreshWoundsSizes() as $index => $selectedWoundsSize) {
            $woundDetails['woundSize'] = $selectedWoundsSize;
            $woundDetails['seriousWoundOrigin'] = $selectedSeriousWoundOrigins[$index] ?? SeriousWoundOriginCode::findIt('');
            $woundDetails['woundBoundary'] = $woundBoundary;
            $woundsDetails[] = $woundDetails;
        }

        return $woundsDetails;
    }

    /**
     * @return array|WoundSize[]
     */
    public function getSelectedFreshWoundsSizes(): array
    {
        return $this->createWoundSizes((array)$this->getCurrentValues()->getCurrentValue(self::FRESH_WOUND_SIZE));
    }

    /**
     * @param array|int[] $woundValues
     * @return array|WoundSize[]
     */
    private function createWoundSizes(array $woundValues): array
    {
        if (!$woundValues) {
            return [];
        }
        $woundValues = \array_filter($woundValues, function ($woundSize) {
            return $woundSize > 0;
        });

        return \array_map(
            function ($woundSize) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                return WoundSize::createIt($woundSize);
            },
            $woundValues
        );
    }

    /**
     * @return array|WoundSize[]
     */
    public function getSelectedOldWoundsSizes(): array
    {
        return $this->createWoundSizes((array)$this->getCurrentValues()->getCurrentValue(self::OLD_WOUND_SIZE));
    }

    /**
     * @return array|SeriousWoundOriginCode[]
     */
    public function selectedSeriousWoundOrigins(): array
    {
        return \array_map(
            function (string $seriousWoundOrigin) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                return SeriousWoundOriginCode::findIt($seriousWoundOrigin);
            },
            (array)$this->getCurrentValues()->getCurrentValue(self::SERIOUS_WOUND_ORIGIN)
        );
    }

    /**
     * @return array|SeriousWoundOriginCode[]
     */
    public function getPossibleSeriousWoundOrigins(): array
    {
        return \array_map(function (string $woundOrigin) {
            return SeriousWoundOriginCode::getIt($woundOrigin);
        }, SeriousWoundOriginCode::getPossibleValues());
    }

    /**
     * @param Health $health
     * @return RestController
     * @throws \DrdPlus\Health\Exceptions\UnknownAfflictionOriginatingWound
     * @throws \DrdPlus\Health\Exceptions\AfflictionIsAlreadyRegistered
     */
    private function addAfflictions(Health $health): RestController
    {
        foreach ($this->getSelectedAfflictions() as $affliction) {
            $health->addAffliction($affliction);
        }

        return $this;
    }

    /**
     * @param Health $health
     * @return RestController
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\RestCalculator\Exceptions\UnknownSeriousWoundToHeal
     */
    private function regenerateFromWounds(Health $health): RestController
    {
        if ($this->isResting()) {
            $this->regeneratedAmountOfWounds = $health->regenerate(
                $this->getSelectedHealingPower(),
                $this->getCalculatedWoundBoundary()
            );
            if ($this->regeneratedAmountOfWounds > 0) {
                $this->refreshWoundSizeValuesFromRequest($health);
                $this->rollAgainstMalusFromWoundsIfNeeded($health);
            }
        }

        return $this;
    }

    private function refreshWoundSizeValuesFromRequest(Health $health): void
    {
        $oldWoundSizes = [];
        foreach ($health->getUnhealedOldWounds() as $unhealedOldWound) {
            $oldWoundSizes[] = $unhealedOldWound->getValue();
        }
        $freshWoundSizes = [];
        foreach ($health->getUnhealedFreshWounds() as $unhealedFreshWound) {
            $freshWoundSizes[] = $unhealedFreshWound->getValue();
        }
        // has to rewrite it as we use 'request' as current values
        // TODO ? $this->rewriteValueFromRequest(self::FRESH_WOUND_SIZE, $freshWoundSizes);
        // TODO ? $this->rewriteValueFromRequest(self::OLD_WOUND_SIZE, $oldWoundSizes);
    }

    /**
     * @return int
     */
    public function getRegeneratedAmountOfWounds(): int
    {
        return $this->regeneratedAmountOfWounds;
    }

    /**
     * @param Health $health
     * @return RestController
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\RestCalculator\Exceptions\UnknownSeriousWoundToHeal
     */
    private function healWounds(Health $health): RestController
    {
        return $this;
        // TODO
        if ($this->isResting()) {
            $selectedSeriousWoundToHeal = $this->getSelectedSeriousWoundToHeal();
            if ($selectedSeriousWoundToHeal) {
                $this->healedAmountOfSeriousWound = $this->healSeriousWound($selectedSeriousWoundToHeal, $health);
            } else {
                $this->healedAmountOfOrdinaryWounds = $this->healOrdinaryWounds($health);
            }
            $this->refreshWoundSizeValuesFromRequest($health);
        }

        return $this;
    }

    public function isResting(): bool
    {
        return !empty($_POST[self::RESTING]);
    }

    /**
     * @param SeriousWound $seriousWound
     * @param Health $health
     * @return int
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     */
    private function healSeriousWound(SeriousWound $seriousWound, Health $health): int
    {
        return $health->healFreshSeriousWound(
            $seriousWound,
            $this->getSelectedHealingPower(),
            $this->getCalculatedToughness(),
            Tables::getIt()
        );
    }

    /**
     * @param Health $health
     * @return int
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFromWoundsFirst
     */
    private function healOrdinaryWounds(Health $health): int
    {
        return $health->healFreshOrdinaryWounds(
            $this->getSelectedHealingPower(),
            $this->getCalculatedWoundBoundary()
        );
    }

    /**
     * @return SeriousWound|null
     * @throws \DrdPlus\RestCalculator\Exceptions\UnknownSeriousWoundToHeal
     */
    private function getSelectedSeriousWoundToHeal(): ?SeriousWound
    {
        $seriousWounds = $this->getSelectedSeriousWounds();
        if (!$seriousWounds) {
            return null;
        }
        $woundSize = $this->getSelectedWoundSizeOfSeriousWoundToHeal();
        $woundOrigin = $this->getSelectedOriginOfSeriousWoundToHeal();
        if (!$woundSize || !$woundOrigin) {
            return null;
        }
        foreach ($seriousWounds as $seriousWound) {
            if ($seriousWound->getWoundSize()->getValue() === $woundSize->getValue()
                && $seriousWound->getWoundOriginCode()->getValue() === $woundOrigin->getValue()
            ) {
                return $seriousWound;
            }
        }

        throw new Exceptions\UnknownSeriousWoundToHeal(
            "Got serious 02 wounds.php size {$woundSize} and origin '{$woundOrigin}', but we do not have such, only "
            . ValueDescriber::describe($seriousWounds)
        );
    }

    /**
     * @return WoundSize|null
     * @throws \DrdPlus\Health\Exceptions\WoundSizeCanNotBeNegative
     */
    public function getSelectedWoundSizeOfSeriousWoundToHeal(): ?WoundSize
    {
        $woundSize = $this->getCurrentValues()->getCurrentValue(self::WOUND_SIZE_OF_SERIOUS_WOUND_TO_HEAL);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $woundSize !== null
            ? WoundSize::createIt((int)$woundSize)
            : null;
    }

    public function getSelectedOriginOfSeriousWoundToHeal(): ?SeriousWoundOriginCode
    {
        $origin = $this->getCurrentValues()->getCurrentValue(self::ORIGIN_OF_SERIOUS_WOUND_TO_HEAL);

        return $origin !== null
            ? SeriousWoundOriginCode::getIt($origin)
            : null;
    }

    /**
     * @return int
     */
    public function getHealedAmountOfOrdinaryWounds(): int
    {
        return $this->healedAmountOfOrdinaryWounds;
    }

    /**
     * @return int
     */
    public function getHealedAmountOfSeriousWound(): int
    {
        return $this->healedAmountOfSeriousWound;
    }

    public function getCalculatedToughness(): Toughness
    {
        return Toughness::getIt($this->getSelectedStrength(), $this->getSelectedRaceCode(), $this->getSelectedSubRaceCode(), Tables::getIt());
    }

    public function getSelectedStrength(): Strength
    {
        return Strength::getIt((int)$this->getCurrentValues()->getCurrentValue(self::STRENGTH));
    }

    public function getSelectedWill(): Will
    {
        return Will::getIt((int)$this->getCurrentValues()->getCurrentValue(self::WILL));
    }

    public function getFinalWill(): Will
    {
        return Will::getIt(
            $this->getSelectedWill()->getValue() +
            Tables::getIt()->getRacesTable()->getWill($this->getSelectedRaceCode(), $this->getSelectedSubRaceCode(), $this->getSelectedGenderCode())
        );
    }

    /**
     * @return array|Affliction[]
     */
    public function getSelectedAfflictions(): array
    {
        return [];
    }

    public function isDead(): bool
    {
        return $this->isDeadBecauseOfWounds() || $this->isDeadBecauseOfFatigue();
    }

    public function isDeadBecauseOfWounds(): bool
    {
        return !$this->health->isAlive($this->getCalculatedWoundBoundary());
    }

    public function isDeadBecauseOfFatigue(): bool
    {
        return !$this->stamina->isAlive($this->getCalculatedFatigueBoundary());
    }

    public function isConscious(): bool
    {
        return $this->health->isConscious($this->getCalculatedWoundBoundary()) && $this->stamina->isConscious($this->getCalculatedFatigueBoundary());
    }

    private function getCalculatedWoundBoundary(): WoundBoundary
    {
        return WoundBoundary::getIt($this->getCalculatedToughness(), Tables::getIt());
    }

    public function getSelectedHealingPower(): HealingPower
    {
        if ($this->isHealedByRegeneration()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return HealingPower::createForRegeneration(
                $this->getSelectedRaceCode(),
                $this->getSelectedSubRaceCode(),
                $this->getCalculatedToughness(),
                $this->getSelectedActivityAffectingHealingCode(),
                $this->getSelectedConditionsAffectingHealingCode(),
                $this->getSelectedHealingConditionsPercents(),
                $this->getSelectedRollOnHealing(),
                Tables::getIt()
            );
        }

        return HealingPower::createForTreatment(
            $this->getSelectedTreatmentHealingPowerValue(),
            $this->getCalculatedToughness(),
            Tables::getIt()
        );
    }

    public function getSelectedConditionsAffectingHealingCode(): ConditionsAffectingHealingCode
    {
        return ConditionsAffectingHealingCode::findIt($this->getCurrentValues()->getCurrentValue(self::CONDITIONS_AFFECTING_HEALING));
    }

    /**
     * @return HealingConditionsPercents
     */
    public function getSelectedHealingConditionsPercents(): HealingConditionsPercents
    {
        $healingConditionsPercents = $this->getCurrentValues()->getCurrentValue(self::HEALING_CONDITIONS_PERCENTS);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new HealingConditionsPercents(
            $healingConditionsPercents >= 0 && $healingConditionsPercents <= 100
                ? $healingConditionsPercents
                : 0
        );
    }

    public function getSelectedRollOnHealing(): Roll2d6DrdPlus
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return Roller2d6DrdPlus::getIt()->generateRoll((int)$this->getCurrentValues()->getCurrentValue(self::ROLL_ON_HEALING));
    }

    public function getSelectedTreatmentHealingPowerValue(): int
    {
        return (int)$this->getCurrentValues()->getCurrentValue(self::TREATMENT_HEALING_POWER);
    }

    public function isHealedByRegeneration(): bool
    {
        return (bool)$this->getCurrentValues()->getCurrentValue(self::HEALED_BY_REGENERATION);
    }

    public function getSelectedRaceCode(): RaceCode
    {
        return RaceCode::getIt($this->getCurrentValues()->getCurrentValue(self::RACE) ?: RaceCode::HUMAN);
    }

    public function getSelectedSubRaceCode(): SubRaceCode
    {
        $subRaceCode = SubRaceCode::findIt($this->getCurrentValues()->getCurrentValue(self::SUB_RACE));
        $raceCode = $this->getSelectedRaceCode();
        if ($subRaceCode->isRace($raceCode)) {
            return $subRaceCode;
        }

        return $raceCode->getDefaultSubRaceCode();
    }

    public function canPickSubRace(SubRaceCode $subRaceCode): bool
    {
        return $subRaceCode->isRace($this->getSelectedRaceCode());
    }

    public function getSelectedGenderCode(): GenderCode
    {
        return GenderCode::findIt($this->getCurrentValues()->getCurrentValue(self::GENDER));
    }

    private function getSelectedActivityAffectingHealingCode(): ActivityAffectingHealingCode
    {
        return ActivityAffectingHealingCode::findIt($this->getCurrentValues()->getCurrentValue(self::ACTIVITY_AFFECTING_HEALING));
    }

    public function isHealedByTreatment(): bool
    {
        return (bool)$this->getCurrentValues()->getCurrentValue(self::HEALED_BY_TREATMENT);
    }

    public function getTotalHitPoints(): int
    {
        return $this->health->getHealthMaximum($this->getCalculatedWoundBoundary());
    }

    public function getSumOfWounds(): int
    {
        return $this->health->getUnhealedWoundsSum();
    }

    public function getSumOfOrdinaryWounds(): int
    {
        return $this->health->getUnhealedOrdinaryWoundsSum();
    }

    public function getRemainingHitPoints(): int
    {
        $remainingHitPoints = $this->getTotalHitPoints() - $this->getSumOfWounds();
        if ($remainingHitPoints > 0) {
            return $remainingHitPoints;
        }

        return 0;
    }

    /**
     * @return array|RaceCode[]
     */
    public function getRaceCodes(): array
    {
        return \array_map(function (string $race) {
            return RaceCode::getIt($race);
        }, RaceCode::getPossibleValues());
    }

    /**
     * @return array|SubRaceCode[]
     */
    public function getSubRaceCodes(): array
    {
        return \array_map(function (string $race) {
            return SubRaceCode::getIt($race);
        }, SubRaceCode::getPossibleValues());
    }

    /**
     * @return array|GenderCode[]
     */
    public function getGenderCodes(): array
    {
        return \array_map(function (string $gender) {
            return GenderCode::getIt($gender);
        }, GenderCode::getPossibleValues());
    }

    public function hasFreshWounds(): bool
    {
        return $this->health->hasFreshWounds();
    }

    public function getMalusFromWounds(): int
    {
        return $this->health->getSignificantMalusFromPains($this->getCalculatedWoundBoundary());
    }

    public function mayHaveMalusFromWounds(): bool
    {
        return $this->health->mayHaveMalusFromWounds($this->getCalculatedWoundBoundary()) > 0;
    }

    public function mayHaveMalusFromFatigue(): bool
    {
        return $this->stamina->mayHaveMalusFromFatigue($this->getCalculatedFatigueBoundary()) > 0;
    }

    public function getTotalRollAgainstMalusFromWounds(): int
    {
        return $this->getFinalWill()->getValue() + $this->getSelectedRollAgainstMalusFromWounds()->getValue();
    }

    public function haveToRollAgainstMalusFromWounds(): bool
    {
        $previousFreshWoundsSum = \array_sum((array)$this->getHistory()->getValue(self::FRESH_WOUND_SIZE));
        $previousOldWoundsSum = \array_sum((array)$this->getHistory()->getValue(self::OLD_WOUND_SIZE));
        $currentFreshWoundsSum = \array_sum($this->getSelectedFreshWoundsSizes());
        $currentOldWoundsSum = \array_sum($this->getSelectedOldWoundsSizes());

        // of previous wounds were lower, then we are freshly wounded or if lesser then we are freshly healed
        return ($previousOldWoundsSum + $previousFreshWoundsSum) !== ($currentOldWoundsSum + $currentFreshWoundsSum);
    }

    public function haveToRollAgainstMalusFromFatigue(): bool
    {
        $previousFatigueCount = \count((array)$this->getHistory()->getValue(self::FATIGUE));
        $currentFatiguesCount = \count($this->getSelectedFatigues());

        return $previousFatigueCount < $currentFatiguesCount;
    }

    public function getFormattedBonus(int $bonus): string
    {
        return $bonus >= 0
            ? "+$bonus"
            : (string)$bonus;
    }

    public function getTotalStamina(): int
    {
        return $this->stamina->getStaminaMaximum($this->getCalculatedFatigueBoundary());
    }

    public function getRemainingStaminaAmount(): int
    {
        return $this->stamina->getRemainingStaminaAmount($this->getCalculatedFatigueBoundary());
    }

    public function getTotalFatigue(): int
    {
        return $this->stamina->getFatigue()->getValue();
    }

    /**
     * @return int
     */
    public function getRestedAmountOfFatigue(): int
    {
        return $this->restedAmountOfFatigue;
    }
}