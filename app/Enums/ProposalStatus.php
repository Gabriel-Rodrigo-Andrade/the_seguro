<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Contracted = 'contracted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho', self::Submitted => 'Enviada', self::Approved => 'Aprovada',
            self::Rejected => 'Recusada', self::Contracted => 'Contratada',
        };
    }
}
