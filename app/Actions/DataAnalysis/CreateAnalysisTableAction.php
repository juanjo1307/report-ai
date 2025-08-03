<?php

namespace App\Actions\DataAnalysis;
use Illuminate\Support\Facades\Schema;

class CreateAnalysisTableAction
{
    public function invoke(string $tableName, array $definition): void
    {
        Schema::connection('mariadb')->create($tableName, function ($table) use ($definition) {
            foreach ($definition as $columnName => $columnInfo) {
                $this->addColumnToTable($table, $columnName, $columnInfo['type']);
            }
            $table->timestamps();
        });
    }

    private function addColumnToTable($table, string $columnName, string $type): void
    {
        switch (strtoupper($type)) {
            case 'INTEGER':
                $table->integer($columnName)->nullable();
                break;
            case 'DATETIME':
                $table->dateTime($columnName)->nullable();
                break;
            case 'DATE':
                $table->date($columnName)->nullable();
                break;
            case 'BOOLEAN':
                $table->boolean($columnName)->nullable();
                break;
            case 'TEXT':
                $table->text($columnName)->nullable();
                break;
            case 'FLOAT':
                $table->float($columnName)->nullable();
                break;
            case 'VARCHAR':
                $table->string($columnName, 255)->nullable();
                break;
            default:
                $table->string($columnName, 255)->nullable();
        }
    }
} 