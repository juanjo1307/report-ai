<?php

namespace App\Actions\DataAnalysis;

class GenerateDefinitionAction
{
    public function invoke(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $firstItem = $data[0];
        
        $definition = [];
        foreach (array_keys($firstItem) as $columnName) {
            $definition[$columnName] = [
                'description' => $columnName,
                'type' => 'VARCHAR'
            ];
        }
        
        return $definition;
    }
} 