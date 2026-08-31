<?php

namespace App\Shared\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case PROPRIETAIRE = 'proprietaire';
    case LOCATAIRE = 'locataire';
}
