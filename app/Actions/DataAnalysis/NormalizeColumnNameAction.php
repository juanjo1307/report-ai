<?php

namespace App\Actions\DataAnalysis;

class NormalizeColumnNameAction
{
    public function invoke(string $columnName): string
    {
        return strtolower(str_replace(' ', '_', $columnName));
    }
} 