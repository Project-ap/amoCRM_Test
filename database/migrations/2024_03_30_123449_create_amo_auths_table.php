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
        Schema::create('amo_auths', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->primary()->unique();
            $table->longText('access_token');
            $table->longText('refresh_token');
            $table->dateTime('expires');
            $table->string('base_domain');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amo_auths');
    }
};
