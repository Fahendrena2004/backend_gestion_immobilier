<?php

namespace App\Shared\Enums;

enum PaymentStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDE = 'valide';
    case REJETE = 'rejete';
}
