# Operational Hours Sync to Careem

## Overview

The system now automatically syncs Location opening hours to Careem's operational hours API whenever a menu is synced to Careem.

## How It Works

### 1. Location Model Structure

The `Location` model stores opening hours in the `opening_hours` field as an array:

```php
[
    'monday' => ['open' => '11:00', 'close' => '23:00'],
    'tuesday' => ['open' => '11:00', 'close' => '02:00'], // Closes at 2 AM next day
    'wednesday' => ['open' => '09:00', 'close' => '22:00'],
    // ... other days
]
```

### 2. Transformation to Careem Format

The `CareemMenuTransformer::transformOperationalHours()` method converts Location hours to Careem's format:

**Input Format (Location):**
```php
'monday' => ['open' => '11:00', 'close' => '23:00']
```

**Output Format (Careem):**
```php
[
    ['day' => 'monday', 'start_at' => '11:00', 'end_at' => '23:00']
]
```

### 3. Handling Overnight Hours

When a location closes past midnight (e.g., 11:00 PM - 2:00 AM), the transformer automatically splits this into two shifts as required by Careem:

**Input:**
```php
'monday' => ['open' => '23:00', 'close' => '02:00']
```

**Output:**
```php
[
    ['day' => 'monday', 'start_at' => '23:00', 'end_at' => '23:59'],
    ['day' => 'tuesday', 'start_at' => '00:00', 'end_at' => '02:00']
]
```

### 4. Default 24/7 Hours

If no opening hours are configured, the system defaults to 24/7 operation:

```php
[
    ['day' => 'monday', 'start_at' => '00:00', 'end_at' => '23:59'],
    ['day' => 'tuesday', 'start_at' => '00:00', 'end_at' => '23:59'],
    // ... all days
]
```

## Integration with Menu Sync

### Automatic Sync

Operational hours are synced automatically during the `SyncMenuToPlatformJob` execution:

1. Menu is synced to Careem (catalog submission)
2. System checks if Location has `opening_hours` configured
3. If yes, transforms and syncs hours to Careem
4. Logs success or failure

### Error Handling

If operational hours sync fails, it **does not fail the entire menu sync**. The error is logged but the catalog sync continues.

```php
// Logs show operational hours sync status
Log::info('Operational hours synced successfully', [
    'menu_id' => $menu->id,
    'location_id' => $location->id,
    'shifts_count' => count($operationalHours),
]);
```

## API Details

### Careem Endpoint

- **Method:** PUT
- **URL:** `/operational-hours`
- **Headers:**
  - `Authorization: Bearer {token}`
  - `Brand-Id: {brand_id}`
  - `Branch-Id: {branch_id}`
  - `User-Agent: {user_agent}`

### Payload Structure

```json
{
  "operational_hours": [
    {
      "day": "monday",
      "start_at": "11:00",
      "end_at": "23:00"
    },
    {
      "day": "tuesday",
      "start_at": "11:00",
      "end_at": "23:59"
    },
    {
      "day": "wednesday",
      "start_at": "00:00",
      "end_at" : "02:00"
    }
  ]
}
```

### Careem API Requirements

1. **24-hour format:** Times must be in HH:MM format (e.g., "11:00", "23:59")
2. **No 00:00 closing:** `end_at` cannot be "00:00" (use "23:59" instead)
3. **Overnight shifts:** Must be split into two entries if spanning midnight
4. **Day names:** Lowercase day names (e.g., "monday", "tuesday")

## Logging

The system logs detailed information at each step:

### Transformation Logs

```
[INFO] Transforming opening hours to Careem operational hours format
[DEBUG] Added operational hours for monday (start_at: 11:00, end_at: 23:00)
[DEBUG] Split overnight shift for tuesday (shift1: tuesday 23:00-23:59, shift2: wednesday 00:00-02:00)
[INFO] Operational hours transformation completed (total_shifts: 14)
```

### Sync Logs

```
[INFO] Syncing operational hours to Careem
[INFO] Operational hours synced successfully (shifts_count: 14)
```

### Error Logs

```
[ERROR] Failed to sync operational hours to Careem
  - error: "Invalid time format"
  - trace: "..."
```

## Testing

### Manual Test

To test operational hours sync:

1. Ensure Location has `opening_hours` configured
2. Ensure Location is linked to a Careem branch with valid `brand_id` and `branch_id`
3. Run menu sync: `php artisan menu:sync {menu_id} careem`
4. Check logs for operational hours sync messages

### Example Test Data

```php
// Update Location with test hours
$location = Location::find(1);
$location->opening_hours = [
    'monday' => ['open' => '09:00', 'close' => '22:00'],
    'tuesday' => ['open' => '09:00', 'close' => '22:00'],
    'wednesday' => ['open' => '09:00', 'close' => '22:00'],
    'thursday' => ['open' => '09:00', 'close' => '22:00'],
    'friday' => ['open' => '09:00', 'close' => '23:30'],
    'saturday' => ['open' => '10:00', 'close' => '02:00'], // Overnight
    'sunday' => ['open' => '10:00', 'close' => '22:00'],
];
$location->save();
```

### Verify on Careem

After sync, verify hours appear correctly on Careem SuperApp (may take ~5 minutes to reflect).

## Troubleshooting

### Hours Not Syncing

**Check:**
1. Location has `opening_hours` array set
2. Location is linked to Menu
3. Careem branch has valid `brand_id` and `branch_id`
4. Check logs for errors

### Invalid Time Format Error

**Solution:**
- Ensure times are in "HH:MM" format (e.g., "09:00", not "9:00")
- No seconds (e.g., "09:00:00" is invalid)

### Overnight Hours Not Working

**Check:**
- System automatically handles overnight hours
- Verify closing time is before 06:00 (used as threshold for "next day")
- Check logs for "Split overnight shift" messages

## Related Files

- **Transformer:** `app/Services/CareemMenuTransformer.php`
  - `transformOperationalHours()` - Main transformation logic
  - `getDefault24HoursSchedule()` - Default 24/7 hours
  
- **Job:** `app/Jobs/SyncMenuToPlatformJob.php`
  - `syncToCareem()` - Includes operational hours sync

- **API Service:** `app/Services/CareemApiService.php`
  - `setBranchOperationalHours()` - API call method

- **Model:** `app/Models/Location.php`
  - `opening_hours` field - Stores hours data

## References

- **Careem API Docs:** https://docs.careemnow.com/#tag/Store-API-endpoints/operation/setBranchOperationalHours
- **Related Docs:**
  - [Careem Menu Sync](./CAREEM_MENU_SYNC_API.md)
  - [Careem Brand/Branch Implementation](./CAREEM_BRAND_BRANCH_IMPLEMENTATION.md)
