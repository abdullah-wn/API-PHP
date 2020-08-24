<?php
namespace App\Utils\Constraints;

use App\Utils\RegExp;
use DateTime;

class Constraints
{
    /**
     *
     * @param mixed $input
     */
    public static function isEmail($input)
    {
        return (new RegExp(
            '^[a-zA-Z0-9.!#$%&\'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$'
        ))->test($input);
    }

    /**
     *
     * @param mixed $input
     */
    public static function isBoolean($input)
    {
        return is_bool($input);
    }

    /**
     *
     * @param mixed $input
     */
    public static function isNumber($input)
    {
        return is_int($input) || is_double($input);
    }

    /**
     *
     * @param mixed $input
     */
    public static function isNull($input)
    {
        return $input === null;
    }

    /**
     *
     * @param mixed $input
     */
    public static function isNotNull($input)
    {
        return $input !== null;
    }

    /**
     *
     * @param mixed $input
     */
    public static function isTrue($input)
    {
        return $input === true;
    }

    /**
     *
     * @param mixed $input
     */
    public static function isFalse($input)
    {
        return $input === false;
    }

    /**
     *
     * @param mixed $input
     * @param int $length
     */
    public static function isLength($input, $length)
    {
        switch (gettype($input)) {
            case 'string':
                return strlen($input);
                break;
            case 'array':
                return count($input);
                break;
            default:
                throw new \Exception('Invalid type');
        }
    }

    /**
     *
     * @param mixed $input
     * @param mixed[] $values
     */
    public static function isOneOf($input, $values)
    {
        return array_reduce($values, function ($isOneOf, $value) use ($input) {
            return $isOneOf || $value === $input;
        });
    }

    /**
     *
     * @param mixed $input
     */
    public static function isNotEmpty($input)
    {
        return strlen(trim($input)) > 0;
    }

    /**
     *
     * @param mixed $input
     * @param mixed $max
     */
    public static function isLessThan($input, $max)
    {
        if (
            gettype($input) === 'string' &&
            (self::isDateISO8601($input) || self::isDateTimeISO8601($input)) &&
            (self::isDateISO8601($max) || self::isDateTimeISO8601($max))
        ) {
            $input = new DateTime($input);
            $max = new DateTime($max);

            return $input->getTimestamp() < $max->getTimestamp();
        } elseif (gettype($input) === 'string') {
            return strlen($input) < $max;
        }
        return $input < $max;
    }

    /**
     *
     * @param mixed $input
     * @param mixed $max
     */
    public static function isLessThanOrEquals($input, $max)
    {
        if (
            gettype($input) === 'string' &&
            (self::isDateISO8601($input) || self::isDateTimeISO8601($input)) &&
            (self::isDateISO8601($max) || self::isDateTimeISO8601($max))
        ) {
            $input = new DateTime($input);
            $max = new DateTime($max);

            return $input->getTimestamp() <= $max->getTimestamp();
        } elseif (gettype($input) === 'string') {
            return strlen($input) <= $max;
        }
        return $input < $max;
    }

    /**
     *
     * @param mixed $input
     * @param mixed $min
     */
    public static function isMoreThan($input, $min)
    {
        if (
            gettype($input) === 'string' &&
            (self::isDateISO8601($input) || self::isDateTimeISO8601($input)) &&
            (self::isDateISO8601($min) || self::isDateTimeISO8601($min))
        ) {
            $input = new DateTime($input);
            $min = new DateTime($min);

            return $input->getTimestamp() > $min->getTimestamp();
        } elseif (gettype($input) === 'string') {
            return strlen($input) > $min;
        }
        return $input > $min;
    }

    /**
     *
     * @param mixed $input
     * @param mixed $min
     */
    public static function isMoreThanOrEquals($input, $min)
    {
        if (
            gettype($input) === 'string' &&
            (self::isDateISO8601($input) || self::isDateTimeISO8601($input)) &&
            (self::isDateISO8601($min) || self::isDateTimeISO8601($min))
        ) {
            $input = new DateTime($input);
            $min = new DateTime($min);

            return $input->getTimestamp() >= $min->getTimestamp();
        } elseif (gettype($input) === 'string') {
            return strlen($input) >= $min;
        }
        return $input >= $min;
    }

    /**
     *
     * @param mixed $input
     */
    public static function isDateISO8601($input)
    {
        return (new RegExp('^\d{4}-\d{2}-\d{2}$'))->test($input) &&
            !self::isNaN((new DateTime($input))->getTimestamp());
    }

    /**
     *
     * @param mixed $input
     */
    public static function isDateTimeISO8601($input)
    {
        return (new RegExp(
            '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{3}Z?)?)?'
        ))->test($input) &&
            !self::isNaN((new DateTime($input))->getTimestamp());
    }

    /**
     *
     * @param RegExp $regex
     * @param string $input
     */
    public static function matches($input, $regex)
    {
        return (new RegExp($regex))->test($input);
    }

    /**
     *
     * @param mixed $input
     */
    public static function isRequired($input)
    {
        return !isset($input) && $input !== null;
    }

    /**
     *
     * @param mixed $input
     */
    public static function isArray($input)
    {
        return is_array($input);
    }

    /**
     *
     * @param mixed $input
     */
    public static function isObject($input)
    {
        return is_object($input);
    }
}
