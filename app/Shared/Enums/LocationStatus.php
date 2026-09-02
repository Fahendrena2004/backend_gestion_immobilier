<?php

namespace App\Shared\Enums;

enum LocationStatus: string
{
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case RENOUVELEE = 'renouvelee';
    case RESILIEE = 'resiliee';
}
