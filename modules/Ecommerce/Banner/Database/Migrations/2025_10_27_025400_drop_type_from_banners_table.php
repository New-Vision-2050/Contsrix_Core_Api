<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_27_025400_drop_type_from_banners_table Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
    
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('type')->after('url');
        });
    }
};
