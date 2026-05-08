# Postman Collection Updates - Project Permissions

## ✅ **New Endpoints Added**

The following endpoints have been added to `ProjectRolesPermissions_API.postman_collection.json`:

---

## 📋 **1. Get Permissions Tree**

**Method:** `GET`  
**Endpoint:** `/projects/permissions/tree`  
**Description:** Get permissions in hierarchical tree structure grouped by submodule

**Headers:**
- `Accept: application/json`
- `Authorization: Bearer {{token}}`

**Use Case:** Display permissions in a tree view for UI

---

## 📋 **2. Bulk Permission Check** ⭐

**Method:** `POST`  
**Endpoint:** `/projects/{{project_id}}/check-permissions`  
**Description:** Check multiple permissions at once for current user

**Request Body:**
```json
{
    "permissions": [
        "PROJECT_EMPLOYEE_CREATE",
        "PROJECT_EMPLOYEE_UPDATE",
        "PROJECT_EMPLOYEE_DELETE",
        "PROJECT_ARCHIVE_VIEW",
        "PROJECT_BUDGET_UPDATE"
    ]
}
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "project_id": "abc-123-def-456",
        "user_id": "user-789-xyz-012",
        "permissions": {
            "PROJECT_EMPLOYEE_CREATE": true,
            "PROJECT_ARCHIVE_VIEW": true,
            "PROJECT_BUDGET_UPDATE": false
        }
    }
}
```

**Use Case:** Frontend permission checks on page load

---

## 📋 **3. Get Users with Permission** ⭐

**Method:** `GET`  
**Endpoint:** `/projects/{{project_id}}/users-with-permission/PROJECT_BUDGET_APPROVE`  
**Description:** Find all users who have a specific permission

**URL Parameters:**
- `{permission_key}` - Config key like `PROJECT_BUDGET_APPROVE`

**Response Example:**
```json
{
    "success": true,
    "data": {
        "project_id": "abc-123",
        "permission": "PROJECT_BUDGET_APPROVE",
        "permission_name": "project-management.project-management*budget.approve",
        "users": [
            {
                "id": "user-1",
                "name": "John Doe",
                "email": "john@example.com",
                "role": {
                    "id": "role-1",
                    "name": "Project Manager",
                    "slug": "project-manager"
                }
            },
            {
                "id": "user-2",
                "name": "Jane Smith",
                "email": "jane@example.com",
                "role": {
                    "id": "role-2",
                    "name": "Finance Manager",
                    "slug": "finance-manager"
                }
            }
        ],
        "count": 2
    }
}
```

**Use Case:** Find who can approve budgets, send notifications

---

## 📋 **4. Compare Roles** ⭐

**Method:** `GET`  
**Endpoint:** `/projects/{{project_id}}/roles/compare?role1={{role_id}}&role2={{role_id_2}}`  
**Description:** Compare permissions between two roles

**Query Parameters:**
- `role1` - First role ID
- `role2` - Second role ID

**Response Example:**
```json
{
    "success": true,
    "data": {
        "project_id": "abc-123",
        "role1": {
            "id": "role-1",
            "name": "Project Manager",
            "permissions_count": 25
        },
        "role2": {
            "id": "role-2",
            "name": "Team Lead",
            "permissions_count": 15
        },
        "comparison": {
            "common_permissions": [
                "project-management.project-management*employee.view",
                "project-management.project-management*task.view"
            ],
            "common_count": 10,
            "only_in_role1": [
                "project-management.project-management*budget.approve",
                "project-management.project-management*employee.delete"
            ],
            "only_in_role1_count": 15,
            "only_in_role2": [
                "project-management.project-management*task.assign"
            ],
            "only_in_role2_count": 5
        }
    }
}
```

**Use Case:** Role analysis, permission auditing

---

## 📋 **5. Get My Permissions (Tree)**

**Method:** `GET`  
**Endpoint:** `/projects/{{project_id}}/my-permissions`  
**Description:** Get current user's permissions in hierarchical tree structure

**Use Case:** Display user's permissions in tree view

---

## 📋 **6. Get My Permissions (Flat)**

**Method:** `GET`  
**Endpoint:** `/projects/{{project_id}}/my-permissions/flat`  
**Description:** Get current user's permissions in flat array format

**Use Case:** Quick permission list for frontend

---

## 🔧 **Variables Added**

```json
{
    "key": "role_id_2",
    "value": "role-2-uuid-here",
    "type": "string"
}
```

---

## 📊 **Collection Summary**

### **Total Endpoints:** 19

#### **Project Permissions** (9 endpoints)
1. ✅ List All Permissions
2. ✅ Get Permissions by Submodule
3. ✅ Update Permission Translations
4. ✅ **Get Permissions Tree** (NEW)
5. ✅ **Bulk Permission Check** (NEW)
6. ✅ **Get Users with Permission** (NEW)
7. ✅ **Compare Roles** (NEW)
8. ✅ **Get My Permissions (Tree)** (NEW)
9. ✅ **Get My Permissions (Flat)** (NEW)

#### **Project Roles** (10 endpoints)
1. ✅ List Project Roles
2. ✅ Create Project Role
3. ✅ Get Role Details
4. ✅ Update Project Role
5. ✅ Delete Project Role
6. ✅ Assign Permissions to Role
7. ✅ Sync Permissions to Role
8. ✅ Get Permissions Tree
9. ✅ Get My Permissions (Tree)
10. ✅ Get My Permissions (Flat)

---

## 🎯 **How to Use**

### **1. Import Collection**
- Open Postman
- Click **Import**
- Select `ProjectRolesPermissions_API.postman_collection.json`

### **2. Set Variables**
- `url` = `http://localhost/api/v1`
- `token` = Your Bearer token
- `project_id` = Your project UUID
- `role_id` = Role UUID
- `role_id_2` = Second role UUID (for comparison)
- `permission_id` = Permission UUID

### **3. Test Endpoints**

#### **Bulk Permission Check:**
1. Select "Bulk Permission Check" request
2. Update `project_id` variable
3. Modify permissions array in body
4. Click **Send**

#### **Get Users with Permission:**
1. Select "Get Users with Permission" request
2. Update `project_id` variable
3. Change permission key in URL (e.g., `PROJECT_BUDGET_APPROVE`)
4. Click **Send**

#### **Compare Roles:**
1. Select "Compare Roles" request
2. Update `project_id`, `role_id`, and `role_id_2` variables
3. Click **Send**

---

## 💡 **Example Workflows**

### **Frontend Permission Check**
```javascript
// 1. Use "Bulk Permission Check" endpoint
// 2. Pass all required permissions
// 3. Cache results in frontend
// 4. Control UI based on permissions
```

### **Find Budget Approvers**
```javascript
// 1. Use "Get Users with Permission" endpoint
// 2. Pass "PROJECT_BUDGET_APPROVE" as permission key
// 3. Get list of users who can approve
// 4. Send notifications to them
```

### **Role Migration Planning**
```javascript
// 1. Use "Compare Roles" endpoint
// 2. Compare current role with target role
// 3. Review permission differences
// 4. Plan migration strategy
```

---

## 📝 **Response Examples Included**

The following endpoints include **response examples**:

1. ✅ **Bulk Permission Check** - Shows true/false for each permission
2. ✅ **Get Users with Permission** - Shows users array with roles
3. ✅ **Compare Roles** - Shows detailed comparison with counts

---

## 🚀 **Benefits**

### **For Developers:**
- ✅ Ready-to-use examples
- ✅ Clear documentation
- ✅ Response examples for reference
- ✅ Easy testing

### **For Frontend:**
- ✅ Bulk permission check (single API call)
- ✅ User permission lookup
- ✅ Role comparison data

### **For Testing:**
- ✅ All endpoints in one collection
- ✅ Variables for easy switching
- ✅ Example requests and responses

---

## 📚 **Related Documentation**

- **API Guide**: `PROJECT_PERMISSIONS_NEW_FEATURES.md`
- **Quick Start**: `PROJECT_PERMISSIONS_QUICK_START.md`
- **Integration**: `PROJECT_PERMISSIONS_INTEGRATION_GUIDE.md`

---

**Collection File:** `ProjectRolesPermissions_API.postman_collection.json`  
**Version:** 2.1.0  
**Last Updated:** April 15, 2026  
**New Endpoints:** 6
