<?php

namespace App\Shared\Enums;

enum FactureStatus: string
{
    case IMPAYEE = 'impayee';
    case PAYEE = 'payee';
    case EN_RETARD = 'en_retard';
}
