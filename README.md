# British School API v2

A fresh Laravel API backend for the British School Management System.

## Features

### 🎓 Student Management
- **Student Registration**: Create and manage student records
- **Student Profiles**: View detailed student information
- **Class Management**: Organize students by levels, streams, and classes

### 📊 Attendance System
- **Real-time Scanning**: Support for ID cards, fingerprint, face recognition
- **Attendance Roster**: Comprehensive view of all attendance records
- **Statistics**: Track present, late, and absent rates
- **Export**: CSV export functionality for reporting

### 💰 Fee Management
- **Fee Groups**: Create and manage different fee categories
- **Fee Types**: Support for mandatory, optional, and one-time fees
- **Fee Tracking**: Monitor fee collection and balances

### 📞 Front Office
- **Enquiries**: Manage prospective student inquiries
- **Lead Tracking**: Track conversion rates and follow-ups
- **Communication**: Log all communication with prospects

## API Endpoints

### Authentication
```
POST /api/auth/login      - User login
POST /api/auth/register   - User registration
POST /api/auth/logout     - User logout
GET  /api/auth/me        - Get current user
```

### Students
```
GET    /api/students              - List students (with filters)
POST   /api/students              - Create new student
GET    /api/students/{id}         - Get student details
PUT    /api/students/{id}         - Update student
DELETE /api/students/{id}         - Delete student
GET    /api/students/by-level-stream - Get by level and stream
```

### Attendance
```
GET    /api/attendance           - List attendance records (with filters)
POST   /api/attendance           - Create attendance record
GET    /api/attendance/{id}      - Get attendance details
PUT    /api/attendance/{id}      - Update attendance record
DELETE /api/attendance/{id}      - Delete attendance record
GET    /api/attendance/statistics - Get attendance statistics
GET    /api/attendance/export    - Export attendance data
```

### Enquiries (Front Office)
```
GET    /api/enquiries           - List enquiries (with filters)
POST   /api/enquiries           - Create new enquiry
GET    /api/enquiries/{id}      - Get enquiry details
PUT    /api/enquiries/{id}      - Update enquiry
DELETE /api/enquiries/{id}      - Delete enquiry
GET    /api/enquiries/statistics - Get enquiry statistics
```

### Fee Management
```
GET    /api/fees/groups        - List fee groups
POST   /api/fees/groups        - Create fee group
GET    /api/fees/groups/{id}   - Get fee group details
PUT    /api/fees/groups/{id}   - Update fee group
DELETE /api/fees/groups/{id}   - Delete fee group
```

## Database Schema

### Students Table
- Personal information (name, email, phone, etc.)
- Academic details (level, stream, class)
- Parent/guardian information
- Admission and status tracking

### Student Attendance Records Table
- Scan time and method tracking
- Device and attendance type logging
- Status management (present, late, absent)
- Performance indexes for fast queries

### Fee Groups Table
- Fee categorization and types
- Amount and status management
- Description and metadata

### School Enquiries Table
- Lead source tracking
- Status management (new, contacted, converted)
- Follow-up scheduling
- Communication logging

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 4. Start Development Server
```bash
php artisan serve
```

## Test Credentials

**Email**: test@example.com
**Password**: password

## API Features

### 🔒 Authentication
- Laravel Sanctum for token-based authentication
- Secure API endpoints
- User management

### 📝 Validation
- Comprehensive request validation
- Custom error messages
- Input sanitization

### 📊 Filtering & Pagination
- Advanced filtering on all list endpoints
- Pagination with metadata
- Search functionality

### 📤 Export Functionality
- CSV export for data analysis
- Filtered export support
- Downloadable reports

### 🚀 Performance
- Database indexes for fast queries
- Optimized relationships
- Efficient query patterns

## Response Format

All API responses follow this format:

### Success Response
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

### Paginated Response
```json
{
    "success": true,
    "data": [ ... ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 100,
        "last_page": 5
    }
}
```

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
php artisan pint
```

## Security

- Input validation on all endpoints
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting (recommended for production)

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Configure production database
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Set up web server (Apache/Nginx)
6. Configure SSL certificate
7. Set up monitoring and logging
