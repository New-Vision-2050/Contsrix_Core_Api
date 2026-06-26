import json
from pathlib import Path
import uuid

p = Path(r'C:\projects\constrix-microservices\constrix_api\ProjectNotification_API.postman_collection.json')
with open(p, encoding='utf-8') as f:
    c = json.load(f)

approve_req = {
    "name": "Approve Assigned Task",
    "request": {
        "method": "POST",
        "header": [
            {"key": "Accept", "value": "application/json", "type": "text"},
            {"key": "Authorization", "value": "Bearer {{token}}", "type": "text"}
        ],
        "body": {
            "mode": "raw",
            "raw": json.dumps({"notes": "Approved from assigned inbox"}, ensure_ascii=False, indent=2),
            "options": {"raw": {"language": "json"}}
        },
        "url": {
            "raw": "{{url}}/admin/employee-tasks/{{task_id}}/approve",
            "host": ["{{url}}"],
            "path": ["admin", "employee-tasks", "{{task_id}}", "approve"]
        },
        "description": "Approve an assigned task from the assigned inbox. Use the task id from the assigned-inbox response."
    },
    "response": [
        {
            "name": "Success Response",
            "status": "OK",
            "code": 200,
            "_postman_previewlanguage": "json",
            "body": json.dumps({
                "code": "SUCCESS_WITH_PAYLOAD_OBJECT",
                "message": "Task approved successfully",
                "payload": {
                    "id": "task-uuid",
                    "status": "approved",
                    "approved_at": "2026-06-26 12:00:00"
                }
            }, ensure_ascii=False, indent=2)
        }
    ]
}

reject_req = {
    "name": "Reject Assigned Task",
    "request": {
        "method": "POST",
        "header": [
            {"key": "Accept", "value": "application/json", "type": "text"},
            {"key": "Authorization", "value": "Bearer {{token}}", "type": "text"}
        ],
        "body": {
            "mode": "raw",
            "raw": json.dumps({"reason": "Cannot perform at this time"}, ensure_ascii=False, indent=2),
            "options": {"raw": {"language": "json"}}
        },
        "url": {
            "raw": "{{url}}/admin/employee-tasks/{{task_id}}/reject",
            "host": ["{{url}}"],
            "path": ["admin", "employee-tasks", "{{task_id}}", "reject"]
        },
        "description": "Reject an assigned task from the assigned inbox. Use the task id from the assigned-inbox response."
    },
    "response": [
        {
            "name": "Success Response",
            "status": "OK",
            "code": 200,
            "_postman_previewlanguage": "json",
            "body": json.dumps({
                "code": "SUCCESS_WITH_PAYLOAD_OBJECT",
                "message": "Task rejected successfully",
                "payload": {
                    "id": "task-uuid",
                    "status": "rejected",
                    "rejected_at": "2026-06-26 12:00:00"
                }
            }, ensure_ascii=False, indent=2)
        }
    ]
}

found = False
for folder in c.get('item', []):
    if folder.get('name') == '4. Employee Task Lifecycle (Start/End)':
        # Ensure no duplicates
        existing_names = {r.get('name') for r in folder.get('item', [])}
        if 'Approve Assigned Task' not in existing_names:
            folder['item'].append(approve_req)
        if 'Reject Assigned Task' not in existing_names:
            folder['item'].append(reject_req)
        found = True
        print('Added Approve/Reject Assigned Task requests.')
        break

if not found:
    print('Target folder not found.')

with open(p, 'w', encoding='utf-8') as f:
    json.dump(c, f, ensure_ascii=False, indent=2)
