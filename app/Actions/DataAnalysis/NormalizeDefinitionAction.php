<?php

namespace App\Actions\DataAnalysis;

class NormalizeDefinitionAction
{
    public function invoke(array $definition): array
    {
        $normalizeColumnNameAction = new NormalizeColumnNameAction();
        
        $normalized = [];
        foreach ($definition as $columnName => $columnInfo) {
            $normalizedName = $normalizeColumnNameAction->invoke($columnName);
            $normalized[$normalizedName] = $columnInfo;
        }
        return $normalized;
    }
} 