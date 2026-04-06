<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Frozen = 'Замороженный';
    case Meat = 'Мясной';
    case Vegetables = 'Овощи';
    case Greens = 'Зелень';
    case Spices = 'Специи';
    case Cereals = 'Крупы';
    case Canned = 'Консервы';
    case Liquid = 'Жидкость';
    case Sweets = 'Сладости';
}
