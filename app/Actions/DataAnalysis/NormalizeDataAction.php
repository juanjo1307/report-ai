<?php

namespace App\Actions\DataAnalysis;

class NormalizeDataAction
{
    public function invoke(array $data, array $definition): array
    {
        $normalizedData = [];
        $columnMapping = [];
        
        $normalizeColumnNameAction = new NormalizeColumnNameAction();
        
        foreach ($definition as $columnName => $columnInfo) {
            $normalizedName = $normalizeColumnNameAction->invoke($columnName);
            $columnMapping[$columnName] = $normalizedName;
        }
        
        foreach ($data as $row) {
            $normalizedRow = [];
            foreach ($row as $originalColumnName => $value) {
                $normalizedColumnName = $columnMapping[$originalColumnName] ?? $this->normalizeColumnName($originalColumnName);
                $normalizedRow[$normalizedColumnName] = $value;
            }
            $normalizedData[] = $normalizedRow;
        }
        
        return $normalizedData;
    }

    private function normalizeColumnName(string $columnName): string
    {
        return strtolower(str_replace(' ', '_', $columnName));
    }
} 