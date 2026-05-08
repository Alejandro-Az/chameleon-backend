<?php

namespace App\Enums;

enum EventType: string
{
    case Wedding      = 'wedding';
    case Quinceanera  = 'quinceanera';
    case Graduation   = 'graduation';
    case Birthday     = 'birthday';
    case Party        = 'party';
    case Other        = 'other';
}
