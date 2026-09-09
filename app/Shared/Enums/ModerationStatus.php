<?php

namespace App\Shared\Enums;

enum ModerationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVE = 'approuve';
    case SUSPENDU = 'suspendu';
    case SUPPRIME = 'supprime';
}
