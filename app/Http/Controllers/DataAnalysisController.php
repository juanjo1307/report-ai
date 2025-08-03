<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Models\AnalysisTableMetadata;
use App\Http\Requests\DataAnalysisRequest;

use App\Actions\DataAnalysis\NormalizeDataAction;
use App\Actions\DataAnalysis\NormalizeDefinitionAction;
use App\Actions\DataAnalysis\GenerateDefinitionAction;
use App\Actions\DataAnalysis\CreateUniqueTableNameAction;
use App\Actions\DataAnalysis\CreateAnalysisTableAction;
use App\Actions\DataAnalysis\InsertAnalysisDataAction;

class DataAnalysisController extends Controller
{
    public function inputData(
        DataAnalysisRequest $request, 
        CreateAnalysisTableAction $createAnalysisTableAction,
        CreateUniqueTableNameAction $createUniqueTableNameAction,
        GenerateDefinitionAction $generateDefinitionAction,
        InsertAnalysisDataAction $insertAnalysisDataAction,
        NormalizeDataAction $normalizeDataAction,
        NormalizeDefinitionAction $normalizeDefinitionAction, 
    ): JsonResponse
    {

        $validated = $request->validated();
        $originalData = $validated['data'];
        
        if (!isset($validated['definition'])) {
            $originalDefinition = $generateDefinitionAction->invoke($originalData);
        } else {
            $originalDefinition = $validated['definition'];
        }

        $definition = $normalizeDefinitionAction->invoke($originalDefinition);
        $data = $normalizeDataAction->invoke($originalData, $originalDefinition);
        $tableName = $createUniqueTableNameAction->invoke();
        $createAnalysisTableAction->invoke($tableName, $definition);
        $rowCount = $insertAnalysisDataAction->invoke($tableName, $data);
        
        $metadata = AnalysisTableMetadata::create([
            'table_name' => $tableName,
            'definition' => $definition,
            'row_count' => $rowCount,
        ]);

        return response()->json([
            'message' => 'Data inserted successfully',
            'data' => [
                'table_name' => $tableName,
                'metadata_id' => $metadata->id,
                'row_count' => $rowCount,
                'expires_at' => $metadata->expires_at
            ]
        ], Response::HTTP_CREATED);
    }
}
