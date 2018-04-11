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
use DrdPlus\Health\Wound;
use DrdPlus\Health\WoundSize;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Properties\Derived\Toughness;
use DrdPlus\Properties\Derived\WoundBoundary;
use DrdPlus\Tables\Body\Healing\HealingConditionsPercents;
use DrdPlus\Tables\Tables;
use Granam\Integer\Tools\ToInteger;

class Controller extends \DrdPlus\Configurator\Skeleton\Controller
{
    public const RACE = 'race';
    public const SUB_RACE = 'sub_race';
    public const ACTIVITY_AFFECTING_HEALING = 'activity_affecting_healing';
    public const ROLL_ON_HEALING = 'roll_on_healing';
    public const HEALING_POWER = 'healing_power';
    public const HEALED_BY_REGENERATION = 'healing_by_regeneration';
    public const HEALED_BY_TREATMENT = 'healing_by_treatment';
    public const SERIOUS_WOUND_ORIGINS = 'serious_wound_origins';
    public const WOUND_SIZES = 'wound_sizes';
    public const STRENGTH = 'strength';
    public const TREATMENT_HEALING_POWER = 'treatment_healing_power';
    public const HEALING_CONDITIONS_PERCENTS = 'healing_conditions_percents';
    public const CONDITIONS_AFFECTING_HEALING = 'conditions_affecting_healing';

    /** @var Health */
    private $health;
    /** @var Wound[] */
    private $wounds = [];
    /** @var int */
    private $healedAmountOfOrdinaryWounds;

    /**
     * @throws \DrdPlus\Health\Exceptions\UnknownAfflictionOriginatingWound
     * @throws \DrdPlus\Health\Exceptions\AfflictionIsAlreadyRegistered
     * @throws \DrdPlus\Health\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function __construct()
    {
        parent::__construct('rest' /* cookies postfix */);
        $health = new Health();
        $this->addWounds($health)
            ->addAfflictions($health)
            ->healOrdinaryWounds($health);
        $this->health = $health;
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
            $this->getValueFromRequest(self::WOUND_SIZES)
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
            (array)$this->getValueFromRequest(self::SERIOUS_WOUND_ORIGINS)
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
     */
    private function healOrdinaryWounds(Health $health): Controller
    {
        $this->healedAmountOfOrdinaryWounds = $health->healFreshOrdinaryWounds(
            $this->getSelectedHealingPower(),
            $this->getCalculatedToughness(),
            Tables::getIt()
        );

        return $this;
    }

    /**
     * @return int
     */
    public function getHealedAmountOfOrdinaryWounds(): int
    {
        return $this->healedAmountOfOrdinaryWounds;
    }

    public function getCalculatedToughness(): Toughness
    {
        return Toughness::getIt($this->getSelectedStrength(), $this->getSelectedRaceCode(), $this->getSelectedSubRaceCode(), Tables::getIt());
    }

    public function getSelectedStrength(): Strength
    {
        return Strength::getIt((int)$this->getValueFromRequest(self::STRENGTH));
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