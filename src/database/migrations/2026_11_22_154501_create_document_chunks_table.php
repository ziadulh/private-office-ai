<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->text('processing_error')->nullable()->after('status');
            $table->timestamp('processed_at')->nullable()->after('processing_error');
        });

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('page_number')->nullable();
            $table->longText('content');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->string('vector_id', 100)->nullable()->index();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['processing_error', 'processed_at']);
        });
    }
};
