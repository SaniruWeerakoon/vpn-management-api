<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vpn_clients', function (Blueprint $table) {
            $table->dateTime('provisioned_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->string('last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vpn_clients', function (Blueprint $table) {
            //
        });
    }
};
