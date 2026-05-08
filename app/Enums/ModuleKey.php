<?php

namespace App\Enums;

enum ModuleKey: string
{
    case Rsvp            = 'rsvp';
    case Gifts           = 'gifts';
    case Songs           = 'songs';
    case Schedule        = 'schedule';
    case Story           = 'story';
    case DressCode       = 'dress_code';
    case Gallery         = 'gallery';
    case RomanticPhrases = 'romantic_phrases';
    case Attendance      = 'attendance';
    case Location        = 'location';

    public static function defaultOrder(): array
    {
        return [
            self::Rsvp->value,
            self::Location->value,
            self::Schedule->value,
            self::Story->value,
            self::Gifts->value,
            self::Songs->value,
            self::DressCode->value,
            self::RomanticPhrases->value,
            self::Gallery->value,
            self::Attendance->value,
        ];
    }
}
