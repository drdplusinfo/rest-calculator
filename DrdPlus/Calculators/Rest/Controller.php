<?php
namespace DrdPlus\Calculators\Rest;

use DrdPlus\DiceRolls\Templates\DiceRolls\Dice1d6Roll;
use DrdPlus\DiceRolls\Templates\Rolls\Roll1d6;
use DrdPlus\Background\BackgroundParts\Ancestry;
use DrdPlus\Background\BackgroundParts\SkillPointsFromBackground;
use DrdPlus\Codes\Armaments\BodyArmorCode;
use DrdPlus\Codes\Armaments\HelmCode;
use DrdPlus\Codes\Units\DistanceUnitCode;
use DrdPlus\Codes\Environment\LandingSurfaceCode;
use DrdPlus\Codes\Transport\RidingAnimalCode;
use DrdPlus\Codes\Transport\RidingAnimalMovementCode;
use DrdPlus\Health\Afflictions\Affliction;
use DrdPlus\Health\HealingPower;
use DrdPlus\Health\Health;
use DrdPlus\Health\SeriousWoundOrigin;
use DrdPlus\Health\Wound;
use DrdPlus\Health\WoundSize;
use DrdPlus\Person\ProfessionLevels\ProfessionFirstLevel;
use DrdPlus\Professions\Commoner;
use DrdPlus\Properties\Base\Agility;
use DrdPlus\Properties\Body\BodyWeight;
use DrdPlus\Properties\Derived\WoundBoundary;
use DrdPlus\Skills\Physical\Athletics;
use DrdPlus\Skills\Physical\PhysicalSkillPoint;
use DrdPlus\Tables\Measurements\Distance\Distance;
use DrdPlus\Tables\Measurements\Weight\Weight;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Tables;
use Granam\Integer\IntegerWithHistory;
use Granam\Integer\PositiveIntegerObject;

class Controller extends \DrdPlus\Configurator\Skeleton\Controller
{
    public const AGILITY = 'agility';
    public const WITHOUT_REACTION = 'without_reaction';
    public const ATHLETICS = 'athletics';
    public const ROLL_1D6 = 'roll_1d6';
    public const HEALING_POWER = 'healing_power';
    public const HEALED_BY_REGENERATION = 'healing_by_regeneration';
    public const HEALED_BY_TREATMENT = 'healing_by_treatment';
    public const FALLING_FROM = 'falling_from';
    public const HORSEBACK = 'horseback';
    public const HEIGHT = 'height';
    public const HEIGHT_OF_FALL = 'height_of_fall';
    public const RIDING_MOVEMENT = 'riding_movement';
    public const HORSE_HEIGHT = 'horse_height';
    public const JUMPING = 'jumping';
    public const SURFACE = 'surface';
    public const BODY_WEIGHT = 'body_weight';
    public const ITEMS_WEIGHT = 'items_weight';
    public const JUMP_IS_CONTROLLED = 'jump_is_controlled';
    public const HORSE_IS_JUMPING = 'horse_is_jumping';
    public const HEAD = 'head';

    /** @var Health */
    private $health;
    /** @var Wound[] */
    private $wounds = [];

    public function __construct()
    {
        parent::__construct('rest' /* cookies postfix */);
        $health = new Health();
        $this->addWounds($health);
        $this->addAfflictions($health);
        $this->healOrdinaryWounds($health);
        $this->health = $health;
    }

    private function addWounds(Health $health): Controller
    {
        foreach ($this->getWoundsDetails() as $woundDetails) {
            [
                'woundSize' => $woundSize,
                'seriousWoundOrigin' => $seriousWoundOrigin,
                'woundBoundary' => $woundBoundary
            ] = $woundDetails;
            /**
             * @var WoundSize $woundSize
             * @var SeriousWoundOrigin $seriousWoundOrigin
             * @var WoundBoundary $woundBoundary
             */
            $this->wounds[] = $health->createWound($woundSize, $seriousWoundOrigin, $woundBoundary);
        }

        return $this;
    }

    private function getWoundsDetails(): array
    {
        return []; // TODO
    }

    private function addAfflictions(Health $health)
    {
        foreach ($this->getCurrentAfflictions() as $affliction) {
            $health->addAffliction($affliction);
        }
    }

    private function healOrdinaryWounds(Health $health)
    {
        $health->healNewOrdinaryWoundsUpTo($this->getCurrentHealingPower(), $this->getCurrentToughness(), Tables::getIt());
    }

    /**
     * @return array|Affliction[]
     */
    public function getCurrentAfflictions(): array
    {
        return []; // TODO
    }

    public function isConscious(): bool
    {
        return $this->health->isConscious($this->getCurrentWoundBoundary());
    }

    private function getCurrentWoundBoundary(): WoundBoundary
    {
        // TODO
    }

    /**
     * @return array|BodyArmorCode[]
     */
    public function getPossibleBodyArmors(): array
    {
        return array_map(function (string $armorValue) {
            return BodyArmorCode::getIt($armorValue);
        }, BodyArmorCode::getPossibleValues());
    }

    public function getCurrentHealingPower(): bool
    {
        if ($this->isHealedByRegeneration()) {
            return HealingPower::createForRegeneration(
                $this->getCurrentRaceCode(),

            );
        }
    }

    public function isHealedByRegeneration(): bool
    {
        return (bool)$this->getValueFromRequest(self::HEALED_BY_REGENERATION);
    }

    public function isHealedByTreatment(): bool
    {
        return (bool)$this->getValueFromRequest(self::HEALED_BY_TREATMENT);
    }

    public function getSelectedAgility(): Agility
    {
        $selectedAgility = $this->getValueFromRequest(self::AGILITY);
        if ($selectedAgility === null) {
            return Agility::getIt(0);
        }

        return Agility::getIt($selectedAgility);
    }

    private function getSelectedAgilityWithReaction(): Agility
    {
        if ($this->isWithoutReaction()) {
            return Agility::getIt(-6);
        }

        return $this->getSelectedAgility();
    }

    public function isWithoutReaction(): bool
    {
        return (bool)$this->getValueFromRequest(self::WITHOUT_REACTION);
    }

    public function getSelectedAthletics(): Athletics
    {
        $athleticsValue = $this->getValueFromRequest(self::ATHLETICS);
        if ($athleticsValue === null) {
            return new Athletics(ProfessionFirstLevel::createFirstLevel(Commoner::getIt()));
        }
        $athletics = new Athletics(ProfessionFirstLevel::createFirstLevel(Commoner::getIt()));
        for ($athleticsRank = 1; $athleticsRank <= $athleticsValue; $athleticsRank++) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $athletics->increaseSkillRank(PhysicalSkillPoint::createFromFirstLevelSkillPointsFromBackground(
                ProfessionFirstLevel::createFirstLevel(Commoner::getIt()),
                SkillPointsFromBackground::getIt(
                    new PositiveIntegerObject(8),
                    Ancestry::getIt(new PositiveIntegerObject(8), Tables::getIt()),
                    Tables::getIt()
                ),
                Tables::getIt()
            ));
        }

        return $athletics;
    }

    public function getSelectedLuck(): ?int
    {
        $luck = $this->getValueFromRequest(self::ROLL_1D6);
        if ($luck) {
            return (int)$luck;
        }

        return null;
    }

    /**
     * @return array|HelmCode[]
     */
    public function getPossibleHelms(): array
    {
        return array_map(function (string $helmValue) {
            return HelmCode::getIt($helmValue);
        }, HelmCode::getPossibleValues());
    }

    public function isHelmSelected(HelmCode $helmCode): bool
    {
        return $this->getValueFromRequest(self::HEALED_BY_REGENERATION) === $helmCode->getValue();
    }

    public function isFallingFromHorseback(): bool
    {
        return $this->getValueFromRequest(self::FALLING_FROM) === self::HORSEBACK;
    }

    public function isFallingFromHeight(): bool
    {
        return $this->getValueFromRequest(self::FALLING_FROM) === self::HEIGHT;
    }

    public function getRidingAnimalsWithHeight(): array
    {
        $perHeight = [];
        foreach (RidingAnimalCode::getPossibleValues() as $ridingAnimalName) {
            switch ($ridingAnimalName) {
                case RidingAnimalCode::DONKEY :
                case RidingAnimalCode::MULE :
                case RidingAnimalCode::HINNY :
                case RidingAnimalCode::PONY :
                    $perHeight['1.1'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
                case RidingAnimalCode::YAK :
                case RidingAnimalCode::LAME :
                    $perHeight['1.3'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
                case RidingAnimalCode::COW :
                case RidingAnimalCode::BULL :
                    $perHeight['1.5'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
                case RidingAnimalCode::CAMEL :
                case RidingAnimalCode::HORSE :
                case RidingAnimalCode::DRAFT_HORSE :
                case RidingAnimalCode::UNICORN :
                    $perHeight['1.7'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
                case RidingAnimalCode::WAR_HORSE :
                    $perHeight['1.8'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
                case RidingAnimalCode::ELEPHANT :
                    $perHeight['3.1'][] = RidingAnimalCode::getIt($ridingAnimalName);
                    break;
            }
        }

        $withHeight = [];
        foreach ($perHeight as $height => $animalsWithSameHeight) {
            $localizedAnimalsWithSameHeight = array_map(function (RidingAnimalCode $ridingAnimalCode) {
                return $ridingAnimalCode->translateTo('cs');
            }, $animalsWithSameHeight);
            $withHeight[$height] = implode(', ', $localizedAnimalsWithSameHeight);
        }
        ksort($withHeight);

        return $withHeight;
    }

    public function isRidingAnimalSelected(float $heightInMeters): bool
    {
        return (float)$this->getValueFromRequest(self::HORSE_HEIGHT) === $heightInMeters;
    }

    /**
     * @return array|RidingAnimalMovementCode[]
     */
    public function getRidingAnimalMovements(): array
    {
        return array_map(function (string $movement) {
            return RidingAnimalMovementCode::getIt($movement);
        }, RidingAnimalMovementCode::getPossibleValuesWithoutJumping());
    }

    public function isRidingAnimalMovementSelected(RidingAnimalMovementCode $ridingAnimalMovementCode): bool
    {
        return $this->getValueFromRequest(self::RIDING_MOVEMENT) === $ridingAnimalMovementCode->getValue();
    }

    public function getBaseOfWoundsModifierByMovement(
        RidingAnimalMovementCode $ridingAnimalMovementCode,
        bool $horseIsJumping
    ): int
    {
        return Tables::getIt()->getWoundsOnFallFromHorseTable()
            ->getWoundsAdditionOnFallFromHorse(
                $ridingAnimalMovementCode,
                $horseIsJumping,
                Tables::getIt()->getWoundsTable()
            )->getValue();
    }

    public function getProtectionOfBodyArmor(BodyArmorCode $bodyArmorCode): int
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return Tables::getIt()->getBodyArmorsTable()->getProtectionOf($bodyArmorCode);
    }

    public function getProtectionOfHelm(HelmCode $helmCode): int
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return Tables::getIt()->getHelmsTable()->getProtectionOf($helmCode);
    }

    /**
     * @return array|LandingSurfaceCode[]
     */
    public function getSurfaces(): array
    {
        return array_map(function (string $surface) {
            return LandingSurfaceCode::getIt($surface);
        }, LandingSurfaceCode::getPossibleValues());
    }

    public function isSurfaceSelected(LandingSurfaceCode $landingSurfaceCode): bool
    {
        return $this->getSelectedLandingSurface()->getValue() === $landingSurfaceCode->getValue();
    }

    /**
     * Also agility is taken into account (for water).
     *
     * @param LandingSurfaceCode $landingSurfaceCode
     * @return IntegerWithHistory
     */
    public function getWoundsModifierBySurface(LandingSurfaceCode $landingSurfaceCode): IntegerWithHistory
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return Tables::getIt()->getLandingSurfacesTable()->getBaseOfWoundsModifier(
            $landingSurfaceCode,
            $this->getSelectedAgilityWithReaction(),
            new PositiveIntegerObject($this->getProtectionOfBodyArmor($this->getSelectedBodyArmor()))
        );
    }

    private function getSelectedBodyArmor(): BodyArmorCode
    {
        $bodyArmor = $this->getValueFromRequest(self::HEALING_POWER);
        if ($bodyArmor) {
            return BodyArmorCode::getIt($bodyArmor);
        }

        return BodyArmorCode::getIt(BodyArmorCode::WITHOUT_ARMOR);
    }

    private function getSelectedHelm(): HelmCode
    {
        $bodyArmor = $this->getValueFromRequest(self::HEALED_BY_REGENERATION);
        if ($bodyArmor) {
            return HelmCode::getIt($bodyArmor);
        }

        return HelmCode::getIt(HelmCode::WITHOUT_HELM);
    }

    public function getWoundsByFall(): ?int
    {
        if (!$this->isFallingFromHeight() && !$this->isFallingFromHorseback()) {
            return null;
        }
        if (!$this->getSelectedBodyWeight() || !$this->getSelected1d6Roll()) {
            return null;
        }
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $woundsFromFall = Tables::getIt()->getJumpsAndFallsTable()->getWoundsFromJumpOrFall(
            $this->isFallingFromHorseback()
                ? $this->getSelectedRidingAnimalHeight()
                : $this->getSelectedHeightOfFall(),
            BodyWeight::getIt($this->getSelectedBodyWeight()->getBonus()),
            $this->getSelectedItemsWeight(),
            $this->getSelected1d6Roll(),
            $this->isJumpControlled(),
            $this->getSelectedAgilityWithReaction(),
            $this->getSelectedAthletics(),
            $this->getSelectedLandingSurface(),
            new PositiveIntegerObject($this->getProtectionOfBodyArmor($this->getSelectedBodyArmor())),
            $this->isHitToHead(),
            new PositiveIntegerObject($this->getProtectionOfHelm($this->getSelectedHelm())),
            Tables::getIt()
        );
        if ($this->isFallingFromHorseback()) {
            $baseOfWoundsModifierByMovement = $this->getBaseOfWoundsModifierByMovement(
                $this->getSelectedRidingAnimalMovement(),
                $this->isHorseJumping()
            );
            if ($baseOfWoundsModifierByMovement !== 0) {
                $woundsFromFall = (new WoundsBonus(
                    $woundsFromFall->getBonus()->getValue() + $baseOfWoundsModifierByMovement,
                    Tables::getIt()->getWoundsTable()
                ))->getWounds();
            }
        }

        return $woundsFromFall->getValue();
    }

    private function getSelectedRidingAnimalHeight(): Distance
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new Distance(
            (float)$this->getValueFromRequest(self::HORSE_HEIGHT),
            DistanceUnitCode::METER,
            Tables::getIt()->getDistanceTable()
        );
    }

    public function getSelectedHeightOfFall(): Distance
    {
        $heightOfFall = $this->getValueFromRequest(self::HEIGHT_OF_FALL);
        if (!$heightOfFall) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return new Distance(0, DistanceUnitCode::METER, Tables::getIt()->getDistanceTable());
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new Distance($heightOfFall, DistanceUnitCode::METER, Tables::getIt()->getDistanceTable());
    }

    private function getSelectedRidingAnimalMovement(): RidingAnimalMovementCode
    {
        $selectedMovement = $this->getValueFromRequest(self::RIDING_MOVEMENT);
        if (!$selectedMovement) {
            return RidingAnimalMovementCode::getIt(RidingAnimalMovementCode::STILL);
        }

        return RidingAnimalMovementCode::getIt($selectedMovement);
    }

    public function getSelectedBodyWeight(): ?Weight
    {
        $weight = $this->getValueFromRequest(self::BODY_WEIGHT);
        if (!$weight) {
            return null;
        }

        return new Weight($weight, Weight::KG, Tables::getIt()->getWeightTable());
    }

    public function getSelectedItemsWeight(): ?Weight
    {
        $weight = $this->getValueFromRequest(self::ITEMS_WEIGHT);
        if (!$weight) {
            return null;
        }

        return new Weight($weight, Weight::KG, Tables::getIt()->getWeightTable());
    }

    public function getSelected1d6Roll(): ?Roll1d6
    {
        $roll = $this->getValueFromRequest(self::ROLL_1D6);
        if (!$roll) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return new Roll1d6(new Dice1d6Roll(1, 1));
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return new Roll1d6(new Dice1d6Roll($roll, 1));
    }

    public function isJumpControlled(): bool
    {
        return (bool)$this->getValueFromRequest(self::JUMP_IS_CONTROLLED);
    }

    public function isHorseJumping(): bool
    {
        return (bool)$this->getValueFromRequest(self::HORSE_IS_JUMPING);
    }

    public function getSelectedLandingSurface(): LandingSurfaceCode
    {
        $landingSurface = $this->getValueFromRequest(self::SURFACE);
        if (!$landingSurface) {
            return LandingSurfaceCode::getIt(LandingSurfaceCode::MEADOW);
        }

        return LandingSurfaceCode::getIt($landingSurface);
    }

    public function isHitToHead(): bool
    {
        return (bool)$this->getValueFromRequest(self::HEAD);
    }
}