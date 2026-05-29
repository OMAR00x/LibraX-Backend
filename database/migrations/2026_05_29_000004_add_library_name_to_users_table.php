<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('library_name')->nullable()->after('role');
            $table->string('library_address')->nullable()->after('library_name');
            $table->decimal('library_latitude', 10, 8)->nullable()->after('library_address');
            $table->decimal('library_longitude', 11, 8)->nullable()->after('library_latitude');
            $table->decimal('wallet_balance', 10, 2)->default(0)->after('library_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'library_name',
                'library_address',
                'library_latitude',
                'library_longitude',
                'wallet_balance'
            ]);
        });
    }
};
