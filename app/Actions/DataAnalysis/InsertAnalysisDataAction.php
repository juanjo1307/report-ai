<?php

namespace App\Actions\DataAnalysis;
use Illuminate\Support\Facades\DB;

class InsertAnalysisDataAction
{
    public function invoke(string $tableName, array $data): int
    {
        $connection = DB::connection('mariadb');
        
        $insertData = [];
        foreach ($data as $row) {
            $processedRow = array_map(function($value) {
                return $value;
            }, $row);

            $insertData[] = array_merge($processedRow, [
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $chunks = array_chunk($insertData, 1000);
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            $connection->table($tableName)->insert($chunk);
            $totalInserted += count($chunk);
        }
        return $totalInserted;
    }
} 