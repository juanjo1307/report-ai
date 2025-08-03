<?php

namespace App\Actions\DataAnalysis;

use Illuminate\Support\Facades\DB;

class ExecuteAnalysisQueryAction
{
    public function invoke(string $sqlQuery): array
    {
        $results = DB::select($sqlQuery);
        
        $resultsArray = array_map(function ($row) {
            return (array) $row;
        }, $results);
        
        return [
            'success' => true,
            'data' => $resultsArray,
            'row_count' => count($resultsArray)
        ];
    }
} 