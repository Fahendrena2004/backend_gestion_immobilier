<?php

namespace App\Shared\Enums;

enum LogementStatus: string
{
    case DISPONIBLE = 'disponible';
    case OCCUPE = 'occupe';
    case EN_ATTENTE = 'en_attente';
    case DESACTIVE = 'desactive';
}
