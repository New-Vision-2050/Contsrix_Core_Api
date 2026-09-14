# Face Verification Exception API

Users flagged as **exempt** bypass face/liveness verification entirely on both
`clock-in` and `clock-out`, regardless of `services.rekognition.enabled`.
The flag is stored on `users.face_verification_exempt` (boolean, default `false`).

Relevant source files:
- `modules/Attendance/Controllers/AttendanceController.php` — `getFaceVerificationException()`, `updateFaceVerificationException()`
- `modules/Attendance/Resources/routes/api.php` — route definitions
- `modules/Attendance/Services/FaceVerificationService.php` — `isExempt(User $user)`
- `modules/User/Models/User.php` — `face_verification_exempt` attribute
- `modules/User/Database/Migrations/2026_09_14_000000_add_face_verification_exempt_to_users_table.php`

---

## 1. Get exception status

Retrieve whether a given user is currently exempt from face/liveness verification.

```
GET {{baseUrl}}/attendance/users/{userId}/face-verification-exception
```

**Permission required:** `EMPLOYEE_ATTENDANCE_VIEW`

**Headers:** same as other authenticated Attendance endpoints
(`Authorization: Bearer {{token}}` + tenancy headers used across the collection).

### Path Parameters
| Name | Type | Description |
|---|---|---|
| `userId` | UUID | ID of the user to check |

### Response — 200 OK
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Face verification exception retrieved successfully.",
  "payload": {
    "user_id": "9c1b2e4a-1234-4a5b-9c1d-abcdef123456",
    "user_name": "John Doe",
    "has_face_verification_exception": false
  }
}
```

### Response — 404 Not Found
```json
{
  "code": "ATTENDANCE_ERROR",
  "message": "User not found."
}
```

---

## 2. Update exception status

Grant or revoke a user's exemption from face/liveness verification.

```
PUT {{baseUrl}}/attendance/users/{userId}/face-verification-exception
```

**Permission required:** `EMPLOYEE_ATTENDANCE_UPDATE`

**Headers:** same as other authenticated Attendance endpoints
(`Authorization: Bearer {{token}}` + tenancy headers used across the collection).

### Path Parameters
| Name | Type | Description |
|---|---|---|
| `userId` | UUID | ID of the user to update |

### Request Body
```json
{
  "has_exception": true
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `has_exception` | boolean | ✅ | `true` = exempt the user from face/liveness verification on clock-in/out. `false` = require verification normally. |

### Response — 200 OK
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Face verification exception updated successfully.",
  "payload": {
    "user_id": "9c1b2e4a-1234-4a5b-9c1d-abcdef123456",
    "has_face_verification_exception": true
  }
}
```

### Response — 404 Not Found
```json
{
  "code": "ATTENDANCE_ERROR",
  "message": "User not found."
}
```

### Response — 422 Validation Error
```json
{
  "message": "The has exception field is required.",
  "errors": {
    "has_exception": ["The has exception field is required."]
  }
}
```

---

## Where the flag is exposed / enforced

- **`GET {{baseUrl}}/attendance/user-constraint/today`** — includes
  `has_face_verification_exception` in the payload (mobile-facing "today" screen).
- **`POST {{baseUrl}}/attendance/clock-in`** and **`POST {{baseUrl}}/attendance/clock-out`** —
  if the authenticated user is exempt, `photo`/`liveness_session_id` become optional
  even when `services.rekognition.enabled = true`, and any verification attempt
  short-circuits with `provider: "exempt"` in `verification_data`.
