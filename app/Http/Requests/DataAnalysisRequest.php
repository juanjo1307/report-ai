<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'definition' => 'array|nullable',
            'definition.*.description' => 'required|string|max:500',
            'definition.*.type' => 'required|string|in:INTEGER,VARCHAR,DATE,DATETIME,BOOLEAN,TEXT,FLOAT',
            'data' => 'required|array|min:1',
            'data.*' => 'array'
        ];
    }
}
