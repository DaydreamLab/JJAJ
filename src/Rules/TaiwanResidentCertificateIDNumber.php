<?php

namespace DaydreamLab\JJAJ\Rules;

use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Contracts\Validation\Rule;

class TaiwanResidentCertificateIDNumber extends BaseRule implements Rule
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
        if(!preg_match("/^[A-Z]{1}[A-D]{1}[0-9]{8}$/", $value))
        {
            return false;
        }

        $num = array("A" => "10","B" => "11","C" => "12","D" => "13","E" => "14",
            "F" => "15","G" => "16","H" => "17","J" => "18","K" => "19","L" => "20",
            "M" => "21","N" => "22","P" => "23","Q" => "24","R" => "25","S" => "26",
            "T" => "27","U" => "28","V" => "29","X" => "30","Y" => "31","W" => "32",
            "Z" => "33","I" => "34","O" => "35");

        $sum = 0;

        $split_str  = str_split($value);
        $multiple   = [1, 9, 8, 7, 6, 5, 4, 3, 2, 1];
        $first      = $num[array_shift($split_str)];
        $first_1    = floor($first/10);
        $first_2    = $first % 10;
        $second     = $num[array_shift($split_str)] % 10;
        array_unshift($split_str, $first_1, $first_2, $second);

        for ($i = 0; $i < 10; $i++)
        {
            $sum += ((int)$split_str[$i] * (int)$multiple[$i]) % 10;
        }

        if($sum % 10 == 0) {
            return (int)$split_str[10] === 0;
        }
        else{
            return 10-($sum%10) === (int)$split_str[10];
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The taiwan resident certificate id number is not valid.';
    }
}
