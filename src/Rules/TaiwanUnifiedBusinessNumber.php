<?php

namespace DaydreamLab\JJAJ\Rules;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Contracts\Validation\Rule;

class TaiwanUnifiedBusinessNumber extends BaseRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!preg_match("/^[0-9]{8}$/", $value)) {
            return false;
        }

        $sum = 0;
        $ubn_str  = str_split($value);
        $multiple = [1, 2, 1, 2, 1, 2, 4, 1];
        $result = [];
        foreach ($ubn_str as $key => $ubn_digit) {
            $result[$key] = (int) $ubn_digit * $multiple[$key];
            $sum += floor($result[$key] / 10) + $result[$key] % 10;
        }

        if (($sum % 5 == 0) || ((($sum + 1) % 5 == 0) && $ubn_str[6] === '7')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The taiwan unified business number id not valid.';
    }
}
