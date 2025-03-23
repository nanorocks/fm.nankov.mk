<?php

use App\Models\RadioChannel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(RadioChannel::TABLE, function (Blueprint $table) {
            $table->boolean(RadioChannel::PUBLISHED)->default(false)->after('subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(RadioChannel::TABLE, function (Blueprint $table) {
            $table->dropColumn(RadioChannel::PUBLISHED);
        });
    }
};
