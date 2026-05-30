<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_handlers', function (Blueprint $table) {
            $table->json('tunggakan')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_handlers', function (Blueprint $table) {
            $table->dropColumn('tunggakan');
        });
    }
};
