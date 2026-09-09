<?php

namespace App\Shared\Enums;

enum PaymentMethod: string
{
    case MVOLA = 'mvola';
    case AIRTEL_MONEY = 'airtel_money';
    case ORANGE_MONEY = 'orange_money';
    case ESPECES = 'especes';
    case VIREMENT = 'virement';
}
