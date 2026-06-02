<?php

namespace App\Enums;

enum RoleType: string
{
    case CLIENT = 'Client';
    case ACCOUNT_SPECIALIST = 'Account Specialist';
    case LEAD_ACCOUNT_SPECIALIST = 'Lead Account Specialist';
    case MARKETING = 'Marketing';
    case HUMAN_RESOURCE = 'Human Resource';
    case OPERATIONS = 'Operations';
    case LEAD_OPERATIONS = 'Lead Operations';
    case FINANCE = 'Finance';
    case IT = 'IT';
    case CLIENT_SUCCESS = 'Client Success';
    case LEAD_CLIENT_SUCCESS = 'Lead Client Success';
}