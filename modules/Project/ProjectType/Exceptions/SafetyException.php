<?php

namespace Modules\Project\ProjectType\Exceptions;

use App\Exceptions\CustomException;

class SafetyException extends CustomException
{
    public static function assigneeRequired(): self
    {
        return new self('يجب تعيين مستخدم واحد على الأقل.', 422);
    }

    public static function notProjectEmployee(string $userId): self
    {
        return new self("المستخدم {$userId} ليس موظفاً في هذا المشروع.", 422);
    }

    public static function cannotModifyCompleted(): self
    {
        return new self('لا يمكن تعديل مهمة مكتملة.', 422);
    }

    public static function cannotEvaluateCompleted(): self
    {
        return new self('لا يمكن تقييم مهمة مكتملة.', 422);
    }

    public static function cannotDeleteCompleted(): self
    {
        return new self('لا يمكن حذف مهمة مكتملة.', 422);
    }

    public static function notAuthorizedToEvaluate(): self
    {
        return new self('غير مصرح لك بتقييم هذه المهمة.', 403);
    }

    public static function invalidMorphable(): self
    {
        return new self('المصدر المرتبط بالمهمة غير صالح أو لا ينتمي لهذا المشروع.', 422);
    }

    public static function notFound(): self
    {
        return new self('مهمة السلامة غير موجودة.', 404);
    }

    public static function searchNotFound(): self
    {
        return new self('لم يتم العثور على إشعار أو أمر عمل بهذا الرقم.', 404);
    }

    public static function noFoundViolations(): self
    {
        return new self('لا توجد مخالفات بحالة "مخالفة موجودة" لإنشاء محضر المخالفة.', 422);
    }

    public static function reportGenerationFailed(): self
    {
        return new self('تعذر إنشاء ملف محضر المخالفة.', 500);
    }

    public static function weeklyReportTemplateMissing(): self
    {
        return new self('ملف قالب تقرير السلامة الأسبوعي غير موجود.', 500);
    }

    public static function weeklyReportGenerationFailed(): self
    {
        return new self('تعذر إنشاء تقرير السلامة الأسبوعي.', 500);
    }

    public static function weeklyReportNotFound(): self
    {
        return new self('تقرير السلامة الأسبوعي غير موجود.', 404);
    }

    public static function weeklyReportNotReady(): self
    {
        return new self('التقرير غير جاهز للتحميل بعد.', 409);
    }

    public static function weeklyReportFileMissing(): self
    {
        return new self('ملف تقرير السلامة الأسبوعي غير موجود.', 404);
    }
}
