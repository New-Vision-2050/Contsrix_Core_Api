<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // حذف البيانات قبل التعديل
        // DB::table('feature_permission')->delete();
        // DB::table('features')->delete();

        // تعديل الجدول
        Schema::table('features', function (Blueprint $table) {
            // إذا العمود module_id موجود احذفه، وسيسقط معه المفتاح الأجنبي تلقائيًا
            if (Schema::hasColumn('features', 'module_id')) {
                $table->dropColumn('module_id');
            }

            // إضافة العمود program_id مع المفتاح الأجنبي
            $table->uuid('program_id')->after('slug');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }

 
};