<?php

namespace App\Enums;

enum RoleType: string
{
    case CLIENT = 'Client';
    case ACCOUNT_SPECIALIST = 'Account Specialist';
    case MARKETING = 'Marketing';
    case HUMAN_RESOURCE = 'Human Resource';
}