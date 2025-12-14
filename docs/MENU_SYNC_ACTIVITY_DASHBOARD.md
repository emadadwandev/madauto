# Menu Sync Activity Dashboard - Implementation Summary

## Overview
Added comprehensive menu sync activity logging to the dashboard. The system now tracks all menu sync operations (to Careem/Talabat) and displays them alongside order sync logs in a unified activity feed.

## Changes Made

### 1. Database Schema
**Created**: `menu_sync_logs` table with the following columns:
- `id` - Primary key
- `tenant_id` - Tenant association (for multi-tenancy)
- `menu_id` - Foreign key to menus table
- `platform` - Platform name (careem, talabat)
- `action` - Action type (sync_started, sync_completed, sync_failed)
- `status` - Current status (pending, processing, success, failed)
- `message` - Human-readable status message
- `metadata` - JSON field for storing additional data (catalog_id, brand_id, errors, etc.)
- `created_at` / `updated_at` - Timestamps

**Indexes added**:
- `(tenant_id, menu_id)` - For tenant-specific menu queries
- `(platform, status)` - For filtering by platform and status
- `created_at` - For chronological ordering

### 2. MenuSyncLog Model
**File**: `app/Models/MenuSyncLog.php`

**Features**:
- Uses `HasTenant` trait for multi-tenant isolation
- Relationship with `Menu` model
- Helper methods:
  - `getPlatformBadgeColorAttribute()` - Returns color class for platform badges
  - `getStatusBadgeColorAttribute()` - Returns color class for status badges
- Automatic JSON casting for metadata

### 3. Job Logging (SyncMenuToPlatformJob)
**File**: `app/Jobs/SyncMenuToPlatformJob.php`

**Logging Flow**:
1. **On Start**: Creates log with status `pending` and action `sync_started`
2. **During Processing**: Updates status to `processing`
3. **On Success**: Updates to status `success`, action `sync_completed`, stores result metadata
4. **On Failure**: Updates to status `failed`, action `sync_failed`, stores error details

**Metadata Stored**:
- `attempt` - Current retry attempt number
- `queue` - Queue name used
- `platform_menu_id` - Catalog ID or request ID from platform
- `error` - Error message if failed
- `error_code` - Error code if available
- `is_retryable` - Whether error is retryable
- `result` - Full API response data

### 4. Dashboard Controller
**File**: `app/Http/Controllers/Dashboard/DashboardController.php`

**Changes**:
- Added `MenuSyncLog` model import
- Fetches both order sync logs and menu sync logs (10 each)
- Adds `sync_type` property to distinguish between order and menu logs
- Merges both collections, sorts by `created_at` descending
- Takes top 10 most recent logs
- Passes unified `$recentLogs` collection to view

### 5. Dashboard View
**File**: `resources/views/dashboard/index.blade.php`

**UI Updates**:
- Detects log type via `sync_type` property
- **For Order Syncs**:
  - Shows platform badge (Careem/Talabat)
  - Shows "Order" badge
  - Displays order ID
- **For Menu Syncs**:
  - Shows platform badge (Careem/Talabat)
  - Shows "Menu" badge (purple)
  - Displays menu name
  - Shows status badge (Pending/Processing/Success/Failed) with appropriate colors
- Left border color: Green for success, Red for failure
- Shows message and relative timestamp for all log types

## Visual Indicators

### Platform Badges
- **Careem**: Blue badge (`bg-blue-100 text-blue-800`)
- **Talabat**: Orange badge (`bg-orange-100 text-orange-800`)

### Type Badges
- **Order**: Gray badge (`bg-gray-100 text-gray-800`)
- **Menu**: Purple badge (`bg-purple-100 text-purple-800`)

### Status Badges (Menu Only)
- **Pending**: Yellow (`bg-yellow-100 text-yellow-800`)
- **Processing**: Blue (`bg-blue-100 text-blue-800`)
- **Success**: Green (`bg-green-100 text-green-800`)
- **Failed**: Red (`bg-red-100 text-red-800`)

### Border Colors
- **Success**: Green left border (`border-green-500`)
- **Failed**: Red left border (`border-red-500`)

## How It Works

1. User clicks "Sync" button on a menu card
2. `MenuController::sync()` dispatches `SyncMenuToPlatformJob`
3. Job creates initial log entry (pending)
4. Job attempts menu sync to platform
5. Job updates log with success/failure status and detailed metadata
6. Dashboard fetches latest 10 logs from both tables
7. Logs are merged, sorted, and displayed in unified feed
8. User sees real-time sync activity with clear visual indicators

## Testing

### To Test Menu Sync Logging:
1. Navigate to Menus page
2. Click "Sync" button on any menu
3. Queue worker processes the job
4. Navigate to Dashboard
5. Check "Recent Sync Activity" section
6. Should see menu sync log with:
   - Platform badge (Careem/Talabat)
   - "Menu" type badge
   - Menu name
   - Status badge
   - Sync message
   - Timestamp

### Queue Worker
Ensure queue workers are running:
```bash
php artisan queue:work database --queue=platform-sync,high,default
```

Or use the provided script:
```bash
.\run-queue-worker.bat  # Windows
./run-queue-worker.sh   # Linux/Mac
```

## Benefits

1. **Unified Activity Feed**: See both order syncs and menu syncs in one place
2. **Real-time Monitoring**: Track sync operations as they happen
3. **Error Visibility**: Immediately see if syncs fail and why
4. **Audit Trail**: Complete history of all sync operations
5. **Multi-tenant Safe**: All logs are tenant-isolated
6. **Performance**: Indexed queries for fast retrieval
7. **Extensible**: Easy to add more platforms (e.g., Deliveroo, Uber Eats)

## Future Enhancements

- Add filtering by platform/status/type
- Add retry button for failed syncs
- Add bulk sync operations
- Add sync analytics/charts
- Add webhook notifications for sync status changes
- Add detailed sync log view with full metadata
