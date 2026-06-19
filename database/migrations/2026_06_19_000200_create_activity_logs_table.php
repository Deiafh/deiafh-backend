<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Actor. Kept (nulled) if the user is later deleted; name is snapshotted.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            // Snapshot: actor was a super-admin or hidden user → only super-admins may view.
            $table->boolean('concealed')->default(false);
            $table->string('action_key')->nullable();   // e.g. users.add
            $table->string('description');               // Arabic, human readable
            $table->string('method', 10);
            $table->string('url');
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('concealed');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
