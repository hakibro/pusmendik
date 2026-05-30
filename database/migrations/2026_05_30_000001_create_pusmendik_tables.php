<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('recommendation_handlers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_siswa_id')->index();
            $table->string('idyayasan')->index();
            $table->string('nama');
            $table->decimal('nominal_rekom', 14, 2);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->string('handled_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_handlers');
        Schema::dropIfExists('app_settings');
    }
};
