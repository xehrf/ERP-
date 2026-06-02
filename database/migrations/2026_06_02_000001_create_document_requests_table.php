<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('calendar_days');
            $table->unsignedInteger('working_days');
            $table->string('status')->default('draft');
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();
            $table->foreignId('director_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
