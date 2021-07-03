<?php namespace EugeneEgr\PeriodicNumber;

use http\Exception\InvalidArgumentException;

class PeriodicNumber
{
    public const SYSTEM_10 = '0123456789';
    private const PERIODIC_NUMBER_PATTERN = '^(-|\+)?([%1$s]+)(?:\.([%1$s]+)(?:\[([%1$s]+)\])?)?(?:E((?:-|\+)[%1$s]+))?$';

    /** @var int */
    private $system;
    /** @var bool */
    private $isPositive;
    /** @var int[] */
    private $numbers;
    /** @var int */
    private $shift;
    /** @var int[] */
    private $period;

    private function __construct(
        int $system = PHP_INT_MAX,
        ?bool $isPositive = null,
        array $numbers = [],
        int $shift = 0,
        array $period = []
    ) {
        $this->system = $system;
        $this->isPositive = $isPositive;
        $this->numbers = $numbers;
        $this->shift = $shift;
        $this->period = $period;
    }

    public static function ofInt(int $value, int $system = PHP_INT_MAX): self
    {
        $isPositive = self::compareWitZero($value);
        $integer = [];

        if ($value < 0) {//PHP_INT_MIN
            $integer[] = - $value % $system;
            $value = - (int) ($value / $system);
        }

        while ($value > $system) {
            $integer[] = $value % $system;
            $value = (int) ($value / $system);
        }

        if ($value > 0) {
            array_unshift($integer, $value);
        }

        return new static($system, $isPositive, $integer);
    }

    public function toInt(): int
    {
        if (count($this->numbers) <= -$this->shift) {
            return 0;
        }

        $result = 0;
        $sign = $this->isPositive ? 1 : -1;

        for ($number = max(0, -$this->shift); $number < count($this->numbers); $number++) {
            $result += $sign * $this->numbers[$number] * $this->system ^ ($number + $this->shift);
        }

        if (is_float($result)) {
            throw new \Exception();
        }

        return $result;
    }

    public static function ofFloat(float $value, int $system = PHP_INT_MAX): self
    {
        return static::ofString(self::floatToString($value), $system);
    }

    public static function ofString(string $value, int $system = PHP_INT_MAX, string $format = self::SYSTEM_10): self
    {
        $format = new StringObject($format);
        $pattern = new RegularExpression(self::PERIODIC_NUMBER_PATTERN, 0, [$format]);
        $check = $pattern->match($value, $matches);

        if (!$check) {
            throw new InvalidArgumentException();
        }

        /**
         * @var StringObject $stringFractional
         * @var StringObject $stringPositive
         * @var StringObject $stringPeriod
         */
        [$value, $stringPositive, $stringInteger, $stringFractional, $stringPeriod, $shiftString] = $matches;
        $shift = $shiftString === '' ? 0 : static::ofString($shiftString, $system, $format)->toInt();

        if ($stringFractional->isEmpty()) {
            $shift -= $stringFractional->getLength();
        }

        [$period, $periodShift] = self::translateSystem(
            static::stringToArray($stringPeriod, $format),
            $format->getLength(),
            0,
            $system
        );
        $period = array_merge(array_fill(0, $periodShift, 0), $period);
        [$numbers, $newShift] = self::translateSystem(
            static::stringToArray(new StringObject($stringInteger . $stringFractional), $format),
            $format->getLength(),
            max(0, $shift),
            $system
        );

        $result = new static($system, !$stringPositive->isEqual('-'), $numbers, $newShift, $period);

        return $shift < 0 ? $result->divide(new static($format->getLength(), true, [1], -$shift)) : $result;
    }

    public function isPositive(): ?bool
    {
        return $this->isPositive;
    }

    public function minus(self $value): self
    {

    }

    public function divide(self $value): self
    {
    }

    public function setSystem(int $system): self
    {
        if ($system === $this->system) {
            return $this;
        }

        $result = new static($system, $this->isPositive, ...$this->translateSystem(array_merge(
            $this->period,
            $this->numbers
        ), $this->system, max(0, $this->shift), $system));

        if (count($this->period)) {
            $result = $result->minus(new static(
                $system,
                $this->isPositive,
                ...$this->translateSystem($this->period, $this->system, max(0, $this->shift), $system)
            ));
        }

        if ($this->shift < 0 || count($this->period)) {
            $result = $result->divide(new static(
                $system,
                true,
                ...$this->translateSystem(array_fill(
                    0,
                    max(1, count($this->period)),
                    count($this->period) ? $this->system - 1 : 1
                ), $this->system, max(0, -$this->shift), $system)
            ));
        }

        return $result;
    }

    public function getSystem(): int
    {
        return $this->system;
    }

    private static function floatToString(float $value): StringObject
    {
        $currentLocale = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'C');
        $result = new StringObject((string) $value);
        setlocale(LC_NUMERIC, $currentLocale);

        return $result;
    }

    private static function compareWitZero(int $value): ?bool
    {
        if ($value > 0) {
            return true;
        }

        if ($value < 0) {
            return false;
        }

        return null;
    }

    private static function stringToArray(StringObject $value, StringObject $format): array
    {
        $result = [];

        foreach ($value as $number) {
            $result[] = $format->getPosition($number);
        }

        return $result;
    }

    private static function translateSystem(
        array $numbers,
        int $fromSystem,
        int $fromShift,
        int $toSystem
    ): array {
        $toShift = 0;
        $result = [];

        while (count($numbers)) {
            $newNumbers = [];
            $newValue = 0;

            for ($number = count($numbers) - 1; $number >= -$fromShift; $number--) {
                $nextValue = $newValue * $fromSystem + ($numbers[$number] ?? 0);

                if ($nextValue >= $toSystem) {
                    if (is_float($nextValue)) {
                        $dSystem = intdiv($fromSystem, $toSystem);
                        $oSystem = ($fromSystem % $toSystem);
                        $dValue = intdiv($numbers[$number] ?? 0, $toSystem);
                        $oValue = ($numbers[$number] ?? 0) % $toSystem;
                        array_unshift(
                            $newNumbers,
                            $dSystem * $newValue + $dValue
                                + intdiv($oSystem * $newValue + $oValue, $toSystem)
                        );
                        $nextValue = ($oSystem * $newValue + $oValue) % $toSystem;
                    } else {
                        array_unshift($newNumbers, intdiv($nextValue, $toSystem));
                        $nextValue = $nextValue % $toSystem;
                    }
                }

                $newValue = $nextValue;
            }

            $newValue === 0 && !count($result)
                ? $toShift++
                : $result[] = $newValue;
            $numbers = $newNumbers;
        }

        return [$result, $toShift];
    }
}