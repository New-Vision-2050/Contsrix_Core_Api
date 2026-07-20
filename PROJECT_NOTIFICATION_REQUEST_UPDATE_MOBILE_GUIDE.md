# Project Notification — Request Update (Mobile / Flutter Guide)

> **Date:** 2026-07-16  
> **Scope:** Mobile app — `requestUpdate` endpoint changes + payload structure + Flutter UI presentation

---

## 1. What Changed

The `POST /api/v1/projects/notifications/{id}/request-update` endpoint now accepts **`site_status_type_id`** and **`site_status_type_values`** — the same dynamic keys that create and update endpoints already support.

### Before (old)
Only flat notification fields were accepted:
```json
{
  "notification_type": "...",
  "feeder_number": "...",
  "work_description": "...",
  "notes": "..."
}
```

### After (new)
Now also accepts site status type + dynamic key-value pairs:
```json
{
  "notification_type": "...",
  "feeder_number": "...",
  "work_description": "...",
  "notes": "...",
  "site_status_type_id": "type-uuid",
  "site_status_type_values": [
    { "key_id": "key-uuid-1", "value": "220V" },
    { "key_id": "key-uuid-2", "value": "75kW" },
    { "key_id": "key-uuid-3", "value": "سليم" }
  ]
}
```

### How it works internally
- **No procedure configured** → update applies immediately, site status values are synced right away.
- **Procedure configured** → update is stored as a workflow process snapshot; site status values are synced when the process completes (all steps approved).

---

## 2. Endpoint

```
POST /api/v1/projects/notifications/{notification_id}/request-update
```

### Headers
| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {jwt_token}` |
| `X-Tenant-ID` | `{tenant_id}` |
| `Accept` | `application/json` |
| `Accept-Language` | `ar` or `en` |
| `Content-Type` | `application/json` |

---

## 3. Request Payload — All Fields

| Field | Type | Nullable | Notes |
|-------|------|----------|-------|
| `notification_type` | string | ✅ | max 255 |
| `feeder_number` | string | ✅ | max 255 |
| `machine_number` | string | ✅ | max 255 |
| `work_description` | string | ✅ | free text |
| `contractor_name` | string | ✅ | max 255 |
| `contractor_technical_name` | string | ✅ | max 255 |
| `contractor_mobile` | string | ✅ | max 30 |
| `task_latitude` | numeric | ✅ | between -90,90 |
| `task_longitude` | numeric | ✅ | between -180,180 |
| `permit_source` | string | ✅ | max 255 |
| `permit_recipient` | string | ✅ | max 255 |
| `notes` | string | ✅ | max 2000 |
| `internal_procedure_setting_id` | uuid | ✅ | exists in procedure_settings |
| `files` | array<File> | ✅ | jpg,jpeg,png,webp — max 10MB each |
| `site_status_type_id` | uuid | ✅ | exists in site_status_types |
| `site_status_type_values` | array | ✅ | dynamic key-value pairs |
| `site_status_type_values.*.key_id` | uuid | required when values present | exists in site_status_type_keys |
| `site_status_type_values.*.value` | string | ✅ | user-entered value |

> **All fields are nullable** — only send the fields you want to update. The backend filters out null values.

---

## 4. Site Status Type Values — How It Works

### 4.1 Fetch available types
```text
GET /api/v1/projects/notifications/site-status-types?project_id={project_id}
```

Response:
```json
{
  "items": [
    { "id": "type-uuid", "name_ar": "صيانة دورية", "name_en": "Periodic Maintenance", "sort_order": 1, "is_active": true }
  ]
}
```

### 4.2 Fetch keys for a type
```text
GET /api/v1/projects/notifications/site-status-types/{type_id}/keys
```

Response:
```json
{
  "items": [
    {
      "id": "key-uuid-1",
      "name_ar": "قراءة الجهد",
      "name_en": "Voltage Reading",
      "key": "voltage_reading",
      "field_type": "text",
      "options": null,
      "show_in_site_status_updates": true,
      "sort_order": 1,
      "is_active": true
    },
    {
      "id": "key-uuid-2",
      "name_ar": "الحالة",
      "name_en": "Condition Status",
      "key": "condition_status",
      "field_type": "select",
      "options": ["سليم", "تالف", "بحاجة صيانة"],
      "show_in_site_status_updates": true,
      "sort_order": 2,
      "is_active": true
    }
  ]
}
```

### 4.3 `field_type` → Flutter widget mapping

| `field_type` | Flutter Widget | Notes |
|--------------|----------------|-------|
| `text` | `TextFormField` | Standard text input |
| `number` | `TextFormField(keyboardType: TextInputType.number)` | Numeric input |
| `date` | `showDatePicker` → `Text` display | Date picker |
| `select` | `DropdownButtonFormField<String>` | Options from `options` array |

---

## 5. Flutter Implementation

### 5.1 Models

```dart
class SiteStatusType {
  final String id;
  final String nameAr;
  final String? nameEn;
  final int sortOrder;
  final bool isActive;

  factory SiteStatusType.fromJson(Map<String, dynamic> json) => SiteStatusType(
    id: json['id'],
    nameAr: json['name_ar'],
    nameEn: json['name_en'],
    sortOrder: json['sort_order'] ?? 0,
    isActive: json['is_active'] ?? true,
  );

  String get localizedName =>
      Get.locale?.languageCode == 'ar' ? nameAr : (nameEn ?? nameAr);
}

class SiteStatusTypeKey {
  final String id;
  final String nameAr;
  final String? nameEn;
  final String key;
  final String fieldType; // text | number | date | select
  final List<String>? options;
  final bool showInSiteStatusUpdates;
  final int sortOrder;

  factory SiteStatusTypeKey.fromJson(Map<String, dynamic> json) => SiteStatusTypeKey(
    id: json['id'],
    nameAr: json['name_ar'],
    nameEn: json['name_en'],
    key: json['key'],
    fieldType: json['field_type'],
    options: (json['options'] as List?)?.map((e) => e.toString()).toList(),
    showInSiteStatusUpdates: json['show_in_site_status_updates'] ?? false,
    sortOrder: json['sort_order'] ?? 0,
  );

  String get localizedName =>
      Get.locale?.languageCode == 'ar' ? nameAr : (nameEn ?? nameAr);
}

class SiteStatusValue {
  final String keyId;
  final String value;

  SiteStatusValue({required this.keyId, required this.value});

  Map<String, dynamic> toJson() => {'key_id': keyId, 'value': value};
}
```

### 5.2 Request Update Payload

```dart
class RequestUpdatePayload {
  final String? notificationType;
  final String? feederNumber;
  final String? machineNumber;
  final String? workDescription;
  final String? contractorName;
  final String? contractorTechnicalName;
  final String? contractorMobile;
  final double? taskLatitude;
  final double? taskLongitude;
  final String? permitSource;
  final String? permitRecipient;
  final String? notes;
  final String? internalProcedureSettingId;
  final String? siteStatusTypeId;
  final List<SiteStatusValue>? siteStatusTypeValues;

  RequestUpdatePayload({
    this.notificationType,
    this.feederNumber,
    this.machineNumber,
    this.workDescription,
    this.contractorName,
    this.contractorTechnicalName,
    this.contractorMobile,
    this.taskLatitude,
    this.taskLongitude,
    this.permitSource,
    this.permitRecipient,
    this.notes,
    this.internalProcedureSettingId,
    this.siteStatusTypeId,
    this.siteStatusTypeValues,
  });

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    // Only add non-null values
    if (notificationType != null) map['notification_type'] = notificationType;
    if (feederNumber != null) map['feeder_number'] = feederNumber;
    if (machineNumber != null) map['machine_number'] = machineNumber;
    if (workDescription != null) map['work_description'] = workDescription;
    if (contractorName != null) map['contractor_name'] = contractorName;
    if (contractorTechnicalName != null) map['contractor_technical_name'] = contractorTechnicalName;
    if (contractorMobile != null) map['contractor_mobile'] = contractorMobile;
    if (taskLatitude != null) map['task_latitude'] = taskLatitude;
    if (taskLongitude != null) map['task_longitude'] = taskLongitude;
    if (permitSource != null) map['permit_source'] = permitSource;
    if (permitRecipient != null) map['permit_recipient'] = permitRecipient;
    if (notes != null) map['notes'] = notes;
    if (internalProcedureSettingId != null) map['internal_procedure_setting_id'] = internalProcedureSettingId;
    if (siteStatusTypeId != null) map['site_status_type_id'] = siteStatusTypeId;
    if (siteStatusTypeValues != null) {
      map['site_status_type_values'] = siteStatusTypeValues!.map((v) => v.toJson()).toList();
    }
    return map;
  }
}
```

### 5.3 API Call

```dart
Future<Response> requestUpdate({
  required String notificationId,
  required RequestUpdatePayload payload,
}) async {
  return await _dio.post(
    '/api/v1/projects/notifications/$notificationId/request-update',
    data: payload.toJson(),
    options: Options(
      headers: {
        'Authorization': 'Bearer $jwtToken',
        'X-Tenant-ID': tenantId,
        'Accept': 'application/json',
        'Accept-Language': Get.locale?.languageCode ?? 'ar',
      },
    ),
  );
}
```

### 5.4 Dynamic Form Widget

```dart
class SiteStatusValuesForm extends StatefulWidget {
  final String projectId;
  final String? initialSiteStatusTypeId;
  final List<SiteStatusValueResponse>? initialValues;

  const SiteStatusValuesForm({
    super.key,
    required this.projectId,
    this.initialSiteStatusTypeId,
    this.initialValues,
  });

  @override
  State<SiteStatusValuesForm> createState() => _SiteStatusValuesFormState();
}

class _SiteStatusValuesFormState extends State<SiteStatusValuesForm> {
  List<SiteStatusType> _types = [];
  List<SiteStatusTypeKey> _keys = [];
  String? _selectedTypeId;
  final Map<String, TextEditingController> _controllers = {};
  final Map<String, String?> _dropdownValues = {};

  @override
  void initState() {
    super.initState();
    _fetchTypes();
  }

  Future<void> _fetchTypes() async {
    final res = await _dio.get(
      '/api/v1/projects/notifications/site-status-types',
      queryParameters: {'project_id': widget.projectId},
    );
    setState(() {
      _types = (res.data['data']['items'] as List)
          .map((e) => SiteStatusType.fromJson(e))
          .toList();
      // Pre-select if editing
      if (widget.initialSiteStatusTypeId != null) {
        _selectedTypeId = widget.initialSiteStatusTypeId;
        _fetchKeys(widget.initialSiteStatusTypeId!);
      }
    });
  }

  Future<void> _fetchKeys(String typeId) async {
    final res = await _dio.get(
      '/api/v1/projects/notifications/site-status-types/$typeId/keys',
    );
    setState(() {
      _keys = (res.data['data']['items'] as List)
          .map((e) => SiteStatusTypeKey.fromJson(e))
          .where((k) => k.isActive)
          .toList()..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));

      // Clear old controllers
      _controllers.values.forEach((c) => c.dispose());
      _controllers.clear();
      _dropdownValues.clear();

      // Pre-fill from initialValues if editing
      if (widget.initialValues != null) {
        for (final val in widget.initialValues!) {
          if (val.fieldType == 'select') {
            _dropdownValues[val.keyId] = val.value;
          } else {
            _controllers[val.keyId] = TextEditingController(text: val.value);
          }
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // 1. Type dropdown
        DropdownButtonFormField<String>(
          value: _selectedTypeId,
          decoration: InputDecoration(labelText: 'نوع حالة الموقع'.tr),
          items: _types.map((t) => DropdownMenuItem(
            value: t.id,
            child: Text(t.localizedName),
          )).toList(),
          onChanged: (val) {
            setState(() => _selectedTypeId = val);
            if (val != null) _fetchKeys(val);
          },
        ),

        // 2. Dynamic fields
        if (_keys.isNotEmpty) ...[
          const SizedBox(height: 16),
          ..._keys.map((key) => _buildField(key)),
        ],
      ],
    );
  }

  Widget _buildField(SiteStatusTypeKey key) {
    switch (key.fieldType) {
      case 'text':
        _controllers.putIfAbsent(key.id, () => TextEditingController());
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            controller: _controllers[key.id],
            decoration: InputDecoration(labelText: key.localizedName),
          ),
        );

      case 'number':
        _controllers.putIfAbsent(key.id, () => TextEditingController());
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            controller: _controllers[key.id],
            keyboardType: TextInputType.number,
            decoration: InputDecoration(labelText: key.localizedName),
          ),
        );

      case 'date':
        _controllers.putIfAbsent(key.id, () => TextEditingController());
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            controller: _controllers[key.id],
            readOnly: true,
            decoration: InputDecoration(labelText: key.localizedName),
            onTap: () async {
              final date = await showDatePicker(
                context: context,
                initialDate: DateTime.now(),
                firstDate: DateTime(2000),
                lastDate: DateTime(2100),
              );
              if (date != null) {
                _controllers[key.id]!.text = DateFormat('yyyy-MM-dd').format(date);
              }
            },
          ),
        );

      case 'select':
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: DropdownButtonFormField<String>(
            value: _dropdownValues[key.id],
            decoration: InputDecoration(labelText: key.localizedName),
            items: (key.options ?? []).map((opt) => DropdownMenuItem(
              value: opt,
              child: Text(opt),
            )).toList(),
            onChanged: (val) => setState(() => _dropdownValues[key.id] = val),
          ),
        );

      default:
        return const SizedBox.shrink();
    }
  }

  /// Call this from the parent form to collect values
  List<SiteStatusValue> collectValues() {
    final values = <SiteStatusValue>[];
    for (final key in _keys) {
      if (key.fieldType == 'select') {
        final val = _dropdownValues[key.id];
        if (val != null) {
          values.add(SiteStatusValue(keyId: key.id, value: val));
        }
      } else {
        final controller = _controllers[key.id];
        if (controller != null && controller.text.isNotEmpty) {
          values.add(SiteStatusValue(keyId: key.id, value: controller.text));
        }
      }
    }
    return values;
  }

  String? get selectedTypeId => _selectedTypeId;
}
```

### 5.5 Submit from Parent Form

```dart
Future<void> submitRequestUpdate() async {
  final values = _siteStatusFormKey.currentState?.collectValues() ?? [];
  final typeId = _siteStatusFormKey.currentState?.selectedTypeId;

  final payload = RequestUpdatePayload(
    notificationType: _notificationTypeController.text.nullIfEmpty,
    feederNumber: _feederNumberController.text.nullIfEmpty,
    workDescription: _workDescriptionController.text.nullIfEmpty,
    contractorName: _contractorNameController.text.nullIfEmpty,
    contractorMobile: _contractorMobileController.text.nullIfEmpty,
    notes: _notesController.text.nullIfEmpty,
    internalProcedureSettingId: _selectedProcedureSettingId,
    siteStatusTypeId: typeId,
    siteStatusTypeValues: values.isNotEmpty ? values : null,
  );

  final res = await api.requestUpdate(
    notificationId: notificationId,
    payload: payload,
  );

  if (res.data['success'] == true) {
    // Handle success
  }
}
```

---

## 6. Response — Show Notification (with site status values)

When you call `GET /api/v1/projects/notifications/{id}`, the response includes:

```json
{
  "success": true,
  "data": {
    "id": "uuid-001",
    "notification_number": "NOTIF-2026-001",
    "notification_type": "إشعار عاجل",
    "status": "received",
    "status_label": "تم الاستلام",
    "site_status_type_id": "type-uuid",
    "site_status_type": {
      "id": "type-uuid",
      "name_ar": "صيانة دورية",
      "name_en": "Periodic Maintenance"
    },
    "site_status_values": [
      {
        "id": "val-uuid",
        "key_id": "key-uuid",
        "key": "voltage_reading",
        "name_ar": "قراءة الجهد",
        "name_en": "Voltage Reading",
        "field_type": "text",
        "options": null,
        "show_in_site_status_updates": true,
        "value": "220V"
      },
      {
        "id": "val-uuid-2",
        "key_id": "key-uuid-2",
        "key": "condition_status",
        "name_ar": "الحالة",
        "name_en": "Condition Status",
        "field_type": "select",
        "options": ["سليم", "تالف", "بحاجة صيانة"],
        "show_in_site_status_updates": true,
        "value": "سليم"
      }
    ]
  }
}
```

### Flutter parsing for display:

```dart
class SiteStatusValueResponse {
  final String id;
  final String keyId;
  final String key;
  final String nameAr;
  final String? nameEn;
  final String fieldType;
  final List<String>? options;
  final bool showInSiteStatusUpdates;
  final String? value;

  factory SiteStatusValueResponse.fromJson(Map<String, dynamic> json) =>
      SiteStatusValueResponse(
        id: json['id'],
        keyId: json['key_id'],
        key: json['key'],
        nameAr: json['name_ar'],
        nameEn: json['name_en'],
        fieldType: json['field_type'],
        options: (json['options'] as List?)?.map((e) => e.toString()).toList(),
        showInSiteStatusUpdates: json['show_in_site_status_updates'] ?? false,
        value: json['value'],
      );

  String get localizedName =>
      Get.locale?.languageCode == 'ar' ? nameAr : (nameEn ?? nameAr);
}
```

### Display in detail view (read-only):

```dart
Widget buildSiteStatusValues(List<SiteStatusValueResponse> values) {
  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: values.map((v) => Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Text('${v.localizedName}: ', style: TextStyle(fontWeight: FontWeight.bold)),
          Text(v.value ?? '-'),
          if (v.showInSiteStatusUpdates)
            IconButton(
              icon: Icon(Icons.copy, size: 16),
              onPressed: () => Clipboard.setData(ClipboardData(text: v.value ?? '')),
            ),
        ],
      ),
    )).toList(),
  );
}
```

---

## 7. Full Example Payload (request-update)

```json
{
  "notification_type": "إشعار عاجل - محدث",
  "feeder_number": "F-123-A",
  "machine_number": "M-456",
  "work_description": "صيانة عاجلة محدثة للمحول",
  "contractor_name": "شركة المقاولات المحدثة",
  "contractor_technical_name": "المهندس سعد",
  "contractor_mobile": "+966500000088",
  "task_latitude": 24.7200,
  "task_longitude": 46.6800,
  "permit_source": "بلدية الرياض",
  "permit_recipient": "أحمد محمد",
  "notes": "تحديث بيانات الإشعار",
  "internal_procedure_setting_id": "proc-setting-uuid",
  "site_status_type_id": "type-uuid",
  "site_status_type_values": [
    { "key_id": "key-uuid-1", "value": "220V" },
    { "key_id": "key-uuid-2", "value": "75kW" },
    { "key_id": "key-uuid-3", "value": "سليم" }
  ]
}
```

### Response:

```json
{
  "success": true,
  "message": "Update request submitted successfully",
  "data": {
    "id": "uuid-001",
    "notification_number": "NOTIF-2026-001",
    "notification_type": "إشعار عاجل - محدث",
    "status": "received",
    "status_label": "تم الاستلام",
    "site_status_type_id": "type-uuid",
    "site_status_type": {
      "id": "type-uuid",
      "name_ar": "صيانة دورية",
      "name_en": "Periodic Maintenance"
    },
    "site_status_values": [
      {
        "id": "val-1",
        "key_id": "key-uuid-1",
        "key": "voltage_reading",
        "name_ar": "قراءة الجهد",
        "name_en": "Voltage Reading",
        "field_type": "text",
        "options": null,
        "show_in_site_status_updates": true,
        "value": "220V"
      },
      {
        "id": "val-2",
        "key_id": "key-uuid-2",
        "key": "power_reading",
        "name_ar": "قراءة القدرة",
        "name_en": "Power Reading",
        "field_type": "text",
        "options": null,
        "show_in_site_status_updates": true,
        "value": "75kW"
      },
      {
        "id": "val-3",
        "key_id": "key-uuid-3",
        "key": "condition_status",
        "name_ar": "الحالة",
        "name_en": "Condition Status",
        "field_type": "select",
        "options": ["سليم", "تالف", "بحاجة صيانة"],
        "show_in_site_status_updates": true,
        "value": "سليم"
      }
    ]
  }
}
```

---

## 8. Rules Summary

| Rule | Description |
|------|-------------|
| All fields nullable | Only send what you want to update |
| `site_status_type_id = null` | Clears all stored site status values |
| `site_status_type_values` omitted | Existing values left unchanged |
| `site_status_type_values` provided | Fully replaces existing values |
| `key_id` must belong to selected type | Validated by backend |
| `field_type: select` | Value must be one of `options` |
| `files` | Separate multipart upload, not in JSON body |
