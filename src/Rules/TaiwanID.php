<?php

namespace DaydreamLab\JJAJ\Rules;

use Illuminate\Contracts\Validation\Rule;

class TaiwanID extends BaseRule implements Rule
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
        $id=strtoupper($value);
        $d0=strlen($id);
        if ($d0 <= 0) {return false;}
        if ($d0 > 10) {return false;}
        if ($d0 < 10 && $d0 > 0) {return false;}
        $d1=substr($id,0,1);
        $ds=ord($d1);
        if ($ds > 90 || $ds < 65) {return false;}
        $d2=substr($id,1,1);
        if($d2!="1" && $d2!="2") {return false;}
        for ($i=1;$i<10;$i++) {
            $d3=substr($id,$i,1);
            $ds=ord($d3);
            if ($ds > 57 || $ds < 48) {
                $n=$i+1;
                return false;
                break;
            }
        }
        $num=array("A" => "10","B" => "11","C" => "12","D" => "13","E" => "14",
            "F" => "15","G" => "16","H" => "17","J" => "18","K" => "19","L" => "20",
            "M" => "21","N" => "22","P" => "23","Q" => "24","R" => "25","S" => "26",
            "T" => "27","U" => "28","V" => "29","X" => "30","Y" => "31","W" => "32",
            "Z" => "33","I" => "34","O" => "35");
        $n1=substr($num[$d1],0,1)+(substr($num[$d1],1,1)*9);
        $n2=0;
        for ($j=1;$j<9;$j++) {
            $d4=substr($id,$j,1);
            $n2=$n2+$d4*(9-$j);
        }
        $n3=$n1+$n2+substr($id,9,1);
        if(($n3 % 10)!=0) {return false;}
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be Taiwan ID.';
    }
}
