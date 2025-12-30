# API Documentation

## Base URL
```
https://yourdomain.com/wp-json/lgp/v1
```

## Authentication

All endpoints require WordPress nonce authentication:
```bash
X-WP-Nonce: {nonce}
Content-Type: application/json
```

## Endpoints

### Companies

#### List Companies
```
GET /companies
```

#### Get Company
```
GET /companies/{id}
```

#### Create Company
```
POST /companies
{
  "name": "Company Name",
  "contact_email": "email@example.com"
}
```

### Units

#### List Units
```
GET /units?company_id={id}
```

#### Get Unit
```
GET /units/{id}
```

### Tickets

#### List Tickets
```
GET /tickets?status=open&priority=urgent
```

#### Get Ticket
```
GET /tickets/{id}
```

#### Create Ticket
```
POST /tickets
{
  "type": "install",
  "priority": "normal",
  "title": "Ticket Title",
  "description": "Details here"
}
```

#### Update Ticket
```
PUT /tickets/{id}
{
  "status": "closed"
}
```

### Service Requests

#### List Requests
```
GET /service-requests
```

#### Create Request
```
POST /service-requests
{
  "company_id": 1,
  "type": "maintenance",
  "description": "Details"
}
```

---

For more details, see [DEPLOYMENT.md](DEPLOYMENT.md)
