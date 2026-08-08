<?php

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Project\ProjectType\Models\Violation;

class ViolationSeeder extends Seeder
{
    public function run(): void
    {
        $violations = [
            ['code' => '1-19-2-1',  'description' => 'عدم حمل بطاقة التعميد والتأهيل في موقع العمل', 'category' => 'A', 'default_weight' => 7],
            ['code' => '2-19-2-1',  'description' => 'مخالفة عدم تأهيل سائق معدات', 'category' => 'B', 'default_weight' => 2],
            ['code' => '3-19-2-1',  'description' => 'عدم تدريب الموظفين على (إجراءات العمل الآمن وتقييم المخاطر، اجتماع ما قبل البدء بالعمل) وتوفرها بموقع العمل باللغة التي يفهمها العاملين.', 'category' => 'B', 'default_weight' => 2],
            ['code' => '4-19-2-1',  'description' => 'البدء بالعمل دون تركيب أقفال السلامة المعتمدة بالشركة', 'category' => 'A', 'default_weight' => 7],
            ['code' => '5-19-2-1',  'description' => 'عدم وجود البطاقات التحذيرية', 'category' => 'B', 'default_weight' => 2],
            ['code' => '6-19-2-1',  'description' => 'عدم الالتزام بمسافة الخلوص الآمنة للخطوط الهوائية', 'category' => 'B', 'default_weight' => 2],
            ['code' => '7-19-2-1',  'description' => 'العمل من دون تصريح عمل', 'category' => 'A', 'default_weight' => 7],
            ['code' => '8-19-2-1',  'description' => 'عدم ارتداء ملابس الحماية من السقوط', 'category' => 'A', 'default_weight' => 7],
            ['code' => '9-19-2-1',  'description' => 'عدم ارتداء ملابس-مهمات الوقاية الشخصية (صالحة ومطابقة للمواصفات)', 'category' => 'A', 'default_weight' => 7],
            ['code' => '10-19-2-1', 'description' => 'عدم توفير مصدر تغذية آمن', 'category' => 'B', 'default_weight' => 2],
            ['code' => '11-19-2-1', 'description' => 'استخدام عدد يدوية غير معزولة', 'category' => 'B', 'default_weight' => 2],
            ['code' => '12-19-2-1', 'description' => 'استخدام حواجز حماية تالفة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '13-19-2-1', 'description' => 'عدم وضع حواجز الحماية في أماكنها الصحيحة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '14-19-2-1', 'description' => 'عدم وضع حواجز وإشارات ولوحات مرورية وإنارة ليلية على الطرق وإغلاق منطقة العمل', 'category' => 'A', 'default_weight' => 7],
            ['code' => '15-19-2-1', 'description' => 'عدم تسجيل الحمل الأقصى الآمن على معدات السلامة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '16-19-2-1', 'description' => 'عدم سلامة المركبات', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '17-19-2-1', 'description' => 'عدم سلامة المعدات', 'category' => 'B', 'default_weight' => 0],
            ['code' => '18-19-2-1', 'description' => 'عدم توفير طفايات حريق صالحة ومفحوصة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '19-19-2-1', 'description' => 'عدم وجود (مسعف - مكافح حريق) مؤهل', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '20-19-2-1', 'description' => 'التدخين في منطقة العمل', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '21-19-2-1', 'description' => 'عدم توفير حقيبة إسعافات أولية كاملة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '22-19-2-1', 'description' => 'عدم وجود الشعار الخاص بالمقاول', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '23-19-2-1', 'description' => 'عدم إزالة المخلفات بعد الانتهاء من العمل', 'category' => 'C', 'default_weight' => 0],
            ['code' => '24-19-2-1', 'description' => 'عدم تطبيق أنظمة السلامة والصحة المهنية على مستودع المقاول', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '25-19-2-1', 'description' => '1- عدم توفير مظلة للعاملين داخل الغرفة في حالة الجو الحار', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '26-19-2-1', 'description' => '2- عدم توفر كمية كافية من مياه الشرب في موقع العمل لعمالة المقاول', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '27-19-2-1', 'description' => '3- عدم سند جوانب الحفر في حالة الحفريات العميقة', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '28-19-2-1', 'description' => '4- عدم وضع جسور عبور مشاة سليمة فوق الحفريات المواجهة لأبواب المنازل والكراجات يمكن العبور عليها بشكل سليم', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '29-19-2-1', 'description' => '25- عدم نقل او تخزين المواد بطريقة جيدة ( غير سيئة )', 'category' => 'C', 'default_weight' => 0.5],
            ['code' => '30-19-2-1', 'description' => '26- عدم التقيد بطريقة تمديد الكابلات الأرضية وسحب الأسلاك الهوائية حسب المواصفات', 'category' => 'A', 'default_weight' => 7],
            ['code' => '31-19-2-1', 'description' => '27- عدم استخدام عمالة نظامية', 'category' => 'A', 'default_weight' => 7],
            ['code' => '32-19-2-1', 'description' => '28- التعاقد مع مقاول من الباطن دون موافقة الشركة', 'category' => 'A', 'default_weight' => 7],
            ['code' => '33-19-2-1', 'description' => '29- الفصل أو التوصيل لمعدات الشركة من قبل المقاول دون موافقة الشركة', 'category' => 'A', 'default_weight' => 7],
            ['code' => '34-19-2-1', 'description' => '30- تخزين المواد في مستودع غير معلن للشركة', 'category' => 'A', 'default_weight' => 7],
        ];

        foreach ($violations as $v) {
            Violation::updateOrCreate(['code' => $v['code']], $v);
        }
    }
}
