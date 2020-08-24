<?php

namespace App\Utils\Constraints;

class ErrorMessage
{
    const isEmail = 'This field is not a valid email';
    const isBoolean = 'This field must be a boolean';
    const isNumber = 'This field must be a number';
    const isNull = 'This field must be null';
    const isNotNull = 'This field must be not null';
    const isTrue = 'This field must be true';
    const isFalse = 'This field must be false';
    const isLength = 'This field must have length of {value}';
    const isNotEmpty = 'This field must be not empty';
    const isLessThan = 'This field must be less than {value}';
    const isLessThanOrEquals = 'This field must be less than or equals {value}';
    const isMoreThan = 'This field must be more than {value}';
    const isMoreThanOrEquals = 'This field must be more than or equals {value}';
    const isDateISO8601 = 'This field must be a date';
    const isDateTimeISO8601 = 'This field must be a datetime';
    const matches = 'This field is not valid';
    const isArray = 'This field must be an array';
    const isOneOf = 'This field must be one of {value}';
    const isObject = 'This field must be an object';
}
