<?php

namespace App\Enums;

enum UserNotificationType: string
{
    case REPORT_READY =
        'REPORT_READY';

    case REPORT_FAILED =
        'REPORT_FAILED';

    case CREDIT_LIMIT_NEAR =
        'CREDIT_LIMIT_NEAR';

    case CREDIT_LIMIT_REACHED =
        'CREDIT_LIMIT_REACHED';

    case CREDIT_LIMIT_EXCEEDED_ATTEMPT =
        'CREDIT_LIMIT_EXCEEDED_ATTEMPT';

    case DAILY_DATA_REMINDER =
        'DAILY_DATA_REMINDER';
        
    case SYSTEM =
        'SYSTEM';
}
