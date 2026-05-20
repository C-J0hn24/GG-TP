# RFID Pickup Page - Multi-Environment Fix

## Summary

Fixed two major issues with the RFID pickup page:

1. **Fetch URL routing for different environments** - The page now works on both:
   - `http://localhost/GG-TP/pickup-rfid` (XAMPP subfolder environment)
   - `http://127.0.0.1:8000/pickup-rfid` (Laravel dev server)

2. **NodeMCU reset behavior** - Improved handling for ESP8266 auto-resets:
   - 6-second warmup period before scanning is allowed
   - 8-second fallback message if RFID_READY not received
   - 2-second cooldown between scans to prevent duplicates
   - Better logging for debugging

## Changes Made

### 1. Dynamic API Endpoint Detection

**File**: `resources/views/pickup-rfid.blade.php`

Added a constant that automatically detects the environment and uses the correct API endpoint:

```javascript
const PICKUP_CONFIRM_URL = (() => {
    const path = window.location.pathname;
    
    if (path.startsWith('/GG-TP/')) {
        return '/GG-TP/api/pickup-rfid/confirm';
    }
    
    return '/api/pickup-rfid/confirm';
})();
```

**How it works**:
- Checks `window.location.pathname`
- If path starts with `/GG-TP/`, uses `/GG-TP/api/pickup-rfid/confirm`
- Otherwise, uses `/api/pickup-rfid/confirm`
- All fetch calls now use `PICKUP_CONFIRM_URL` instead of hardcoded paths

### 2. Improved NodeMCU Connection Handling

**File**: `resources/views/pickup-rfid.blade.php`

**Updated timing**:
- **6 seconds**: `scanAllowed` becomes true (minimum warmup time for ESP8266)
- **8 seconds**: Fallback message shown if RFID_READY not received
- **2 seconds**: Cooldown between RFID scans to prevent duplicates

**New states**:
- `scanAllowed`: Can scans be processed?
- `scannerReady`: Did device send RFID_READY signal?
- `scanCooldown`: Is the 2-second cooldown active?

**Improved messages**:
- After connection: "Scanner connected. Waiting for NodeMCU to restart..."
- If RFID_READY received: "Scanner ready. Please scan RFID card."
- If timeout: "Scanner connected. Try scanning now. If it does not scan, press reset once."

### 3. Enhanced Logging

Both fetch calls now log:
```javascript
log('Sending pickup request to: ' + PICKUP_CONFIRM_URL);
```

This helps debug which endpoint is being used. Check the browser DevTools → Network tab if issues occur.

**Serial reader logging**:
- `RFID_READY received from device` - Device ready
- `RFID_SCANNED received` - Card detected
- `Scan ignored because scanner is still warming up.` - Too soon after connection
- `Duplicate scan ignored during cooldown.` - Too soon after previous scan

## Testing Results

### XAMPP Environment (localhost/GG-TP)
✅ **PASSED**
- URL: `http://localhost/GG-TP/pickup-rfid`
- Manual pickup with order OR4: Success
- Fetch endpoint: `/GG-TP/api/pickup-rfid/confirm`
- Log shows correct URL being used

### Laravel Dev Server (127.0.0.1:8000)
✅ **PASSED**
- URL: `http://127.0.0.1:8000/pickup-rfid`
- Manual pickup with order OR5: Success
- Fetch endpoint: `/api/pickup-rfid/confirm`
- Log shows correct URL being used

## Route Configuration

**routes/api.php** (No changes needed - already correct):
```php
Route::post('/pickup-rfid/confirm', [PickupRfidController::class, 'confirm']);
```

**routes/web.php** (No changes needed - already correct):
```php
Route::get('/pickup-rfid', [PickupRfidController::class, 'show']);
```

## Troubleshooting

### Issue: Still seeing HTML instead of JSON

**Check this**:
1. Open browser DevTools (F12)
2. Go to Network tab
3. Click "Mark Picked Up Manually"
4. Find the POST request in Network tab
5. Check Request URL:
   - Should be: `http://localhost/GG-TP/api/pickup-rfid/confirm` (XAMPP)
   - Or: `http://127.0.0.1:8000/api/pickup-rfid/confirm` (Dev server)
6. Check Response tab:
   - Should be JSON, not HTML

If URL is wrong, the JavaScript constant isn't detecting the environment correctly.

### Issue: RFID Scanner needs reset button

**The improvements help**:
- 6-second warmup gives ESP8266 time to initialize
- Device sends RFID_READY when ready
- 8-second fallback allows scanning if RFID_READY not received

**If still needed**:
1. Load the new Arduino sketch: `scripts/RFID_NodeMCU_Arduino_Sketch.ino`
2. Key improvements:
   - Reinitializes RC522 every 5 seconds
   - Uses D1 (GPIO5) for LED, NOT D4
   - Sends "RFID_READY" on startup

### Issue: Duplicate scans or multiple updates

The 2-second cooldown prevents this. Check browser console if you see multiple log entries.

## Files Modified

1. **resources/views/pickup-rfid.blade.php**
   - Added `PICKUP_CONFIRM_URL` constant
   - Updated `connectScanner()` - 6s/8s timing
   - Updated `readSerialPort()` - better logging
   - Updated `handleRfidScanned()` - use URL constant and logging
   - Updated `markPickedUpManually()` - use URL constant and logging

2. **scripts/RFID_NodeMCU_Arduino_Sketch.ino** (NEW)
   - Complete Arduino sketch with improvements
   - RC522 reinitialization every 5 seconds
   - Correct pin configuration
   - RFID_READY and RFID_SCANNED signals

## Cache Clearing

After these changes, ran:
```
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

All caches cleared successfully.

## Next Steps

1. **Test both environments** as documented above
2. **Update Arduino firmware** with the new sketch if you have hardware issues
3. **Check network requests** in DevTools if you encounter issues
4. **Monitor console logs** for debugging messages

## Environment-Specific Behavior

### XAMPP (localhost/GG-TP)
- Apache serves files from `htdocs/GG-TP`
- PHP routes requests through `index.php`
- API endpoint must include `/GG-TP` prefix
- Works with: `http://localhost/GG-TP/pickup-rfid`

### Laravel Dev Server (127.0.0.1:8000)
- Built-in PHP server serves from project root
- No subfolder needed
- API endpoint is `/api/pickup-rfid/confirm`
- Works with: `http://127.0.0.1:8000/pickup-rfid`

## Key Takeaway

The `PICKUP_CONFIRM_URL` constant automatically detects which environment you're in and uses the correct endpoint. This eliminates the need to maintain two separate blade files or manually switch endpoints.

Both environments now work seamlessly with a single codebase!
