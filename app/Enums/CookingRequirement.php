<?php

namespace App\Enums;

enum CookingRequirement: string
{
    case Ready = 'Готовый к употреблению';
    case SemiFinished = 'Полуфабрикат';
    case NeedsCooking = 'Требует приготовления';
}
