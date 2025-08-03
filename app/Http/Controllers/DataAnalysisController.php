<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Models\AnalysisTableMetadata;
use App\Http\Requests\DataAnalysisRequest;
use App\Http\Requests\QueryRequest;

use App\Actions\DataAnalysis\NormalizeDataAction;
use App\Actions\DataAnalysis\NormalizeDefinitionAction;
use App\Actions\DataAnalysis\GenerateDefinitionAction;
use App\Actions\DataAnalysis\CreateUniqueTableNameAction;
use App\Actions\DataAnalysis\CreateAnalysisTableAction;
use App\Actions\DataAnalysis\InsertAnalysisDataAction;
use App\Actions\DataAnalysis\ExecuteAnalysisQueryAction;
use App\Services\OllamaGenerateSqlService;
use App\Services\OllamaGenerateNaturalResponseService;

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

    public function queryData(QueryRequest $request, NormalizeDefinitionAction $normalizeDefinitionAction, OllamaGenerateSqlService $ollamaGenerateSqlService, ExecuteAnalysisQueryAction $executeAnalysisQueryAction, OllamaGenerateNaturalResponseService $ollamaGenerateNaturalResponseService)
    {
        $validated = $request->validated();
        $tableName = $validated['table_name'];
        $queryText = $validated['query_text'];

        $metadata = AnalysisTableMetadata::where('table_name', $tableName)
                ->where('expires_at', '>', now())
                ->first();

        if (!$metadata) {
            return response()->json([
                'message' => 'Table not found or expired',
            ], Response::HTTP_NOT_FOUND);
        }

        $normalizedDefinition = $normalizeDefinitionAction->invoke($metadata->definition);
        $sqlResult = $ollamaGenerateSqlService->generateSql($queryText, $normalizedDefinition, $tableName);
        
        if (isset($sqlResult['error'])) {
            return response()->json([
                'message' => $sqlResult['error'],
                'details' => $sqlResult['details'] ?? null
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $sqlQuery = $sqlResult['sql_query'];

        $queryResults = $executeAnalysisQueryAction->invoke($sqlQuery);

        $naturalResponse = $ollamaGenerateNaturalResponseService->generateNaturalResponse($queryText, $metadata->definition, $queryResults['data']);
        
        if (!$naturalResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating response',
                'error' => $naturalResponse['response']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $queryText,
                'sql_generated' => $sqlQuery,
                'response' => $naturalResponse['response'],
                'results_count' => count($queryResults),
                'table_name' => $tableName,
                'expires_at' => $metadata->expires_at
            ]
        ]);
    }
}
