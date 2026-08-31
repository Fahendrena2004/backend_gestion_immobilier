<?php

namespace App\Shared\Enums;

enum DemandeStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTEE = 'acceptee';
    case REFUSEE = 'refusee';
    case ANNULEE = 'annulee';
}
