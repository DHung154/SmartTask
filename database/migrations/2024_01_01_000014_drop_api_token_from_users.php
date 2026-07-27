<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * api_token là di sản từ thời chưa dùng Sanctum, không chỗ nào đọc tới.
 * Sanctum đã có bảng personal_access_tokens riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_api_token_unique');
            $table->dropColumn('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('api_token', 64)->unique()->nullable();
        });
    }
};
