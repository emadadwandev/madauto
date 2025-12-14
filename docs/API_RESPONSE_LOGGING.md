# API Response Logging for Menu Sync

## Overview
Enhanced the menu sync logging system to capture complete API request/response details for debugging failed syncs. All API interactions are now logged to the `menu_sync_logs.metadata` JSON field.

## What's Logged

### Success Scenarios
When a menu sync succeeds, the following data is captured:

```json
{
  "attempt": 1,
  "queue": "platform-sync",
  "platform_menu_id": "catalog_123",
  "catalog_id": "catalog_123_456",
  "request_id": "req_abc123xyz",
  "api_response": {
    "success": true,
    "status": "accepted",
    "catalog_id": "catalog_123",
    "message": "Catalog submitted successfully",
    "http_status": 200,
    "raw_response": {
      // Full API response from Careem
    }
  },
  "status_check_response": {
    "status": "accepted",
    // Full status check response
  },
  "result": {
    // Complete result object from sync operation
  }
}
```

### Failure Scenarios
When a menu sync fails, additional error details are captured:

```json
{
  "attempt": 1,
  "queue": "platform-sync",
  "error": "Catalog submission failed: Invalid brand_id",
  "error_code": 400,
  "is_retryable": false,
  "api_response": {
    "status": 400,
    "body": {
      "error": "INVALID_BRAND_ID",
      "message": "The provided brand_id is not valid"
    },
    "raw_body": "..."
  },
  "error_file": "/path/to/file.php",
  "error_line": 123,
  "trace": "Full stack trace..."
}
```

## Code Changes

### 1. PlatformApiException Enhancement
**File**: `app/Exceptions/PlatformApiException.php`

Added ability to store and retrieve full API response:
- `setResponse(array $response)` - Store API response
- `getResponse()` - Retrieve stored response

### 2. CareemApiService Updates
**File**: `app/Services/CareemApiService.php`

Enhanced `submitCatalog()` method:
- Logs complete request details (URL, headers, payload size)
- Captures full HTTP response (status, headers, body)
- Returns enhanced result with `http_status` and `raw_response`
- Logs detailed error information including status text, headers, URL

### 3. SyncMenuToPlatformJob Improvements
**File**: `app/Jobs/SyncMenuToPlatformJob.php`

Enhanced logging at every stage:

**Before sync:**
```php
Log::info('Submitting catalog to Careem', [
    'menu_id' => $menu->id,
    'catalog_id' => $catalogId,
    'brand_id' => $brandId,
    'branch_id' => $branchId,
    'payload_size' => strlen(json_encode($catalogData)),
    'items_count' => count($catalogData['items'] ?? []),
    'categories_count' => count($catalogData['categories'] ?? []),
]);
```

**After API call:**
```php
Log::info('Careem API response received', [
    'menu_id' => $menu->id,
    'catalog_id' => $catalogId,
    'result' => $result,
]);
```

**On rejection:**
```php
Log::error('Careem catalog rejected', [
    'request_id' => $requestId,
    'status_result' => $statusResult,
    'submit_result' => $result,
]);
```

**Metadata stored includes:**
- `api_response` - Full API response
- `status_response` - Status check response
- `catalog_id` - Generated catalog ID
- `request_id` - Platform request ID
- `error` - Error message (if failed)
- `error_code` - HTTP status code (if failed)
- `error_file` & `error_line` - Exception location
- `trace` - Full stack trace (if failed)

### 4. View Updates
**File**: `resources/views/dashboard/sync-logs/show.blade.php`

Enhanced detail view with three new sections:

**API Response Section:**
- HTTP status badge (color-coded)
- Request ID
- Catalog ID
- Full JSON response (formatted, scrollable)

**Error Details Section (Failures Only):**
- Red-themed alert box
- Error message (highlighted)
- Error code
- Stack trace (collapsible)

**Enhanced Type Detection:**
- Detects order sync vs menu sync
- Shows appropriate badges and fields
- Platform badges for menu syncs

## How to View Logs

### Option 1: Dashboard
1. Go to Dashboard
2. Click on any sync log in "Recent Sync Activity"
3. View detailed log page with all API responses

### Option 2: Sync Logs Page
1. Navigate to **Sync Logs** from sidebar
2. Click "View" on any menu sync log
3. See complete API request/response details

### Option 3: Database Query
```sql
SELECT 
    id,
    menu_id,
    platform,
    status,
    message,
    JSON_PRETTY(metadata) as metadata,
    created_at
FROM menu_sync_logs
WHERE status = 'failed'
ORDER BY created_at DESC
LIMIT 10;
```

### Option 4: Laravel Logs
Check `storage/logs/laravel.log` for detailed timestamped logs:
```
[2025-12-14 10:30:15] local.INFO: Submitting catalog to Careem {"menu_id":1,"catalog_id":"catalog_1_456","brand_id":"brand123","branch_id":"branch456","payload_size":12345,"items_count":25,"categories_count":5}
[2025-12-14 10:30:16] local.INFO: Careem API response received {"menu_id":1,"catalog_id":"catalog_1_456","result":{...}}
```

## Debugging Failed Syncs

### Step 1: Identify the Failure
```sql
SELECT * FROM menu_sync_logs 
WHERE status = 'failed' 
ORDER BY created_at DESC 
LIMIT 1;
```

### Step 2: Check Metadata
```sql
SELECT 
    JSON_EXTRACT(metadata, '$.error') as error_message,
    JSON_EXTRACT(metadata, '$.error_code') as error_code,
    JSON_EXTRACT(metadata, '$.api_response') as api_response
FROM menu_sync_logs 
WHERE id = [LOG_ID];
```

### Step 3: Common Issues

**400 Bad Request:**
- Check `api_response.body` for validation errors
- Verify brand_id and branch_id are correct
- Ensure catalog data matches Careem's schema

**401 Unauthorized:**
- Token expired or invalid
- Check API credentials in Settings
- Verify client_id and client_secret

**429 Rate Limited:**
- Too many requests
- Job will automatically retry with backoff
- Check `is_retryable` flag

**500 Server Error:**
- Careem API issue
- Job will automatically retry
- Check `trace` for our side errors

### Step 4: View in Dashboard
1. Go to **Sync Logs** → Click failed log
2. Review **Error Details** section
3. Check **API Response** section for platform errors
4. Copy **Full Metadata** for support ticket

## Testing

### Manual Test Sync
1. Go to **Menus** page
2. Click **Sync** on any menu
3. Ensure queue worker is running
4. Check **Dashboard** for sync log
5. Click log to view detailed API response

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker output
.\run-queue-worker.bat
```

### Database Check
```bash
php artisan tinker
>>> MenuSyncLog::latest()->first()->metadata
```

## Benefits

1. **Complete Visibility**: Every API call is fully logged
2. **Debugging Made Easy**: See exact request/response for failures
3. **Audit Trail**: Track all sync attempts with timestamps
4. **Error Context**: Stack traces show exact failure points
5. **API Compliance**: Verify payloads match Careem requirements
6. **Performance Tracking**: Payload sizes and response times logged

## Future Enhancements

- [ ] Add retry button on failed sync detail page
- [ ] Export logs as JSON for support tickets
- [ ] Add search/filter by error type
- [ ] Add webhook for failed sync notifications
- [ ] Add sync analytics dashboard
- [ ] Add diff view for catalog changes
