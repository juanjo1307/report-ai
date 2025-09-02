<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('query_analytics', function (Blueprint $table) {
            $table->id();
            
            $table->text('query_text');
            $table->text('sql_generated')->nullable();
            $table->text('natural_response')->nullable();
            $table->string('table_name');
            $table->unsignedBigInteger('user_id')->nullable();
            
            $table->integer('execution_time_ms')->nullable();
            $table->integer('sql_generation_time_ms')->nullable();
            $table->integer('natural_response_time_ms')->nullable();
            $table->integer('query_execution_time_ms')->nullable();
            
            $table->integer('results_count')->default(0);
            $table->boolean('sql_success')->default(true);
            $table->boolean('natural_response_success')->default(true);
            $table->text('error_message')->nullable();
            
            $table->string('sql_generation_model', 100)->nullable();
            $table->string('natural_response_model', 100)->nullable();
            $table->decimal('sql_generation_temperature', 3, 2)->nullable();
            $table->decimal('sql_generation_top_p', 3, 2)->nullable();
            $table->decimal('natural_response_temperature', 3, 2)->nullable();
            $table->decimal('natural_response_top_p', 3, 2)->nullable();
            
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('table_name');
            $table->index('created_at');
            $table->index('user_id');
            $table->index(['sql_success', 'natural_response_success']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('query_analytics');
    }
}; 