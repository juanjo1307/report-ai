<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaGenerateNaturalResponseService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('ollama.base_url');
    }
    
    public function generateNaturalResponse(string $queryText, array $definition, array $queryResults): array
    {
        $prompt = $this->createResponsePrompt($queryText, $definition, $queryResults);
        
        $payload = [
            'model' => config('ollama.model_query_execution'),
            'prompt' => $prompt,
            'options' => [
                'temperature' => (float)config('ollama.temperature_natural_response'),
                'top_p' => (float)config('ollama.top_p_natural_response'),
                'stream' => false
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/api/generate', $payload);
        
        $responseBody = $response->body();
        $naturalResponse = $this->extractCompleteResponse($responseBody);
        $formattedResponse = $this->formatResponseForLaravel($naturalResponse);
        
        return [
            'success' => true,
            'response' => $formattedResponse
        ];
            
    }
    
    private function createResponsePrompt(string $queryText, array $definition, array $queryResults): string
    {
        $columnsDescription = [];
        foreach ($definition as $colName => $colInfo) {
            $desc = "- {$colName} ({$colInfo['type']}): {$colInfo['description']}";
            $columnsDescription[] = $desc;
        }
        
        $columnsText = implode("\n", $columnsDescription);
        
        if (empty($queryResults)) {
            $resultsText = "No results found.";
        } else {
            $resultsText = json_encode($queryResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        $promptPath = __DIR__ . '/natural_response_prompt.txt';
        
        if (!file_exists($promptPath)) {
            throw new \Exception("Prompt file not found: {$promptPath}");
        }
        
        $promptTemplate = file_get_contents($promptPath);
        
        return str_replace(
            ['{columnsText}', '{queryText}', '{resultsText}'],
            [$columnsText, $queryText, $resultsText],
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
        
        return trim($completeResponse);
    }
    
    private function formatResponseForLaravel(string $responseText): string
    {
        $responseText = trim($responseText);
        
        $responseText = str_replace('\\', '\\\\', $responseText);
        $responseText = str_replace('"', '\\"', $responseText); 
        $responseText = str_replace(["\n", "\r", "\t"], ' ', $responseText);
        
        $responseText = preg_replace('/[\x00-\x1F\x7F]/', '', $responseText);
        
        if (strlen($responseText) > 2000) {
            $responseText = substr($responseText, 0, 1997) . "...";
        }
        
        return $responseText;
    }
} 