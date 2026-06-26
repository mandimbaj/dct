<?php

use App\Models\DataIntegrationConnection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_integration_connections', function (Blueprint $table): void {
            $table->string('ssl_mode')->default(DataIntegrationConnection::SSL_MODE_DISABLED);
            $table->text('ssl_ca_path')->nullable();
            $table->text('ssl_certificate_path')->nullable();
            $table->text('ssl_key_path')->nullable();
            $table->string('ssl_cipher')->nullable();
            $table->unsignedSmallInteger('connection_timeout')->default(15);
        });

        DB::table('data_integration_connections')
            ->where('integration_method', DataIntegrationConnection::METHOD_DIRECT)
            ->whereRaw('lower(server_name) like ?', ['%.mysql.database.azure.com'])
            ->update([
                'ssl_mode' => DataIntegrationConnection::SSL_MODE_VERIFY_IDENTITY,
            ]);
    }

    public function down(): void
    {
        Schema::table('data_integration_connections', function (Blueprint $table): void {
            $table->dropColumn([
                'ssl_mode',
                'ssl_ca_path',
                'ssl_certificate_path',
                'ssl_key_path',
                'ssl_cipher',
                'connection_timeout',
            ]);
        });
    }
};
