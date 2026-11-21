<?php

namespace App\Domains\Accounts\Enums;

enum OtpPurpose: string
{
    case VerifyPhone = 'verify_phone';
    case ResetPassword = 'reset_password';
}
