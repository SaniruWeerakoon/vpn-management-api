<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vpn_clients', function (Blueprint $table) {
            $table->enum('status', ['pending', 'provisioning', 'provisioned', 'revoking', 'revoked', 'failed'])->default('pending')->change();
            $table->longText('config')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vpn_clients', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'revoked', 'failed'])->default('pending');

        });
    }
};
