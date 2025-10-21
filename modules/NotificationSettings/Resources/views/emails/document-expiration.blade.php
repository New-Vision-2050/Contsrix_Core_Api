<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعار انتهاء صلاحية المستندات</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #007bff;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #f39c12;
        }
        .alert.danger {
            background-color: #f8d7da;
            border-color: #f1c0c7;
            border-left-color: #e74c3c;
        }
        .alert-icon {
            font-size: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .document-section {
            margin-bottom: 30px;
        }
        .section-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ecf0f1;
        }
        .document-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        .document-item.expired {
            border-left-color: #e74c3c;
            background-color: #fdeded;
        }
        .document-item.due-today {
            border-left-color: #f39c12;
            background-color: #fef9e7;
        }
        .document-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .document-details {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        .document-date {
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            font-size: 12px;
        }
        .date-expired {
            background-color: #e74c3c;
            color: white;
        }
        .date-due-today {
            background-color: #f39c12;
            color: white;
        }
        .date-upcoming {
            background-color: #27ae60;
            color: white;
        }
        .custom-message {
            background-color: #e8f4fd;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #007bff;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
        }
        .company-name {
            color: #007bff;
            font-weight: 600;
        }
        .no-documents {
            text-align: center;
            color: #27ae60;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    @php
        $itemLabel = $itemType === 'file' ? 'ملف' : 'مستند';
        $itemsLabel = $itemType === 'file' ? 'ملفات' : 'مستندات';
        $icon = $itemType === 'file' ? '📁' : '📄';
        $dateField = $itemType === 'file' ? 'end_date' : 'notification_date';
    @endphp
    
    <div class="container">
        <div class="header">
            <h1>{{ $icon }} إشعار انتهاء صلاحية {{ $itemLabel }}</h1>
            <p style="color: #666; font-size: 14px;">إجمالي {{ $itemsLabel }}: {{ $totalDocuments }}</p>
        </div>

        @if($expiredDocuments && $expiredDocuments->count() > 0)
        <div class="alert danger">
            <span class="alert-icon">⚠️</span>
            <strong>عاجل:</strong> لديك {{ $itemsLabel }} منتهية الصلاحية وتحتاج إلى اهتمام فوري!
        </div>
        @elseif($upcomingDocuments && $upcomingDocuments->count() > 0)
        <div class="alert">
            <span class="alert-icon">📅</span>
            <strong>تذكير:</strong> لديك {{ $itemsLabel }} تنتهي اليوم!
        </div>
        @endif

        @if($expiredDocuments && $expiredDocuments->count() > 0)
        <div class="document-section">
            <h2 class="section-title">🚨 {{ $itemsLabel }} منتهية الصلاحية ({{ $expiredDocuments->count() }})</h2>
            @foreach($expiredDocuments as $item)
            <div class="document-item expired">
                @if($itemType === 'file')
                    <div class="document-name">{{ $item->name ?? 'ملف بدون اسم' }}</div>
                    @if($item->reference_number)
                    <div class="document-details">
                        <strong>رقم المرجع:</strong> {{ $item->reference_number }}
                    </div>
                    @endif
                @else
                    <div class="document-name">{{ $item->documentType?->name ?? 'نوع مستند غير معروف' }}</div>
                    <div class="document-details">
                        <strong>الشركة:</strong> {{ $item->company?->name ?? 'شركة غير معروفة' }}
                    </div>
                @endif
                <div class="document-details">
                    <strong>تاريخ الانتهاء:</strong> 
                    <span class="document-date date-expired">
                        {{ \Carbon\Carbon::parse($item->$dateField)->format('Y-m-d') }}
                        ({{ \Carbon\Carbon::parse($item->$dateField)->locale('ar')->diffForHumans() }})
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if($upcomingDocuments && $upcomingDocuments->count() > 0)
        <div class="document-section">
            <h2 class="section-title">📅 تنتهي اليوم ({{ $upcomingDocuments->count() }})</h2>
            @foreach($upcomingDocuments as $item)
            <div class="document-item due-today">
                @if($itemType === 'file')
                    <div class="document-name">{{ $item->name ?? 'ملف بدون اسم' }}</div>
                    @if($item->reference_number)
                    <div class="document-details">
                        <strong>رقم المرجع:</strong> {{ $item->reference_number }}
                    </div>
                    @endif
                @else
                    <div class="document-name">{{ $item->documentType?->name ?? 'نوع مستند غير معروف' }}</div>
                    <div class="document-details">
                        <strong>الشركة:</strong> {{ $item->company?->name ?? 'شركة غير معروفة' }}
                    </div>
                @endif
                <div class="document-details">
                    <strong>تاريخ الاستحقاق:</strong> 
                    <span class="document-date date-due-today">
                        {{ \Carbon\Carbon::parse($item->$dateField)->format('Y-m-d') }} (اليوم)
                    </span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if((!$expiredDocuments || $expiredDocuments->count() === 0) && 
            (!$upcomingDocuments || $upcomingDocuments->count() === 0))
        <div class="no-documents">
            <p>✅ أخبار رائعة! جميع {{ $itemsLabel }} محدثة.</p>
        </div>
        @endif

        @if($customMessage)
        <div class="custom-message">
            <strong>رسالة إضافية:</strong><br>
            {{ $customMessage }}
        </div>
        @endif

        <div class="footer">
            <p><strong>نظام إدارة المستندات</strong></p>
            <p>هذا إشعار تلقائي. يرجى التأكد من تجديد جميع المستندات في الوقت المحدد للحفاظ على الامتثال.</p>
            <p>تم الإنشاء في {{ \Carbon\Carbon::now()->locale('ar')->translatedFormat('d F Y الساعة h:i A') }}</p>
        </div>
    </div>
</body>
</html>
