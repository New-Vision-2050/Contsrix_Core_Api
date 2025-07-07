# API Documentation - Attendance Constraints Branch Locations

This directory contains comprehensive API documentation for the **Branch Locations** feature in the Employee Attendance Management System.

## 📁 Files

### 🔧 Postman Collection
**File**: `attendance-constraints-branch-locations.postman_collection.json`

Complete Postman collection with 9 pre-configured requests for testing the branch locations API:

1. **Create Constraint with Branch Locations** - Basic multi-branch constraint creation
2. **Update Constraint Branch Locations** - Full update with new locations
3. **Get Constraint with Branch Locations** - Retrieve constraint with location data
4. **List Constraints with Branch Locations** - List all constraints with optional location data
5. **Validate Branch Location Data** - Test validation errors with invalid data
6. **Create Restaurant Chain Locations** - Real-world restaurant chain example
7. **Create Security Company Locations** - High-security site example with precise boundaries
8. **Partial Update - Add New Branch Location** - PATCH request to add single location
9. **Clear All Branch Locations** - Remove all branch-specific locations

#### 🚀 Quick Setup
1. Import the collection into Postman
2. Set collection variables:
   - `base_url`: Your API base URL (default: `http://localhost:8000`)
   - `access_token`: Your JWT authentication token
3. Run the requests in sequence for complete testing

#### ✅ Test Coverage
- **Validation Testing**: Comprehensive error condition coverage
- **Real-world Examples**: Restaurant chain, security company, office building scenarios
- **CRUD Operations**: Create, read, update, delete operations
- **Edge Cases**: Invalid coordinates, missing fields, radius limits
- **Automated Assertions**: 25+ test assertions for response validation

### 📋 OpenAPI Specification
**File**: `attendance-constraints-branch-locations-openapi.yaml`

Complete OpenAPI 3.0 specification including:

- **Detailed Schemas**: BranchLocation, BranchLocations, request/response models
- **Comprehensive Examples**: 8+ real-world usage examples
- **Validation Rules**: GPS coordinate limits, radius constraints, required fields
- **Error Responses**: Detailed error handling documentation
- **Security**: JWT Bearer token authentication
- **Use Cases**: Restaurant chains, security companies, office buildings, healthcare facilities

#### 🎯 Key Features Documented
- **Custom Location Data**: Branch-specific GPS coordinates and radius settings
- **Geofencing Support**: Configurable radius from 1-10000 meters
- **Flexible Management**: Add, update, remove branch locations independently
- **Validation**: Comprehensive coordinate and data validation
- **Multi-tenant**: Company-level isolation and security

## 🏢 Use Cases Covered

### 1. **Restaurant Chain** 🍽️
- Downtown location: 25m radius (precise tracking)
- Mall location: 50m radius (moderate flexibility)
- Airport location: 100m radius (large terminal coverage)

### 2. **Security Company** 🔒
- Bank site: 10m radius (maximum precision)
- Warehouse: 200m radius (large facility coverage)
- Office building: 50m radius (building-level tracking)

### 3. **Office Building** 🏢
- Headquarters: 100m radius (building + parking)
- Co-working space: 150m radius (flexible workspace)
- Remote hub: Variable radius based on location type

### 4. **Healthcare Facility** 🏥
- Hospital campus: 500m radius (large campus coverage)
- Clinic: 30m radius (precise location tracking)
- Emergency services: 200m radius (facility + ambulance bay)

## 🔧 Technical Details

### Data Structure
```json
{
  "branch_locations": {
    "branch-uuid": {
      "name": "Location Name",
      "address": "Physical Address",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "radius": 50
    }
  }
}
```

### Validation Rules
- **Latitude**: -90 to 90 degrees
- **Longitude**: -180 to 180 degrees  
- **Radius**: 1 to 10,000 meters
- **Name**: Required, 1-255 characters
- **Address**: Optional, max 500 characters

### API Endpoints
- `POST /api/attendance/constraints` - Create with branch locations
- `GET /api/attendance/constraints` - List with optional location data
- `GET /api/attendance/constraints/{id}` - Get specific constraint
- `PUT /api/attendance/constraints/{id}` - Full update
- `PATCH /api/attendance/constraints/{id}` - Partial update
- `DELETE /api/attendance/constraints/{id}` - Delete constraint

## 🚀 Getting Started

1. **Import Postman Collection**:
   ```bash
   # Import the JSON file into Postman
   # Set your base_url and access_token variables
   ```

2. **View OpenAPI Docs**:
   ```bash
   # Use Swagger UI, Redoc, or any OpenAPI viewer
   # Load the YAML file for interactive documentation
   ```

3. **Test the API**:
   ```bash
   # Run the Postman collection tests
   # Verify all assertions pass
   # Check response data structure
   ```

## 📊 Testing Results

When running the complete Postman collection, you should see:
- ✅ **25+ Test Assertions** passing
- ✅ **9 API Requests** successful
- ✅ **Validation Testing** covering error cases
- ✅ **Real-world Examples** demonstrating practical usage
- ✅ **CRUD Operations** fully functional

## 🔗 Integration

This API documentation is ready for:
- **Frontend Integration**: React/Vue.js location management UI
- **Mobile Apps**: Flutter/React Native geofencing features
- **Third-party Systems**: Integration with HR and payroll systems
- **Monitoring Tools**: Location compliance and reporting dashboards

---

**Last Updated**: June 2024  
**API Version**: 1.0.0  
**Status**: Production Ready ✅
