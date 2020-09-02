<?php

namespace App\Utils;

use App\Utils\Entity\Entity;

class Validator
{
    /**
     * 
     * @param object $object
     * @param Entity $entity
     * @throws ValidationException
     */
    public static function validate($object, Entity $entity)
    {
        $columns = EntityManager::getColumnsNotRefWithProperties($entity);
        $errors = [];
        $TypeField = '\App\Utils\Constraints\TypeField';
        $Constraints = '\App\Utils\Constraints\Constraints';
        $ErrorMessage = '\App\Utils\Constraints\ErrorMessage';

        foreach ($columns as $key => $column) {
            if (
                $column->required &&
                (!array_key_exists($key, $object) || $object[$key] === null)
            ) {
                $errors[] = [
                    "field" => $key,
                    "message" => 'This field is required',
                ];
            } elseif (array_key_exists($key, $object) && $object[$key] !== null) {
                $validations = $column->validation;
                if (count($validations) > 0) {
                    foreach ($validations as $validation) {
                        // If field's type is invalid
                        if (gettype($validation) === 'object') {
                            if (
                                constant("$TypeField::{$validation->check}") &&
                                gettype($object[$key]) !==
                                    constant("$TypeField::{$validation->check}")
                            ) {
                                $errors[] = [
                                    'field' => $key,
                                    'message' => "Invalid type, must be {${constant(
                                        "$TypeField::{$validation->check}"
                                    )}}",
                                ];
                            } else {
                                if ($validation->value) {
                                    $test = function (...$args) use (
                                        $Constraints,
                                        $validation
                                    ) {
                                        return call_user_func_array(
                                            "$Constraints::{$validation->check}",
                                            $args
                                        );
                                    };

                                    // Apply the verification
                                    if (
                                        method_exists(
                                            $Constraints,
                                            $validation->check
                                        ) &&
                                        !$test(
                                            $object[$key],
                                            $validation->value
                                        )
                                    ) {
                                        $errors[] = [
                                            'field' => $key,
                                            // Verify if there are a custom message
                                            'message' => property_exists(
                                                $validation,
                                                'message'
                                            )
                                                ? $validation->message
                                                : str_replace(
                                                    '{value}',
                                                    $validation->value,
                                                    constant(
                                                        "$ErrorMessage::{$validation->check}"
                                                    )
                                                ),
                                        ];
                                    }
                                } else {
                                    $test = function (...$args) use (
                                        $Constraints,
                                        $validation
                                    ) {
                                        return call_user_func_array(
                                            "$Constraints::{$validation->check}",
                                            $args
                                        );
                                    };
                                    // Apply the verification
                                    if (
                                        method_exists(
                                            $Constraints,
                                            $validation->check
                                        ) &&
                                        !$test($object[$key])
                                    ) {
                                        $errors[] = [
                                            'field' => $key,
                                            // Verify if there are a custom message
                                            'message' => property_exists(
                                                $validation,
                                                'message'
                                            )
                                                ? $validation->message
                                                : str_replace(
                                                    '{value}',
                                                    $validation->value,
                                                    constant(
                                                        "$ErrorMessage::{$validation->check}"
                                                    )
                                                ),
                                        ];
                                    }
                                }
                            }
                        } else {
                            if (
                                constant("$TypeField::{$validation}") &&
                                gettype($object[$key]) !==
                                    constant("$TypeField::{$validation}")
                            ) {
                                $errors[] = [
                                    'field' => $key,
                                    'message' => "Invalid type, must be {${constant(
                                        "$TypeField::{$validation->check}"
                                    )}}",
                                ];
                            } else {
                                $test = function (...$args) use (
                                    $Constraints,
                                    $validation
                                ) {
                                    return call_user_func_array(
                                        "$Constraints::{$validation->check}",
                                        $args
                                    );
                                };

                                if (
                                    method_exists(
                                        $Constraints,
                                        $validation->check
                                    ) &&
                                    !$test($object[$key])
                                ) {
                                    $errors[] = [
                                        'field' => $key,
                                        'message' => constant(
                                            "$ErrorMessage::{$validation->check}"
                                        ),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
