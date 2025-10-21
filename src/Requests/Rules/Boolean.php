<?php

namespace Aventus\Laraventus\Requests\Rules;

use Aventus\Laraventus\Helpers\Converter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

class Boolean implements ValidationRule, ValidatorAwareRule
{

    /**
     * The validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Set the current validator.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Converter::isBool($value)) {
            $fail('The :attribute must be uppercase.');
        } else {
            $this->validator->setData(array_merge($this->validator->getData(), [
                $attribute => Converter::toBool($value),
            ]));
        }
    }
}
