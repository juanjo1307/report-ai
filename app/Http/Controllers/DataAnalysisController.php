<?php

namespace App\Http\Controllers;

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
use App\Actions\DataAnalysis\SaveQueryAnalyticsAction;
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

    public function queryData(QueryRequest $request, NormalizeDefinitionAction $normalizeDefinitionAction, OllamaGenerateSqlService $ollamaGenerateSqlService, ExecuteAnalysisQueryAction $executeAnalysisQueryAction, OllamaGenerateNaturalResponseService $ollamaGenerateNaturalResponseService, SaveQueryAnalyticsAction $saveQueryAnalyticsAction)
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

        $startTime = microtime(true);
        
        $sqlStartTime = microtime(true);
        $normalizedDefinition = $normalizeDefinitionAction->invoke($metadata->definition);
        $sqlResult = $ollamaGenerateSqlService->generateSql($queryText, $normalizedDefinition, $tableName);
        $sqlGenerationTime = (microtime(true) - $sqlStartTime) * 1000; 
        
        if (isset($sqlResult['error'])) {
            $this->saveAnalytics($saveQueryAnalyticsAction, [
                'query_text' => $queryText,
                'table_name' => $tableName,
                'sql_success' => false,
                'natural_response_success' => false,
                'error_message' => $sqlResult['error'],
                'sql_generation_model' => config('ollama.model_sql_generation'),
                'sql_generation_temperature' => config('ollama.temperature_sql'),
                'sql_generation_top_p' => config('ollama.top_p_sql'),
                'expires_at' => $metadata->expires_at
            ]);
            
            return response()->json([
                'message' => $sqlResult['error'],
                'details' => $sqlResult['details'] ?? null
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $sqlQuery = $sqlResult['sql_query'];

        $queryStartTime = microtime(true);
        $queryResults = $executeAnalysisQueryAction->invoke($sqlQuery);
        $queryExecutionTime = (microtime(true) - $queryStartTime) * 1000;

        $naturalStartTime = microtime(true);
        $naturalResponse = $ollamaGenerateNaturalResponseService->generateNaturalResponse($queryText, $metadata->definition, $queryResults['data']);
        $naturalResponseTime = (microtime(true) - $naturalStartTime) * 1000;
        
        $totalExecutionTime = (microtime(true) - $startTime) * 1000;
        
        if (!$naturalResponse['success']) {
            $this->saveAnalytics($saveQueryAnalyticsAction, [
                'query_text' => $queryText,
                'sql_generated' => $sqlQuery,
                'table_name' => $tableName,
                'sql_success' => true,
                'natural_response_success' => false,
                'error_message' => $naturalResponse['response'],
                'sql_generation_model' => config('ollama.model_sql_generation'),
                'sql_generation_temperature' => config('ollama.temperature_sql'),
                'sql_generation_top_p' => config('ollama.top_p_sql'),
                'natural_response_model' => config('ollama.model_query_execution'),
                'natural_response_temperature' => config('ollama.temperature_natural_response'),
                'natural_response_top_p' => config('ollama.top_p_natural_response'),
                'expires_at' => $metadata->expires_at,
                'execution_time_ms' => (int)$totalExecutionTime,
                'sql_generation_time_ms' => (int)$sqlGenerationTime,
                'query_execution_time_ms' => (int)$queryExecutionTime,
                'natural_response_time_ms' => (int)$naturalResponseTime,
                'results_count' => count($queryResults['data'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating response',
                'error' => $naturalResponse['response']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->saveAnalytics($saveQueryAnalyticsAction, [
            'query_text' => $queryText,
            'sql_generated' => $sqlQuery,
            'natural_response' => $naturalResponse['response'],
            'table_name' => $tableName,
            'sql_success' => true,
            'natural_response_success' => true,
            'sql_generation_model' => config('ollama.model_sql_generation'),
            'sql_generation_temperature' => config('ollama.temperature_sql'),
            'sql_generation_top_p' => config('ollama.top_p_sql'),
            'natural_response_model' => config('ollama.model_query_execution'),
            'natural_response_temperature' => config('ollama.temperature_natural_response'),
            'natural_response_top_p' => config('ollama.top_p_natural_response'),
            'expires_at' => $metadata->expires_at,
            'execution_time_ms' => (int)$totalExecutionTime,
            'sql_generation_time_ms' => (int)$sqlGenerationTime,
            'query_execution_time_ms' => (int)$queryExecutionTime,
            'natural_response_time_ms' => (int)$naturalResponseTime,
            'results_count' => count($queryResults['data'])
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $queryText,
                'sql_generated' => $sqlQuery,
                'response' => $naturalResponse['response'],
                'results_count' => count($queryResults['data']),
                'table_name' => $tableName,
                'expires_at' => $metadata->expires_at
            ]
        ]);
    }
    
    private function saveAnalytics(SaveQueryAnalyticsAction $saveQueryAnalyticsAction, array $analyticsData): void
    {
        $saveQueryAnalyticsAction->invoke($analyticsData);
    }
}
