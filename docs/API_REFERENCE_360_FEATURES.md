# 360-Degree Feedback System API Reference

## Overview
This comprehensive API reference documents all endpoints for the 360-degree performance management system enhancements, including Self-Assessment, Kudos System, Upward Feedback, OKR Management, and Individual Development Plans (IDPs).

## API Conventions

### Base URL
```
https://your-domain.com/api/360/
```

### Authentication
```
Authorization: Bearer {jwt_token}
```

### Response Format
All responses are JSON-encoded with consistent structure:
```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

### Error Handling
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid request parameters",
    "details": ["Name is required"]
  }
}
```

## Self-Assessment API Reference

### Core Endpoints

#### Create Self-Assessment
**Endpoint:** `POST /api/360/self-assessment/create`

**Authentication:** Required

**Request Body:**
```json
{
  "config_id": 1,
  "responses": {
    "competencies": {
      "communication": {
        "self_rating": 4,
        "evidence": "Led 3 team meetings",
        "improvement_areas": ["presentation"]
      }
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "response_id": 123,
    "status": "created",
    "created_at": "2024-11-15T10:30:00Z"
  }
}
```

#### Update Self-Assessment
**Endpoint:** `POST /api/360/self-assessment/update`

**Request Body:**
```json
{
  "response_id": 123,
  "responses": { /* updated data */ },
  "status": "draft"
}
```

#### Get Self-Assessment Details
**Endpoint:** `GET /api/360/self-assessment/{response_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "response_id": 123,
    "employee": { "id": 456, "name": "John Doe" },
    "responses": { ... },
    "status": "submitted",
    "submitted_at": "2024-11-15T10:30:00Z"
  }
}
```

## Kudos System API Reference

### Give Kudos
**Endpoint:** `POST /api/360/kudos/give`

**Request Body:**
```json
{
  "recipient_id": 789,
  "category_id": 2,
  "message": "Excellent work on Q4 launch!",
  "is_public": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "kudos_id": 987,
    "points_awarded": 15,
    "recipient_notification_sent": true
  }
}
```

### Get Kudos Leaderboard
**Endpoint:** `GET /api/360/kudos/leaderboard`

**Query Parameters:**
- `type`: received|given|points (default: received)
- `period`: day|week|month|year (default: month)

**Response:**
```json
{
  "success": true,
  "data": {
    "leaderboard": [
      {
        "employee": { "id": 123, "name": "Alice Smith" },
        "received": 45,
        "total_points": 450
      }
    ],
    "period": "month"
  }
}
```

## OKR Management API Reference

### Create Objective
**Endpoint:** `POST /api/360/okr/create`

**Request Body:**
```json
{
  "title": "Improve Customer Satisfaction",
  "description": "Focus on customer support response times",
  "key_results": [
    {
      "title": "Reduce response time to under 2 hours",
      "target_value": 120,
      "unit": "minutes"
    }
  ]
}
```

### Update Key Result Progress
**Endpoint:** `POST /api/360/okr/key-result/update`

**Request Body:**
```json
{
  "key_result_id": 456,
  "current_value": 125,
  "notes": "Implemented faster queue system"
}
```

## Upward Feedback API Reference

### Initiate Feedback
**Endpoint:** `POST /api/360/upward-feedback/initiate`

**Request Body:**
```json
{
  "manager_id": 789,
  "anonymous": true,
  "period_id": 12
}
```

### Submit Feedback
**Endpoint:** `POST /api/360/upward-feedback/submit`

**Request Body:**
```json
{
  "session_id": 101,
  "competency_ratings": {
    "leadership": {"rating": 4, "comment": "Great direction setting"}
  },
  "overall_rating": 4.5
}
```

## IDP System API Reference

### Create Development Plan
**Endpoint:** `POST /api/360/idp/create`

**Request Body:**
```json
{
  "title": "Leadership Development Journey",
  "description": "Build management skills over 6 months",
  "target_date": "2025-05-15",
  "competencies": ["leadership", "strategic_thinking"],
  "milestones": [
    {
      "title": "Complete Leadership Workshop",
      "milestone_type": "training",
      "deadline": "2024-12-31"
    }
  ]
}
```

## Error Code Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 400 | Invalid request data |
| `AUTHENTICATION_FAILED` | 401 | Invalid credentials |
| `PERMISSION_DENIED` | 403 | Insufficient permissions |
| `RESOURCE_NOT_FOUND` | 404 | Requested resource not found |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_SERVER_ERROR` | 500 | Server-side error |

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| Create assessments | 10/hour | 1 hour |
| Send kudos | 50/hour | 1 hour |
| Update progress | 100/hour | 1 hour |
| API total | 500/hour | 1 hour |

## SDK Examples

### JavaScript (Fetch API)
```javascript
// Give kudos
const response = await fetch('/api/360/kudos/give', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    recipient_id: 789,
    category_id: 2,
    message: "Excellent work!"
  })
});

const data = await response.json();
```

### Python (requests)
```python
import requests

# Update OKR progress
url = '/api/360/okr/key-result/update'
headers = {'Authorization': f'Bearer {token}'}
data = {
    'key_result_id': 456,
    'current_value': 125
}

response = requests.post(url, json=data, headers=headers)
result = response.json()
```

### cURL Examples
```bash
# Get self-assessment details
curl -X GET \
  'https://your-domain.com/api/360/self-assessment/123' \
  -H 'Authorization: Bearer your-jwt-token'

# Submit upward feedback
curl -X POST \
  'https://your-domain.com/api/360/upward-feedback/submit' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer your-jwt-token' \
  -d '{
    "session_id": 101,
    "competency_ratings": {
      "leadership": {"rating": 4, "comment": "Great leadership"}
    }
  }'
```

## Testing and Validation

### Health Check
**Endpoint:** `GET /api/360/system/health`

**Response:**
```json
{
  "success": true,
  "data": {
    "database": "connected",
    "redis": "connected",
    "memory_usage": "65%",
    "uptime": "99.9%"
  }
}
```

### Test Endpoints
**Base:** `GET /api/360/test/{endpoint-name}`

**Available tests:**
- `/api/360/test/self-assessment`: Test self-assessment flow
- `/api/360/test/kudos`: Test kudos system
- `/api/360/test/okr`: Test OKR management
- `/api/360/test/upward-feedback`: Test upward feedback
- `/api/360/test/idp`: Test development plan system

## Change Log
| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-11-15 | Initial API release |
| 1.1.0 | 2024-12-01 | Added analytics endpoints |
| 1.2.0 | 2024-12-15 | Added batch operations |
| 1.3.0 | 2025-01-01 | Added mobile-optimized endpoints