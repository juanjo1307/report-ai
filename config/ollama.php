<?php

return [
    'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'model_sql_generation' => env('OLLAMA_MODEL_SQL', 'codellama:latest '),
    'model_query_execution' => env('OLLAMA_MODEL_TEXT', 'llama3:latest'),
    'temperature' => env('OLLAMA_TEMPERATURE', 0.3),
    'top_p' => env('OLLAMA_TOP_P', 0.9)
];