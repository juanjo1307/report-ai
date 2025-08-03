<?php

namespace App\Actions\DataAnalysis;

class CreateUniqueTableNameAction
{
    public function invoke(): string
    {
        return date('Y_m_d_His') . '_analysis_' . uniqid();
    }
} 