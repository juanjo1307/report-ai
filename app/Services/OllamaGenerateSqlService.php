<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaGenerateSqlService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('ollama.base_url');
    }
    
    public function generateSql(string $queryText, array $definition, string $tableName): array
    {
        $prompt = $this->createSqlPrompt($queryText, $definition, $tableName);
        
        $payload = [
            'model' => config('ollama.model_sql_generation'),
            'prompt' => $prompt,
            'options' => [
                'temperature' => (float)config('ollama.temperature_sql'),
                'top_p' => (float)config('ollama.top_p_sql'),
                'stream' => false
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/api/generate', $payload);
        
        $responseBody = $response->body();
        $sqlQuery = $this->extractCompleteResponse($responseBody);
        
        if (!$this->validateSqlQuery($sqlQuery, $tableName)) {
            return [
                'error' => 'The generated SQL query is invalid or unsafe',
                'details' => "Generated SQL: {$sqlQuery}",
                'sql_query' => null
            ];
        }
        
        return [
            'sql_query' => $sqlQuery,
            'reasoning' => "Generated SQL for the question: {$queryText}"
        ];
    }
    
    private function createSqlPrompt(string $queryText, array $definition, string $tableName): string
    {
        $columnsDescription = [];
        foreach ($definition as $colName => $colInfo) {
            $desc = "- {$colName} ({$colInfo['type']}): {$colInfo['description']}";
            $columnsDescription[] = $desc;
        }
        
        $columnsText = implode("\n", $columnsDescription);
        
        $promptPath = __DIR__ . '/sql_generation_prompt.txt';
        
        if (!file_exists($promptPath)) {
            throw new \Exception("Prompt file not found: {$promptPath}");
        }
        
        $promptTemplate = file_get_contents($promptPath);
        
        return str_replace(
            ['{table_name}', '{columns_text}', '{query_text}'],
            [$tableName, $columnsText, $queryText],
            $promptTemplate
        );
    }
    
    private function extractCompleteResponse(string $responseBody): string
    {
        $lines = explode("\n", trim($responseBody));
        $completeResponse = '';
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $jsonData = json_decode($line, true);
            if ($jsonData && isset($jsonData['response'])) {
                $completeResponse .= $jsonData['response'];
            }
        }
        
        return $this->cleanSqlResponse($completeResponse);
    }
    
    private function cleanSqlResponse(string $response): string
    {
        $sqlQuery = trim($response);
        
        if (str_starts_with($sqlQuery, '```sql')) {
            $sqlQuery = str_replace(['```sql', '```'], '', $sqlQuery);
        } elseif (str_starts_with($sqlQuery, '```')) {
            $sqlQuery = str_replace('```', '', $sqlQuery);
        }
        
        $sqlQuery = preg_replace('/\s+/', ' ', trim($sqlQuery));
        
        return $sqlQuery;
    }
    
    private function validateSqlQuery(string $sqlQuery, string $tableName): bool
    {
        $sqlUpper = strtoupper(trim($sqlQuery));
        
        if (!str_starts_with($sqlUpper, 'SELECT')) {
            return false;
        }
        
        $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE', 'EXEC', 'EXECUTE'];
        foreach ($dangerousKeywords as $keyword) {
            if (str_contains($sqlUpper, $keyword)) {
                return false;
            }
        }
        
        if (!str_contains($sqlQuery, $tableName)) {
            return false;
        }
        
        return true;
    }
} 