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
    public static function local(): array
    {
        $arr=[];
        foreach (array_column(self::cases(), 'name') as $item)
        {
            array_push($arr,__('lookups.'.$item));
        }
        return $arr;
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

}
