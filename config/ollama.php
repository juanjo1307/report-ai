<?php

return [
    'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'model_sql_generation' => env('OLLAMA_MODEL_SQL', 'codellama:latest '),
    'model_query_execution' => env('OLLAMA_MODEL_TEXT', 'llama3:latest'),
    'temperature_sql' => env('OLLAMA_TEMPERATURE_SQL', 0.3),
    'top_p_sql' => env('OLLAMA_TOP_P_SQL', 0.9),
    'temperature_natural_response' => env('OLLAMA_TEMPERATURE_NATURAL_RESPONSE', 0.3),
    'top_p_natural_response' => env('OLLAMA_TOP_P_NATURAL_RESPONSE', 0.9)
];