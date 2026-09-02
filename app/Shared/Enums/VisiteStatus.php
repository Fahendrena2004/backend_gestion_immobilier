<?php

namespace App\Shared\Enums;

enum VisiteStatus: string
{
    case DEMANDEE = 'demandee';
    case PROPOSEE = 'proposee';
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';
    case REALISEE = 'realisee';
}
