# Branch Locations for Attendance Constraints Guide

## Overview

The AttendanceConstraint system now supports **custom location settings for each branch** assigned to a constraint. This allows you to define specific geographical locations, addresses, and geofencing parameters for attendance validation on a per-branch basis.

## 🌍 **Key Features**

### **Branch-Specific Location Data**
- **Custom Name**: Descriptive name for the location
- **Address**: Physical address of the branch location
- **GPS Coordinates**: Latitude and longitude for precise positioning
- **Geofencing Radius**: Configurable radius in meters for attendance validation
- **Per-Branch Customization**: Each branch can have unique location settings

### **Location Data Structure**
```json
{
  "branch_locations": {
    "branch-uuid-1": {
      "name": "Downtown Office",
      "address": "123 Main St, Downtown, City 12345",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "radius": 100
    },
    "branch-uuid-2": {
      "name": "Airport Branch",
      "address": "456 Airport Blvd, Terminal 2, City 54321",
      "latitude": 40.6892,
      "longitude": -74.1745,
      "radius": 200
    }
  }
}
```

## 🚀 **How to Use Branch Locations**

### **1. Create Constraint with Branch Locations**

#### API Request
```http
POST /api/v1/attendance/constraints
Content-Type: application/json
Authorization: Bearer {jwt_token}

{
  "constraint_name": "Multi-Branch Office Hours with Locations",
  "constraint_type": "time_multiple_periods",
  "branch_ids": [
    "branch-uuid-1",
    "branch-uuid-2",
    "branch-uuid-3"
  ],
  "branch_locations": {
    "branch-uuid-1": {
      "name": "Downtown Headquarters",
      "address": "123 Business Ave, Downtown, NY 10001",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "radius": 50
    },
    "branch-uuid-2": {
      "name": "Uptown Branch Office",
      "address": "789 Corporate Blvd, Uptown, NY 10002",
      "latitude": 40.7831,
      "longitude": -73.9712,
      "radius": 75
    },
    "branch-uuid-3": {
      "name": "Remote Work Hub",
      "address": "456 Co-working St, Brooklyn, NY 11201",
      "latitude": 40.6892,
      "longitude": -73.9442,
      "radius": 100
    }
  },
  "constraint_config": {
    "weekly_schedule": {
      "monday": {
        "enabled": true,
        "periods": [
          {
            "name": "Standard Hours",
            "start_time": "09:00",
            "end_time": "17:00",
            "spans_next_day": false,
            "grace_period_before": 15,
            "grace_period_after": 15
          }
        ]
      }
      // ... other days
    }
  },
  "is_active": true
}
```

### **2. Update Branch Locations**

#### Update Specific Branch Location
```http
PUT /api/v1/attendance/constraints/{constraint-id}
{
  "branch_locations": {
    "branch-uuid-1": {
      "name": "New Downtown Office",
      "address": "999 Updated St, Downtown, NY 10001",
      "latitude": 40.7200,
      "longitude": -74.0100,
      "radius": 80
    }
  }
}
```

#### Add Location to New Branch
```http
PUT /api/v1/attendance/constraints/{constraint-id}
{
  "branch_ids": ["branch-uuid-1", "branch-uuid-2", "branch-uuid-4"],
  "branch_locations": {
    "branch-uuid-4": {
      "name": "New Satellite Office",
      "address": "321 Expansion Ave, Queens, NY 11101",
      "latitude": 40.7282,
      "longitude": -73.7949,
      "radius": 60
    }
  }
}
```

### **3. Programmatic Management**

#### Set Branch Location
```php
use Modules\Attendance\Models\AttendanceConstraint;

$constraint = AttendanceConstraint::find($constraintId);

// Set location for a specific branch
$constraint->setBranchLocation('branch-uuid-1', [
    'name' => 'Main Office',
    'address' => '123 Business St, City, State 12345',
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'radius' => 100
]);

// Get location for a branch
$location = $constraint->getBranchLocation('branch-uuid-1');
if ($location) {
    echo "Branch location: {$location['name']} at {$location['address']}";
}

// Check if branch has custom location
if ($constraint->hasBranchLocation('branch-uuid-1')) {
    echo "Branch has custom location settings";
}

// Set multiple locations at once
$constraint->setBranchLocations([
    'branch-uuid-1' => [
        'name' => 'HQ Office',
        'address' => '123 Main St',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'radius' => 50
    ],
    'branch-uuid-2' => [
        'name' => 'Branch Office',
        'address' => '456 Side St',
        'latitude' => 40.7500,
        'longitude' => -73.9800,
        'radius' => 75
    ]
]);
```

## 📍 **Real-World Examples**

### **Example 1: Restaurant Chain with Different Locations**

```php
// Create constraint for restaurant chain with specific locations
$restaurantConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'constraint_name' => 'Restaurant Chain Locations',
    'constraint_type' => 'time_multiple_periods',
    'branch_ids' => [
        'restaurant-downtown-uuid',
        'restaurant-mall-uuid',
        'restaurant-airport-uuid'
    ],
    'branch_locations' => [
        'restaurant-downtown-uuid' => [
            'name' => 'Downtown Restaurant',
            'address' => '123 Food St, Downtown, City 12345',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 30  // Small radius for city location
        ],
        'restaurant-mall-uuid' => [
            'name' => 'Shopping Mall Food Court',
            'address' => '456 Mall Blvd, Shopping Center, City 54321',
            'latitude' => 40.7500,
            'longitude' => -73.9800,
            'radius' => 100  // Larger radius for mall complex
        ],
        'restaurant-airport-uuid' => [
            'name' => 'Airport Terminal Restaurant',
            'address' => '789 Airport Way, Terminal B, City 98765',
            'latitude' => 40.6892,
            'longitude' => -74.1745,
            'radius' => 200  // Large radius for airport complex
        ]
    ],
    'constraint_config' => MultiplePeriodsConfig::restaurantServiceHours()->toArray(),
    'is_active' => true
]);
```

### **Example 2: Security Company with 24/7 Sites**

```php
// Security sites with precise location requirements
$securityConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'constraint_name' => '24/7 Security Sites',
    'constraint_type' => 'time_multiple_periods',
    'branch_ids' => [
        'security-bank-uuid',
        'security-hospital-uuid',
        'security-warehouse-uuid'
    ],
    'branch_locations' => [
        'security-bank-uuid' => [
            'name' => 'First National Bank',
            'address' => '100 Financial St, Banking District, City 11111',
            'latitude' => 40.7080,
            'longitude' => -74.0090,
            'radius' => 25  // Very precise for high-security location
        ],
        'security-hospital-uuid' => [
            'name' => 'General Hospital Security',
            'address' => '200 Health Ave, Medical Center, City 22222',
            'latitude' => 40.7200,
            'longitude' => -73.9900,
            'radius' => 50  // Medium radius for hospital campus
        ],
        'security-warehouse-uuid' => [
            'name' => 'Distribution Center',
            'address' => '300 Logistics Blvd, Industrial Zone, City 33333',
            'latitude' => 40.6800,
            'longitude' => -74.1200,
            'radius' => 150  // Large radius for warehouse complex
        ]
    ],
    'constraint_config' => MultiplePeriodsConfig::securityShifts()->toArray(),
    'is_active' => true
]);
```

### **Example 3: Office Buildings with Flexible Work Locations**

```php
// Office locations with different work arrangements
$officeConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'constraint_name' => 'Flexible Office Locations',
    'constraint_type' => 'time_multiple_periods',
    'branch_ids' => [
        'office-hq-uuid',
        'office-coworking-uuid',
        'office-satellite-uuid'
    ],
    'branch_locations' => [
        'office-hq-uuid' => [
            'name' => 'Corporate Headquarters',
            'address' => '1000 Corporate Plaza, Business District, City 10001',
            'latitude' => 40.7590,
            'longitude' => -73.9845,
            'radius' => 40
        ],
        'office-coworking-uuid' => [
            'name' => 'WeWork Coworking Space',
            'address' => '500 Innovation St, Tech Hub, City 20002',
            'latitude' => 40.7400,
            'longitude' => -74.0200,
            'radius' => 80  // Larger radius for shared space
        ],
        'office-satellite-uuid' => [
            'name' => 'Satellite Office',
            'address' => '250 Remote Work Ave, Suburb, City 30003',
            'latitude' => 40.6950,
            'longitude' => -73.9600,
            'radius' => 60
        ]
    ],
    'constraint_config' => MultiplePeriodsConfig::flexibleOfficeHours()->toArray(),
    'is_active' => true
]);
```

## 🔧 **Location Management Methods**

### **Model Helper Methods**
```php
// Set location for specific branch
$constraint->setBranchLocation($branchId, $locationData);

// Get location for specific branch
$location = $constraint->getBranchLocation($branchId);

// Remove location for specific branch
$constraint->removeBranchLocation($branchId);

// Get all branch locations
$allLocations = $constraint->getAllBranchLocations();

// Set multiple locations at once
$constraint->setBranchLocations($locationsArray);

// Check if branch has location
$hasLocation = $constraint->hasBranchLocation($branchId);
```

### **Location Data Validation**
```php
// Validation rules applied automatically
$locationRules = [
    'name' => 'required|string|max:255',
    'address' => 'nullable|string|max:500',
    'latitude' => 'nullable|numeric|between:-90,90',
    'longitude' => 'nullable|numeric|between:-180,180',
    'radius' => 'nullable|integer|min:1|max:10000'  // meters
];
```

## 📱 **Mobile App Integration**

### **Geofencing Validation**
```javascript
// Example mobile app geofencing check
function validateAttendanceLocation(userLat, userLng, branchLocation) {
    const distance = calculateDistance(
        userLat, userLng,
        branchLocation.latitude, branchLocation.longitude
    );
    
    return distance <= branchLocation.radius;
}

// Usage in attendance check-in
const constraint = await getConstraintForBranch(branchId);
const branchLocation = constraint.branch_locations[branchId];

if (branchLocation) {
    const isWithinRange = validateAttendanceLocation(
        currentLat, currentLng, branchLocation
    );
    
    if (!isWithinRange) {
        throw new Error(`You must be within ${branchLocation.radius}m of ${branchLocation.name} to check in`);
    }
}
```

## 🎯 **Use Cases and Benefits**

### **✅ Advantages**
1. **Precise Location Control**: Set exact GPS coordinates and radius for each branch
2. **Flexible Geofencing**: Different radius requirements per location type
3. **Address Tracking**: Store physical addresses for reference and navigation
4. **Location Naming**: Custom names for easy identification
5. **Mobile Integration**: Ready for GPS-based attendance validation
6. **Audit Trail**: Track where employees are checking in from

### **📊 Location Types and Recommended Radii**

| Location Type | Typical Radius | Use Case |
|---------------|----------------|----------|
| Small Office | 20-50m | Precise indoor location |
| Large Office Building | 50-100m | Multi-floor buildings |
| Shopping Mall | 100-200m | Large retail complexes |
| Hospital Campus | 100-300m | Medical facility complexes |
| Airport Terminal | 200-500m | Large transportation hubs |
| Warehouse/Factory | 150-400m | Industrial facilities |
| University Campus | 300-1000m | Educational institutions |
| Construction Site | 100-500m | Temporary work locations |

## 🔒 **Security and Privacy**

### **Location Data Protection**
- GPS coordinates stored securely in encrypted database
- Access controlled by user permissions
- Location data used only for attendance validation
- Compliance with privacy regulations (GDPR, CCPA)

### **Geofencing Best Practices**
- Set appropriate radius based on location type
- Consider GPS accuracy limitations (±3-5 meters)
- Account for building structure interference
- Test location boundaries before deployment
- Provide clear communication to employees about location requirements

## 📚 **API Documentation**

### **Location Data Structure**
```json
{
  "name": "string (required, max: 255)",
  "address": "string (optional, max: 500)",
  "latitude": "number (optional, -90 to 90)",
  "longitude": "number (optional, -180 to 180)",
  "radius": "integer (optional, 1 to 10000 meters)"
}
```

### **Validation Rules**
- **Name**: Required, descriptive location name
- **Address**: Optional, physical address for reference
- **Latitude**: Optional, GPS coordinate (-90 to 90)
- **Longitude**: Optional, GPS coordinate (-180 to 180)
- **Radius**: Optional, geofencing radius in meters (1-10000)

### **Error Responses**
```json
{
  "error": "validation_failed",
  "message": "The given data was invalid.",
  "errors": {
    "branch_locations.branch-uuid-1.latitude": [
      "The latitude must be between -90 and 90."
    ],
    "branch_locations.branch-uuid-1.radius": [
      "The radius must be at least 1 meter."
    ]
  }
}
```

---

This branch locations feature provides powerful geofencing capabilities for attendance constraints, enabling precise location-based attendance validation while maintaining flexibility for different types of work environments.
