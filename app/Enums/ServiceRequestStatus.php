<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta', self::InProgress => 'Em atendimento', self::Completed => 'Concluída'
        };
    }
}
