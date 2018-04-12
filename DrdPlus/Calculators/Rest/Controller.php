<?php
namespace DrdPlus\Calculators\Rest;

use DrdPlus\Codes\Body\ActivityAffectingHealingCode;
use DrdPlus\Codes\Body\ConditionsAffectingHealingCode;
use DrdPlus\Codes\Body\SeriousWoundOriginCode;
use DrdPlus\Codes\RaceCode;
use DrdPlus\Codes\SubRaceCode;
use DrdPlus\Codes\Armaments\BodyArmorCode;
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

class Controller extends \DrdPlus\Configurator\Skeleton\Controller
{
    public const RACE = 'race';
    public const SUB_RACE = 'sub_race';
    public const ACTIVITY_AFFECTING_HEALING = 'activity_affecting_healing';
    public const ROLL_ON_HEALING = 'roll_on_healing';
    public const HEALING_POWER = 'healing_power';
    public const REST_POWER = 'rest_power';
    public const HEALED_BY_REGENERATION = 'healing_by_regeneration';
    public const HEALED_BY_TREATMENT = 'healing_by_treatment';
    public const SERIOUS_WOUND_ORIGIN = 'serious_wound_origin';
    public const WOUND_SIZE = 'wound_size';
    public const STRENGTH = 'strength';
    public const WILL = 'will';
    public const FATIGUE = 'fatigue';
    public const TREATMENT_HEALING_POWER = 'treatment_healing_power';
    public const HEALING_CONDITIONS_PERCENTS = 'healing_conditions_percents';
    public const CONDITIONS_AFFECTING_HEALING = 'conditions_affecting_healing';
    public const WOUND_SIZE_OF_SERIOUS_WOUND_TO_HEAL = 'wound_size_of_serious_wound_to_heal';
    public const ORIGIN_OF_SERIOUS_WOUND_TO_HEAL = 'origin_of_serious_wound_to_heal';

    /** @var Health */
    private $health;
    /** @var Wound[] */
    private $wounds = [];
    /** @var int */
    private $healedAmountOfOrdinaryWounds;
    /** @var int */
    private $healedAmountOfSeriousWound;
    /** @var int */
    private $restedAmountOfFatigue;

    /**
     * @throws \DrdPlus\Health\Exceptions\UnknownAfflictionOriginatingWound
     * @throws \DrdPlus\Health\Exceptions\AfflictionIsAlreadyRegistered
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     * @throws \DrdPlus\Calculators\Rest\Exceptions\UnknownSeriousWoundToHeal
     */
    public function __construct()
    {
        parent::__construct('rest' /* cookies postfix */);
        $health = new Health();
        $this->addWounds($health)
            ->addAfflictions($health)
            ->healWounds($health);
        $this->health = $health;
        $stamina = new Stamina();
        $this->addFatigues($stamina)
            ->rest($stamina);
    }

    /**
     * @param Stamina $stamina
     * @return Controller
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     */
    private function addFatigues(Stamina $stamina): Controller
    {
        foreach ($this->getSelectedFatigues() as $fatigue) {
            $stamina->addFatigue($fatigue, $this->getCalculatedFatigueBoundary());
        }

        return $this;
    }

    /**
     * @return array|Fatigue[]
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\PositiveIntegerCanNotBeNegative
     */
    public function getSelectedFatigues(): array
    {
        $fatigueValues = (array)$this->getValueFromRequest(self::FATIGUE);
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
        return Endurance::getIt($this->getSelectedStrength(), $this->getSelectedWill());
    }

    /**
     * @param Stamina $stamina
     * @return Controller
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function rest(Stamina $stamina): Controller
    {
        $this->restedAmountOfFatigue = $stamina->rest(
            $this->getSelectedRestPower(),
            $this->getCalculatedFatigueBoundary(),
            $this->getCalculatedEndurance(),
            Tables::getIt()
        );

        return $this;
    }

    public function getSelectedRestPower(): RestPower
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new RestPower((int)$this->getValueFromRequest(self::REST_POWER));
    }

    /**
     * @return array|SeriousWound[]
     */
    public function getSeriousWounds(): array
    {
        return \array_filter($this->wounds, function (Wound $wound) {
            return $wound->isSerious();
        });
    }

    /**
     * @return array|OrdinaryWound[]
     */
    public function getOrdinaryWounds(): array
    {
        return \array_filter($this->wounds, function (Wound $wound) {
            return $wound->isOrdinary();
        });
    }

    /**
     * @param Health $health
     * @return Controller
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function addWounds(Health $health): Controller
    {
        foreach ($this->getWoundsDetails() as $woundDetails) {
            [
                'woundSize' => $woundSize,
                'seriousWoundOrigin' => $seriousWoundOriginCode,
                'woundBoundary' => $woundBoundary
            ] = $woundDetails;
            /**
             * @var WoundSize $woundSize
             * @var SeriousWoundOriginCode $seriousWoundOriginCode
             * @var WoundBoundary $woundBoundary
             */
            $this->wounds[] = $health->createWound($woundSize, $seriousWoundOriginCode, $woundBoundary);
        }

        return $this;
    }

    private function getWoundsDetails(): array
    {
        $woundsDetails = [];
        $selectedSeriousWoundOrigins = $this->selectedSeriousWoundOrigins();
        $woundBoundary = $this->getCalculatedWoundBoundary();
        foreach ($this->getSelectedWoundsSizes() as $index => $selectedWoundsSize) {
            $woundDetails['woundSize'] = $selectedWoundsSize;
            $woundDetails['seriousWoundOrigin'] = $selectedSeriousWoundOrigins[$index];
            $woundDetails['woundBoundary'] = $woundBoundary;
            $woundsDetails[] = $woundDetails;
        }

        return $woundsDetails;
    }

    public function getSelectedWoundsSizes(): array
    {
        return \array_map(
            function ($woundSize) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                return ToInteger::toInteger($woundSize);
            },
            $this->getValueFromRequest(self::WOUND_SIZE)
        );
    }

    /**
     * @return array|SeriousWoundOriginCode[]
     */
    public function selectedSeriousWoundOrigins(): array
    {
        return \array_map(
            function (string $seriousWoundOrigin) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                return SeriousWoundOriginCode::getEnum($seriousWoundOrigin);
            },
            (array)$this->getValueFromRequest(self::SERIOUS_WOUND_ORIGIN)
        );
    }

    /**
     * @param Health $health
     * @return Controller
     * @throws \DrdPlus\Health\Exceptions\UnknownAfflictionOriginatingWound
     * @throws \DrdPlus\Health\Exceptions\AfflictionIsAlreadyRegistered
     */
    private function addAfflictions(Health $health): Controller
    {
        foreach ($this->getSelectedAfflictions() as $affliction) {
            $health->addAffliction($affliction);
        }

        return $this;
    }

    /**
     * @param Health $health
     * @return Controller
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     * @throws \DrdPlus\Calculators\Rest\Exceptions\UnknownSeriousWoundToHeal
     */
    private function healWounds(Health $health): Controller
    {
        $selectedSeriousWoundToHeal = $this->getSelectedSeriousWoundToHeal();
        if ($selectedSeriousWoundToHeal) {
            $this->healedAmountOfSeriousWound = $this->healSeriousWound($selectedSeriousWoundToHeal, $health);
        } else {
            $this->healedAmountOfOrdinaryWounds = $this->healOrdinaryWounds($health);
        }

        return $this;
    }

    /**
     * @param SeriousWound $seriousWound
     * @param Health $health
     * @return int
     * @throws \DrdPlus\Health\Exceptions\UnknownSeriousWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\ExpectedFreshWoundToHeal
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
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
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function healOrdinaryWounds(Health $health): int
    {
        return $health->healFreshOrdinaryWounds(
            $this->getSelectedHealingPower(),
            $this->getCalculatedToughness(),
            Tables::getIt()
        );
    }

    /**
     * @return SeriousWound|null
     * @throws \DrdPlus\Calculators\Rest\Exceptions\UnknownSeriousWoundToHeal
     */
    private function getSelectedSeriousWoundToHeal(): ?SeriousWound
    {
        $seriousWounds = $this->getSeriousWounds();
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
            "Got serious wounds size {$woundSize} and origin '{$woundOrigin}', but we do not have such, only "
            . ValueDescriber::describe($seriousWounds)
        );
    }

    /**
     * @return WoundSize|null
     * @throws \DrdPlus\Health\Exceptions\WoundSizeCanNotBeNegative
     */
    public function getSelectedWoundSizeOfSeriousWoundToHeal(): ?WoundSize
    {
        $woundSize = $this->getValueFromRequest(self::WOUND_SIZE_OF_SERIOUS_WOUND_TO_HEAL);

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $woundSize !== null
            ? WoundSize::createIt((int)$woundSize)
            : null;
    }

    public function getSelectedOriginOfSeriousWoundToHeal(): ?SeriousWoundOriginCode
    {
        $origin = $this->getValueFromRequest(self::ORIGIN_OF_SERIOUS_WOUND_TO_HEAL);

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
        return Strength::getIt((int)$this->getValueFromRequest(self::STRENGTH));
    }

    public function getSelectedWill(): Will
    {
        return Will::getIt((int)$this->getValueFromRequest(self::WILL));
    }

    /**
     * @return array|Affliction[]
     */
    public function getSelectedAfflictions(): array
    {
        return [];
    }

    public function isConscious(): bool
    {
        return $this->health->isConscious($this->getCalculatedWoundBoundary());
    }

    private function getCalculatedWoundBoundary(): WoundBoundary
    {
        return WoundBoundary::getIt($this->getCalculatedToughness(), Tables::getIt());
    }

    /**
     * @return array|BodyArmorCode[]
     */
    public function getPossibleBodyArmors(): array
    {
        return \array_map(
            function (string $armorValue) {
                return BodyArmorCode::getIt($armorValue);
            },
            BodyArmorCode::getPossibleValues()
        );
    }

    public function getSelectedHealingPower(): HealingPower
    {
        if ($this->isHealedByRegeneration()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return HealingPower::createForRegeneration(
                $this->getSelectedRaceCode(),
                $this->getSelectedSubRaceCode(),
                $this->getSelectedActivityAffectingHealingCode(),
                $this->getSelectedConditionsAffectingHealingCode(),
                $this->getSelectedHealingConditionsPercents(),
                $this->getSelectedRollOnHealing(),
                Tables::getIt()
            );
        }

        return HealingPower::createForTreatment($this->getSelectedTreatmentHealingPowerValue(), Tables::getIt());
    }

    public function getSelectedConditionsAffectingHealingCode(): ConditionsAffectingHealingCode
    {
        return ConditionsAffectingHealingCode::findIt($this->getValueFromRequest(self::CONDITIONS_AFFECTING_HEALING));
    }

    /**
     * @return HealingConditionsPercents
     */
    public function getSelectedHealingConditionsPercents(): HealingConditionsPercents
    {
        $healingConditionsPercents = $this->getValueFromRequest(self::HEALING_CONDITIONS_PERCENTS);

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
        return Roller2d6DrdPlus::getIt()->generateRoll((int)$this->getValueFromRequest(self::ROLL_ON_HEALING));
    }

    public function getSelectedTreatmentHealingPowerValue(): int
    {
        return (int)$this->getValueFromRequest(self::TREATMENT_HEALING_POWER);
    }

    public function isHealedByRegeneration(): bool
    {
        return (bool)$this->getValueFromRequest(self::HEALED_BY_REGENERATION);
    }

    public function getSelectedRaceCode(): RaceCode
    {
        return RaceCode::getIt($this->getValueFromRequest(self::RACE) ?: RaceCode::HUMAN);
    }

    public function getSelectedSubRaceCode(): SubRaceCode
    {
        return SubRaceCode::findIt($this->getValueFromRequest(self::SUB_RACE));
    }

    private function getSelectedActivityAffectingHealingCode(): ActivityAffectingHealingCode
    {
        return ActivityAffectingHealingCode::findIt($this->getValueFromRequest(self::ACTIVITY_AFFECTING_HEALING));
    }

    public function isHealedByTreatment(): bool
    {
        return (bool)$this->getValueFromRequest(self::HEALED_BY_TREATMENT);
    }

}