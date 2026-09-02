<?php

namespace App\Shared\Enums;

enum VisiteStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case EFFECTUEE = 'effectuee';
    case ANNULEE = 'annulee';
}
