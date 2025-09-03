# Report AI - Análisis de Datos con IA

Una aplicación Laravel que utiliza inteligencia artificial para analizar datos mediante consultas en lenguaje natural. La aplicación convierte preguntas en español a consultas SQL y genera respuestas comprensibles usando modelos de Ollama.

## Características

- **Análisis de datos con IA**: Sube datos CSV/json y haz preguntas en lenguaje natural
- **Generación automática de SQL**: Convierte preguntas a consultas SQL usando modelos de IA
- **Respuestas en español**: Genera respuestas comprensibles en español estándar
- **Interfaz moderna**: Construida con Laravel, Vue.js 3, Inertia.js y Tailwind CSS
- **Base de datos temporal**: Los datos se almacenan temporalmente para análisis

## Requisitos del Sistema

- PHP 8.2 o superior
- Composer
- Node.js 18+ y npm
- Ollama instalado y ejecutándose
- Modelos de IA: Qwen2.5 y Llama3

## Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/juanjo1307/report-ai
cd report-ai
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Instalar Dependencias de Node.js

```bash
npm install
```

### 4. Configurar Variables de Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` y configura las siguientes variables relacionadas con Ollama:

```env
# Configuración de Ollama
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL_SQL=qwen2.5:latest
OLLAMA_MODEL_TEXT=llama3:latest
OLLAMA_TEMPERATURE_SQL=0.3
OLLAMA_TOP_P_SQL=0.9
OLLAMA_TEMPERATURE_NATURAL_RESPONSE=0.3
OLLAMA_TOP_P_NATURAL_RESPONSE=0.9
```

### 5. Configurar Base de Datos
```
php artisan migrate
```

## Instalación de Ollama

### macOS

```bash
# Instalar usando Homebrew
brew install ollama

# O descargar desde https://ollama.ai/download
```

### Linux

```bash
# Instalar usando el script oficial
curl -fsSL https://ollama.ai/install.sh | sh
```

### Windows

Descarga el instalador desde [https://ollama.ai/download](https://ollama.ai/download)

## Configuración de Modelos de IA

### 1. Iniciar Ollama

```bash
ollama serve
```

### 2. Descargar Modelos

En una nueva terminal, descarga los modelos necesarios:

```bash
# Modelo para generación de SQL (Qwen2.5)
ollama pull qwen2.5:latest

# Modelo para respuestas naturales (Llama3)
ollama pull llama3:latest
```

### 3. Verificar Modelos Instalados

```bash
ollama list
```

Deberías ver algo similar a:

```
NAME            ID              SIZE    MODIFIED
qwen2.5:latest  abc123def456    4.7GB   2 hours ago
llama3:latest   def456ghi789    4.7GB   2 hours ago
```

## Ejecutar la Aplicación

### Opción 1: Comando de Desarrollo (Recomendado)

```bash
composer run dev
```

Este comando ejecuta simultáneamente:
- Servidor Laravel (puerto 8000)
- Cola de trabajos
- Logs en tiempo real
- Vite para assets

### Opción 2: Comandos Separados

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Cola de trabajos
php artisan queue:work

# Terminal 3: Assets de desarrollo
npm run dev
```

## Uso de la Aplicación
1. Por el momento usando postman.
   - endpoint: http://localhost:8000/api/data-analysis/input-data
   - payload: {"data": [ coloca aqui tu json ]}
   - endpoint: http://localhost:8000/api/data-analysis/query
   - payload: 
      {
         "table_name": "el nombre de la tabla que se genero en el paso anterior",
         "query_text": "aqui tu pregunta"
      }

## Estructura del Proyecto

```
app/
├── Actions/DataAnalysis/     # Acciones para procesamiento de datos
├── Http/Controllers/         # Controladores de la aplicación
├── Models/                   # Modelos de Eloquent
├── Services/                 # Servicios para integración con Ollama
└── Services/                 # Prompts para los modelos de IA

config/
└── ollama.php               # Configuración de Ollama

resources/
├── js/                      # Frontend Vue.js
└── views/                   # Vistas Blade

routes/
├── web.php                  # Rutas web
└── api.php                  # Rutas API
```

## Configuración Avanzada

### Personalizar Modelos

Puedes cambiar los modelos en el archivo `.env`:

### Ajustar Parámetros de IA

```env
# Temperatura más baja = respuestas más determinísticas
OLLAMA_TEMPERATURE_SQL=0.1

# Temperatura más alta = respuestas más creativas
OLLAMA_TEMPERATURE_NATURAL_RESPONSE=0.7
```

## Solución de Problemas

### Ollama no responde

1. Verifica que Ollama esté ejecutándose:
   ```bash
   curl http://localhost:11434/api/tags
   ```

2. Reinicia Ollama:
   ```bash
   ollama serve
   ```

### Modelos no encontrados

1. Verifica que los modelos estén instalados:
   ```bash
   ollama list
   ```

2. Reinstala los modelos:
   ```bash
   ollama pull qwen2.5:latest
   ollama pull llama3:latest
   ```
   
## Tecnologías Utilizadas

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS
- **IA**: Ollama con modelos Qwen2.5 y Llama3
- **Base de datos**: MariaDB (configurable)
- **Herramientas**: Vite, TypeScript, ESLint, Prettier
