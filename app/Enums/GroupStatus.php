<?php

namespace App\Enums;

enum GroupStatus: string
{
    case Ready = 'ready';
    case Cooldown = 'cooldown';
    case NotStarted = 'not_started';
    case Completed = 'completed';
}
