<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('status', 'tersedia')
            ->update(['status' => 'available']);
        DB::table('products')
            ->where('status', 'habis')
            ->update(['status' => 'out_of_stock']);

        Schema::table('products', function (Blueprint $table): void {
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['status', 'category']);
        });

        DB::table('products')
            ->where('status', 'available')
            ->update(['status' => 'tersedia']);
        DB::table('products')
            ->where('status', 'out_of_stock')
            ->update(['status' => 'habis']);
    }
};
