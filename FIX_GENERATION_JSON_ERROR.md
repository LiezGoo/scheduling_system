# Fix: "Unexpected token '<'" JSON Error in Schedule Generation

## Problem Description

**Error Message:**
```
Error: Unexpected token '<', '<!DOCTYPE ...' is not valid JSON
```

**Root Cause:** 
The frontend expected a JSON response from the schedule generation endpoint, but the backend was returning HTML (likely an error page or exception page) instead.

This happens when:
- The GA execution throws an exception
- The exception handler returns HTML instead of JSON
- The frontend fetch tries to parse HTML as JSON and fails

## Files Modified

### 1. Backend Controller: `app/Http/Controllers/DepartmentHead/GenerateScheduleController.php`

**Changes:**
- ✅ Added `use Illuminate\Support\Facades\Log;` import
- ✅ Wrapped entire `generate()` method in try-catch blocks
- ✅ Added try-catch around `$this->geneticScheduler->generate()` call
- ✅ Added comprehensive logging at key points
- ✅ Ensured all exceptions are converted to JSON responses

**Key Fixes:**
```php
// BEFORE: No exception handling - any error returns HTML
$result = $this->geneticScheduler->generate($parameters);

// AFTER: Wrapped in try-catch - returns JSON on error
try {
    $result = $this->geneticScheduler->generate($parameters);
    // ... process result
} catch (\Throwable $blockException) {
    return response()->json([
        'success' => false,
        'message' => 'Error generating Block ' . $block . ': ' . $blockException->getMessage(),
    ], 500);
}
```

**Logging Added:**
- Request received with user info
- Validation passed
- Configuration created
- Each block generation started/completed
- Errors logged with full stack trace

### 2. Frontend View: `resources/views/department-head/schedules/generate.blade.php`

**Changes:**
- ✅ Added `'Accept': 'application/json'` header to fetch request
- ✅ Improved `.then(response => ...)` handler to check response status and content-type
- ✅ Added proper error parsing for both JSON and non-JSON error responses
- ✅ Added console logging for debugging
- ✅ Better error messages for different failure scenarios

**Key Fixes:**
```javascript
// BEFORE: No error handling for non-JSON responses
fetch(url)
    .then(response => response.json())  // ❌ Fails if HTML returned
    .then(data => { ... })

// AFTER: Robust error handling
fetch(url)
    .then(async response => {
        if (!response.ok) {
            // Try to parse error as JSON first
            if (contentType.includes('application/json')) {
                const errorData = await response.json();
                errorMessage = errorData.message;
            } else {
                // HTML error page - provide helpful message
                errorMessage = `Server error (${response.status}): Backend returned non-JSON response.`;
            }
            throw new Error(errorMessage);
        }
        return response.json();
    })
    .catch(error => {
        console.error('Generation error:', error);
        showToast('Error: ' + error.message, 'error');
    })
```

## How It Works Now

### Generation Request Flow

1. **Frontend** sends POST to `/department-head/schedules/generate` with form data
2. **Backend Controller** validates input
3. **If validation fails**: Returns JSON error (422)
4. **If everything OK**: 
   - Creates ScheduleConfiguration record
   - Loops through each block
   - **For each block**:
     - Calls GeneticScheduler
     - If GA succeeds: Stores result
     - If GA throws exception: Catches it, logs it, returns JSON error (500)
5. **Returns JSON** with results or error details
6. **Frontend** receives JSON response
   - Checks `response.ok` status
   - Checks `content-type` header
   - Parses JSON safely
   - Shows appropriate error or success message

## Debugging

### View Generation Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Look for these log entries:
[2026-03-21] local.DEBUG: Schedule generation request received
[2026-03-21] local.DEBUG: Schedule generation validation passed
[2026-03-21] local.INFO: Schedule configuration created
[2026-03-21] local.DEBUG: Generating block
[2026-03-21] local.INFO: Block generation successful
[2026-03-21] local.ERROR: Exception during block generation
```

### Common Issues & Solutions

| Issue | Log Message | Solution |
|-------|-------------|----------|
| No subjects for curriculum | "No subjects found for the selected curriculum context" | Add subjects to the curriculum for the selected semester |
| No faculty assignments | "Some subjects have no faculty assignment" | Assign faculty to subjects in the faculty load module |
| No rooms available | "Insufficient room inventory" | Ensure lecture and lab rooms are created |
| Database error | Full exception trace | Check database logs for query errors |
| Missing fields | "Unexpected error" | Check logs for details |

### Browser Console Debugging

The frontend also logs to console:
```javascript
// Check browser DevTools > Console for detailed error info
console.error('Generation error:', error);
```

## Testing the Fix

1. Navigate to Department Head → Schedules → Generate
2. Fill in the form with valid data
3. Click "Generate Schedule"
4. **Expected behavior now:**
   - ✅ If successful: Shows generated schedules
   - ✅ If error: Shows clear error message (not JSON parsing error)
   - ✅ Check logs for detailed error info

## Technical Details

### Response Format

**Success Response (200):**
```json
{
    "success": true,
    "message": "Schedules generated successfully using Genetic Algorithm.",
    "data": {
        "configuration_id": 1,
        "total_blocks": 2,
        "generated_schedules": [
            {
                "block": "Block 1",
                "schedule_id": 10,
                "fitness_score": 9250.5,
                "metrics": {},
                "overloaded_faculty": []
            }
        ]
    }
}
```

**Error Response (500):**
```json
{
    "success": false,
    "message": "Error generating Block 1: No subjects found for the selected curriculum context."
}
```

**Validation Error Response (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "program_id": ["The program id must be an integer."]
    }
}
```

## Prevention Going Forward

To prevent this error in the future:

1. **Always wrap database/service calls in try-catch** in AJAX endpoints
2. **Always return `response()->json()`** from AJAX endpoints, never `view()` or redirect
3. **Add proper error handling in frontend** fetch calls (check status and content-type)
4. **Use logging extensively** for debugging
5. **Test error paths** not just happy paths

## Files Changed Summary

- `app/Http/Controllers/DepartmentHead/GenerateScheduleController.php` - Added exception handling + logging
- `resources/views/department-head/schedules/generate.blade.php` - Improved fetch error handling
- Cache cleared - `config:clear` and `cache:clear`

## Status

✅ **FIXED** - The schedule generation now properly handles all error cases and returns JSON responses consistently.
