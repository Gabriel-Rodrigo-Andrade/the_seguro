<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'cliente';
    case Broker = 'corretor';
    case Administrator = 'administrador';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
