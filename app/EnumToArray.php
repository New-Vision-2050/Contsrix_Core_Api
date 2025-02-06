<?php

namespace App;

trait EnumToArray
{

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }


    public static function array(): array
    {
        $terms = [];

        foreach (self::cases() as $value) {
            $terms[] = ["key"=>self::lang($value->value),"value"=>$value->value];
        }
        return $terms ;
    }

}
