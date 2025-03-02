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
        Schema::create(RadioChannel::TABLE, function (Blueprint $table) {
            $table->id();

            $table->string(RadioChannel::LINK);
            $table->string(RadioChannel::SRC);
            $table->string(RadioChannel::ALT)->unique();
            $table->string(RadioChannel::BASE_URL);

            $table->string(RadioChannel::PHOTO)->nullable();
            $table->string(RadioChannel::TITLE)->nullable();
            $table->string(RadioChannel::AUDIO_URL)->nullable();
            $table->string(RadioChannel::SUBTITLE)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(RadioChannel::TABLE);
    }
};