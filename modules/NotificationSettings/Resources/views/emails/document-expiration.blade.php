<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Expiration Notification</title>
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
    <div class="container">
        <div class="header">
            <h1>📄 Document Expiration Notification</h1>
            <p>Company: <span class="company-name">{{ $companyName }}</span></p>
        </div>

        @if($hasExpiredDocuments)
        <div class="alert danger">
            <span class="alert-icon">⚠️</span>
            <strong>Urgent:</strong> You have documents that have already expired and require immediate attention!
        </div>
        @elseif($hasDueTodayDocuments)
        <div class="alert">
            <span class="alert-icon">📅</span>
            <strong>Reminder:</strong> You have documents expiring today!
        </div>
        @endif

        @if($expiredDocuments && $expiredDocuments->count() > 0)
        <div class="document-section">
            <h2 class="section-title">🚨 Expired Documents ({{ $expiredDocuments->count() }})</h2>
            @foreach($expiredDocuments as $document)
            <div class="document-item expired">
                <div class="document-name">{{ $document->documentType?->name ?? 'Unknown Document Type' }}</div>
                <div class="document-details">
                    <strong>Company:</strong> {{ $document->company?->name ?? 'Unknown Company' }}
                </div>
                <div class="document-details">
                    <strong>Expired Date:</strong> 
                    <span class="document-date date-expired">
                        {{ \Carbon\Carbon::parse($document->notification_date)->format('M d, Y') }}
                        ({{ \Carbon\Carbon::parse($document->notification_date)->diffForHumans() }})
                    </span>
                </div>
                @if($document->description)
                <div class="document-details">
                    <strong>Description:</strong> {{ $document->description }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($dueTodayDocuments && $dueTodayDocuments->count() > 0)
        <div class="document-section">
            <h2 class="section-title">📅 Due Today ({{ $dueTodayDocuments->count() }})</h2>
            @foreach($dueTodayDocuments as $document)
            <div class="document-item due-today">
                <div class="document-name">{{ $document->documentType?->name ?? 'Unknown Document Type' }}</div>
                <div class="document-details">
                    <strong>Company:</strong> {{ $document->company?->name ?? 'Unknown Company' }}
                </div>
                <div class="document-details">
                    <strong>Due Date:</strong> 
                    <span class="document-date date-due-today">
                        {{ \Carbon\Carbon::parse($document->notification_date)->format('M d, Y') }} (Today)
                    </span>
                </div>
                @if($document->description)
                <div class="document-details">
                    <strong>Description:</strong> {{ $document->description }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($upcomingDocuments && $upcomingDocuments->count() > 0)
        <div class="document-section">
            <h2 class="section-title">📋 Upcoming Documents ({{ $upcomingDocuments->count() }})</h2>
            @foreach($upcomingDocuments as $document)
            <div class="document-item">
                <div class="document-name">{{ $document->documentType?->name ?? 'Unknown Document Type' }}</div>
                <div class="document-details">
                    <strong>Company:</strong> {{ $document->company?->name ?? 'Unknown Company' }}
                </div>
                <div class="document-details">
                    <strong>Due Date:</strong> 
                    <span class="document-date date-upcoming">
                        {{ \Carbon\Carbon::parse($document->notification_date)->format('M d, Y') }}
                        ({{ \Carbon\Carbon::parse($document->notification_date)->diffForHumans() }})
                    </span>
                </div>
                @if($document->description)
                <div class="document-details">
                    <strong>Description:</strong> {{ $document->description }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if((!$expiredDocuments || $expiredDocuments->count() === 0) && 
            (!$dueTodayDocuments || $dueTodayDocuments->count() === 0) && 
            (!$upcomingDocuments || $upcomingDocuments->count() === 0))
        <div class="no-documents">
            <p>✅ Great news! All your documents are up to date.</p>
        </div>
        @endif

        @if($customMessage)
        <div class="custom-message">
            <strong>Additional Message:</strong><br>
            {{ $customMessage }}
        </div>
        @endif

        <div class="footer">
            <p><strong>Document Management System</strong></p>
            <p>This is an automated notification. Please ensure all documents are renewed on time to maintain compliance.</p>
            <p>Generated on {{ \Carbon\Carbon::now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>
