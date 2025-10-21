# Project Context - Careem Now to Loyverse POS Integration

## Project Overview

This project is a web service that integrates **Careem Now** (food delivery platform) with **Loyverse POS** (Point of Sale system). The service automatically creates orders in Loyverse POS when new orders are placed on the Careem Now platform.

## Business Problem

Currently, when orders are placed on Careem Now, they need to be manually entered into the Loyverse POS system. This manual process is:
- Time-consuming
- Error-prone
- Inefficient for high-volume operations
- Delays order processing

This integration service solves this problem by automatically synchronizing orders in real-time.

## Technical Solution

### Architecture Overview

```
Careem Now Platform → Webhook → Laravel Service → Queue → Loyverse POS API
                                      ↓
                                 MySQL Database
                                      ↓
                              Admin Dashboard (WebView)
```

### Technology Stack

#### Backend
- **Framework**: Laravel 12.33
- **Database**: MySQL
- **Real-time Communication**: Laravel Echo
- **Queue System**: Laravel Queue
- **PHP Version**: 8.2+ (recommended for Laravel 12+)

#### Frontend (WebView)
- **Template Engine**: Laravel Blade
- **CSS Framework**: Tailwind CSS
- **JavaScript Framework**: Alpine.js
- **Real-time Updates**: Laravel Echo Client

#### Infrastructure
- **Queue Driver**: Redis (recommended) or Database
- **Broadcasting**: Pusher or Redis
- **Cache**: Redis (recommended)
- **Session**: Redis or Database

### Integration Flow

1. **Order Creation on Careem Now**
   - Customer places order on Careem Now platform
   - Careem Now sends webhook to our service

2. **Webhook Reception**
   - Service receives webhook with order details
   - Validates webhook signature/authentication
   - Logs webhook payload

3. **Order Processing**
   - Transforms Careem Now order format
   - Validates order data
   - Queues order for processing

4. **Queue Processing**
   - Worker picks up queued job
   - Maps order data to Loyverse format
   - Sets customer name to "Careem"

5. **Loyverse API Integration**
   - Sends order to Loyverse POS API
   - Handles API response
   - Updates order status
   - Broadcasts status update via Echo

6. **Real-time Dashboard Update**
   - Admin dashboard receives broadcast
   - Updates order status in real-time
   - Shows sync status and logs

### Data Flow

#### Careem Now Order Structure (Expected)
```json
{
  "order_id": "string",
  "customer": {
    "name": "string",
    "phone": "string",
    "address": "string"
  },
  "items": [
    {
      "product_id": "string",
      "name": "string",
      "quantity": number,
      "price": number
    }
  ],
  "total": number,
  "created_at": "timestamp"
}
```

#### Loyverse Order Structure (Target)
```json
{
  "customer_name": "Careem",
  "line_items": [
    {
      "item_id": "string",
      "quantity": number,
      "price": number
    }
  ],
  "total_money": number,
  "created_at": "timestamp"
}
```

## Key Features

### 1. Automatic Order Synchronization
- Real-time webhook processing
- Automatic order creation in Loyverse
- All orders attributed to customer "Careem"

### 2. Queue Management
- Asynchronous processing
- Retry mechanism for failed orders
- Queue monitoring and management

### 3. Admin Dashboard
- View all orders and sync status
- Monitor webhook logs
- Manually retry failed syncs
- Real-time updates
- Search and filter orders

### 4. Error Handling
- Automatic retry for failed API calls
- Detailed error logging
- Alert system for critical failures
- Manual intervention capabilities

### 5. Real-time Updates
- WebSocket-based status updates
- Live order tracking
- Instant sync status notifications

## Database Schema

### Tables

#### `orders`
- `id` - Primary key
- `careem_order_id` - Unique order ID from Careem
- `order_data` - JSON field with full order details
- `status` - pending/processing/synced/failed
- `created_at`, `updated_at`

#### `loyverse_orders`
- `id` - Primary key
- `order_id` - Foreign key to orders
- `loyverse_order_id` - Order ID from Loyverse
- `loyverse_receipt_number` - Receipt number
- `sync_status` - success/failed
- `sync_response` - JSON field with API response
- `synced_at`
- `created_at`, `updated_at`

#### `sync_logs`
- `id` - Primary key
- `order_id` - Foreign key to orders
- `action` - Type of action performed
- `status` - success/failed
- `message` - Log message
- `metadata` - JSON field with additional data
- `created_at`

#### `webhook_logs`
- `id` - Primary key
- `payload` - JSON field with webhook data
- `headers` - JSON field with request headers
- `status` - received/processed/failed
- `processed_at`
- `created_at`

#### `api_credentials`
- `id` - Primary key
- `service` - Service name (loyverse/careem)
- `credentials` - Encrypted JSON field
- `is_active` - Boolean
- `created_at`, `updated_at`

## Security Considerations

### 1. Webhook Security
- Signature verification
- IP whitelist (optional)
- Rate limiting

### 2. API Credentials
- Encrypted storage
- Environment variables
- Rotation policy

### 3. Authentication
- Admin dashboard login
- API token authentication
- Role-based access control (if needed)

### 4. Data Protection
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

## Performance Considerations

### 1. Queue Processing
- Multiple queue workers for scalability
- Job prioritization
- Timeout handling

### 2. Database Optimization
- Proper indexing
- Query optimization
- Connection pooling

### 3. Caching
- API response caching (where applicable)
- Configuration caching
- Route caching

### 4. API Rate Limiting
- Respect Loyverse API limits
- Implement backoff strategy
- Monitor API usage

## Error Handling Strategy

### 1. Webhook Errors
- Log all webhook payloads
- Return appropriate HTTP status codes
- Alert on repeated failures

### 2. Queue Errors
- Automatic retry with exponential backoff
- Move to failed jobs after max attempts
- Manual retry capability

### 3. API Errors
- Handle different error types (network, auth, validation)
- Retry transient errors
- Alert on persistent errors

### 4. Database Errors
- Transaction management
- Connection error handling
- Deadlock detection and retry

## Monitoring & Alerting

### Key Metrics to Monitor
- Order processing rate
- Queue depth and processing time
- Failed job count
- API response times
- Webhook reception rate
- Error rates

### Alert Conditions
- Queue depth exceeds threshold
- Failed jobs exceed threshold
- API errors exceed threshold
- Webhook authentication failures
- Critical system errors

## Integration Requirements

### Careem Now Requirements
- Webhook URL endpoint
- Webhook authentication method
- Expected payload format
- Retry policy

### Loyverse POS Requirements
- API credentials (API key/OAuth)
- API endpoint URLs
- Rate limits
- Supported operations

## Development Principles

### 1. Code Quality
- Follow Laravel best practices
- Use service classes for business logic
- Implement repositories for data access
- Write comprehensive tests

### 2. Maintainability
- Clear and consistent naming
- Comprehensive documentation
- Modular architecture
- Version control

### 3. Scalability
- Design for horizontal scaling
- Use queues for async processing
- Optimize database queries
- Cache where appropriate

### 4. Reliability
- Implement comprehensive error handling
- Use transactions where needed
- Log all important operations
- Monitor system health

## Deployment Environment

### Production Requirements
- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Supervisor (for queue workers)
- SSL certificate
- Domain/subdomain

### Server Configuration
- Web server: Nginx or Apache
- PHP-FPM
- Queue workers (via Supervisor)
- Laravel Echo server
- Cron jobs for scheduled tasks

## Future Enhancements (Nice to Have)

1. **Order Status Updates**
   - Sync order status changes back to Careem

2. **Multi-tenant Support**
   - Support multiple restaurants/branches

3. **Analytics Dashboard**
   - Order statistics
   - Performance metrics
   - Revenue tracking

4. **Advanced Error Recovery**
   - Auto-resolution of common errors
   - Smart retry strategies

5. **API Webhooks**
   - Allow external systems to receive notifications

6. **Mobile App**
   - Mobile dashboard for monitoring

## Contact & Support

### Development Team
- **Project**: Careem Now - Loyverse Integration
- **Repository**: [To be determined]
- **Documentation**: See instruction.md for development guidelines

### API Documentation
- **Careem Now API**: https://docs.careemnow.com/
- **Loyverse API**: https://developer.loyverse.com/

## Glossary

- **Careem Now**: Food delivery platform
- **Loyverse POS**: Point of Sale system
- **Webhook**: HTTP callback for event notifications
- **Queue**: Asynchronous job processing system
- **Laravel Echo**: WebSocket wrapper for Laravel
- **Blade**: Laravel's templating engine
- **Alpine.js**: Lightweight JavaScript framework
- **Tailwind CSS**: Utility-first CSS framework
