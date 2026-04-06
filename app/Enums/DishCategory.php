<?php

namespace App\Enums;

enum DishCategory: string
{
    case Dessert = 'Десерт';
    case First = 'Первое';
    case Second = 'Второе';
    case Drink = 'Напиток';
    case Salad = 'Салат';
    case Soup = 'Суп';
    case Snack = 'Перекус';

    public static function fromMacro(string $macro): ?self
    {
        return match ($macro) {
            '!десерт' => self::Dessert,
            '!первое' => self::First,
            '!второе' => self::Second,
            '!напиток' => self::Drink,
            '!салат' => self::Salad,
            '!суп' => self::Soup,
            '!перекус' => self::Snack,
            default => null,
        };
    }
}
