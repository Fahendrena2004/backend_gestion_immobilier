<?php

namespace App\Shared\Enums;

enum LogementStatus: string
{
    case DISPONIBLE = 'disponible';
    case RESERVE = 'reserve';
    case LOUE = 'loue';
    case INDISPONIBLE = 'indisponible';
}
