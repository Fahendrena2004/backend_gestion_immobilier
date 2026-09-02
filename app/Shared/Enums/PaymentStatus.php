<?php

namespace App\Shared\Enums;

enum PaymentStatus: string
{
    case DECLARE = 'declare';
    case VALIDE = 'valide';
    case REJETE = 'rejete';
}
