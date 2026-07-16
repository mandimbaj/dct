<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('failed_login_count')->default(0)->after('password');
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_login_count');
            $table->timestamp('locked_until')->nullable()->after('last_failed_login_at');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_count',
                'last_failed_login_at',
                'locked_until',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
