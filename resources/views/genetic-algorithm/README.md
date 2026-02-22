# Schedule Generation Module - UI/UX Documentation

## Overview

This document describes the professional, modern Schedule Generation UI built using Bootstrap 5 and a Genetic Algorithm framework for the Sorsu Scheduling System.

## 🎯 Features

### 1. **Schedule Configuration Panel**
- Academic Year & Semester selection
- Department & Program selection
- Year Level & Block/Section selection
- GA parameter customization with tooltips:
  - Population Size (10-500)
  - Number of Generations (10-1000)
  - Mutation Rate (1-100%)
  - Crossover Rate (1-100%)
  - Elite Size (1-50)

### 2. **GA Execution Status Panel**
- Real-time status badge (Idle/Running/Completed/Failed)
- Animated progress bar
- Current generation counter
- Best fitness score display
- Conflict count display
- Running indicator with spinner
- Success confirmation

### 3. **Generated Schedule Preview**
- **Grid View**: 7 AM - 7 PM weekly timetable
  - Color-coded blocks:
    - Blue: Lectures
    - Green: Laboratories
    - Red: Conflicts
    - Gray: Reserved/Breaks
  - Subject code, instructor, room display
  - Hover interactions
  
- **Table View**: List format with sortable columns
  - Subject, Instructor, Room, Day, Time, Type, Status
  - Row selection highlighting

### 4. **Conflict Summary Panel**
- Collapsible section
- Success state (green badge for conflict-free)
- Conflict state with:
  - Room conflicts list
  - Instructor conflicts list
  - Overlapping schedules
  - Penalty score breakdown

### 5. **Export & Action Buttons**
- Export to PDF
- Export to CSV
- Print functionality
- Approve & Submit (Program Head role)
- View History
- Stop Generation

## 📋 File Structure

```
resources/
├── views/
│   └── genetic-algorithm/
│       └── generate.blade.php           # Main UI template
├── css/
│   └── genetic-algorithm.css            # Global styles & responsive design
└── js/
    └── schedule-generation.js           # Advanced interactions & state management
```

## 🚀 Usage

### Include in Route

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/schedule-generation', function () {
        return view('genetic-algorithm.generate');
    })->name('schedule-generation.index');
});
```

### Include CSS & JavaScript

Add to your main layout file (`resources/views/layouts/app.blade.php`):

```html
<!-- In <head> section -->
<link rel="stylesheet" href="{{ asset('css/genetic-algorithm.css') }}">

<!-- Before closing </body> tag -->
<script src="{{ asset('js/schedule-generation.js') }}"></script>
```

### Use in Blade Template

```blade
@extends('layouts.app')

@section('content')
    @include('genetic-algorithm.generate')
@endsection
```

## 🎨 Theme Customization

### Maroon Theme Colors

Primary colors can be customized in `resources/css/genetic-algorithm.css`:

```css
:root {
    --maroon: #660000;
    --maroon-light: #8b0000;
    --maroon-lighter: #a52a2a;
}
```

### Custom Branding

1. **Maroon Headers**: All card headers use maroon background
2. **Accent Colors**: 
   - Success: #28a745 (Green)
   - Warning: #ffc107 (Yellow)
   - Danger: #dc3545 (Red)
   - Info: #17a2b8 (Cyan)

3. **Typography**: Bootstrap 5 default + custom font sizing for schedule grid

## 📱 Responsive Behavior

### Desktop (≥ 992px)
- Left column (4 cols): Configuration panel
- Right column (8 cols): GA Execution status
- Full width: Schedule preview + Conflict summary

### Tablet (768px - 991px)
- Stacked vertically
- Configuration → Controls → Preview
- Optimized padding & font sizes

### Mobile (< 768px)
- Single column layout
- Reduced schedule slot height
- Smaller fonts & buttons
- Touch-friendly button sizes

### Ultra-Mobile (< 576px)
- Minimal padding
- Reduced schedule table
- Collapsed button groups
- Full-width form controls

## 🔄 State Management

### ScheduleGenerationUI Class

```javascript
// Initialize
const scheduleUI = new ScheduleGenerationUI();

// State structure
scheduleUI.state = {
    isRunning: boolean,
    currentGeneration: number,
    totalGenerations: number,
    bestFitness: number,
    conflictCount: number,
    generationHistory: array,
    selectedScheduleItem: number
};
```

### Key Methods

```javascript
// Start generation process
scheduleUI.showConfirmationModal();
scheduleUI.executeGeneration();

// Update UI elements
scheduleUI.updateProgress(percentage);
scheduleUI.updateStatus(status);
scheduleUI.updateFitnessScore(score);

// Switch views
scheduleUI.switchView('grid' | 'table');

// Export
scheduleUI.exportToPDF();
scheduleUI.exportToCSV();

// Notifications
scheduleUI.showToast(message, type);
```

## 🎯 Role-Based Behavior

### Admin
```
✓ Configure all GA parameters
✓ Generate schedules
✓ View all histories
✓ Export schedules
✓ Approve submissions
```

### Program Head
```
✓ Configure program-level generation
✓ Department auto-filled
✓ Approve & submit schedules
✓ Export own schedules
```

### Department Head
```
✓ View submitted schedules
✓ View only (read-only)
✓ Cannot approve without Program Head
```

## 🔌 Backend Integration Points

### 1. Configuration Submission
```php
// POST /api/schedule-generation/configure
{
    "academic_year": "YYYY-YYYY",
    "semester": 1,
    "program_id": 1,
    "year_level": 1,
    "block_section": "A",
    "population_size": 50,
    "generations": 100,
    "mutation_rate": 15,
    "crossover_rate": 80,
    "elite_size": 5
}
```

### 2. Generation Progress (WebSocket/SSE)
```javascript
// Real-time updates
{
    "type": "generation_progress",
    "current_generation": 50,
    "total_generations": 100,
    "best_fitness": 92.5,
    "conflict_count": 0
}
```

### 3. Schedule Completion
```php
// POST /api/schedule-generation/complete
{
    "schedule_id": 123,
    "status": "completed",
    "conflicts": [],
    "fitness_score": 95.5
}
```

## 📊 Sample Schedule Data Structure

```javascript
{
    subject: "CS-101",
    instructor: "Dr. Smith",
    room: "Lab 301",
    day: "Monday",
    time: "09:00",
    type: "lecture",  // lecture | lab | conflict | reserved
    duration: 120      // minutes
}
```

## 🎨 Color Codes

### Schedule Items
- **Lecture**: `#007bff` (Bootstrap Blue)
- **Laboratory**: `#28a745` (Bootstrap Green)
- **Conflict**: `#dc3545` (Bootstrap Red)
- **Reserved**: `#6c757d` (Bootstrap Gray)

### Status Badges
- **Idle**: Gray
- **Running**: Primary Blue
- **Completed**: Green
- **Failed**: Red

### Metric Accents
- **Generation**: Maroon
- **Fitness**: Green
- **Conflicts**: Red
- **Penalty**: Yellow

## 🔐 Security Considerations

1. **Form Validation**: Client-side validation before submission
2. **CSRF Protection**: Laravel @csrf token included
3. **Role Authorization**: Check user roles before rendering actions
4. **API Rate Limiting**: Implement on backend for generation endpoints
5. **Data Sanitization**: All user inputs validated server-side

## ⚡ Performance Optimization

1. **Lazy Loading**: Schedule items loaded on-demand
2. **CSS**: Minified grid styles
3. **JavaScript**: Event delegation for schedule items
4. **Caching**: Store generation history in sessionStorage
5. **Progress Updates**: Throttled to 400ms intervals

## 🎭 Micro-UX Behaviors

### Confirmation Modal
- Shows before generation starts
- Displays configuration summary
- Prevents accidental submissions

### Success Toast
- Auto-hides after 4 seconds
- Success/Error/Warning variants
- Bottom-right positioning

### Loading State
- Spinner in status badge
- Disabled form inputs
- Progress bar animation
- "Running..." indicator message

### Conflict Highlighting
- Red pulse animation for conflict items
- Expanded conflict summary section
- Warning alert box

### Interactive Schedule
- Hover effects on schedule items
- Click to show details
- Table row selection highlighting
- Grid slot spacing

## 📝 Keyboard Navigation

- **Tab**: Navigate through form controls
- **Enter**: Submit forms or activate buttons
- **Space**: Toggle checkboxes/buttons
- **Escape**: Close modals

## 🖨️ Print Support

- Hidden buttons and modals in print view
- Optimized schedule table layout
- Removed unnecessary styling
- Use `@media print` CSS

## 🧪 Testing Checklist

- [ ] Form validation works
- [ ] Confirmation modal displays
- [ ] Generation simulation progresses
- [ ] Progress bar updates smoothly
- [ ] Status transitions correctly
- [ ] Schedule populates after generation
- [ ] Grid/Table view switch works
- [ ] Export buttons function
- [ ] Toast notifications display
- [ ] Responsive on all breakpoints
- [ ] Tooltips appear on hover
- [ ] History records generation
- [ ] Conflict summary displays
- [ ] Role-based UI shows correctly
- [ ] Stop button halts generation

## 🐛 Troubleshooting

### Modal not appearing
```javascript
// Ensure Bootstrap is loaded
if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap not loaded');
}
```

### Schedule items not showing
```javascript
// Check data structure
console.log(scheduleUI.state);
```

### Tooltips not working
```javascript
// Reinitialize tooltips
scheduleUI.initializeTooltips();
```

### Poor performance on mobile
```javascript
// Reduce animation duration in config
scheduleUI.config.progressUpdateInterval = 500;
```

## 📚 Additional Resources

- Bootstrap 5 Docs: https://getbootstrap.com/docs/5.0/
- Font Awesome Icons: https://fontawesome.com/icons
- Laravel Blade: https://laravel.com/docs/blade
- Genetic Algorithm Basics: https://en.wikipedia.org/wiki/Genetic_algorithm

## 🔄 Version History

### v1.0.0 (Initial Release)
- Complete schedule generation UI
- GA parameter configuration
- Real-time progress tracking
- Schedule preview (Grid & Table views)
- Export functionality
- Responsive design
- Role-based features

## 📞 Support

For issues or improvements, contact the development team or create a GitHub issue.

---

**Last Updated**: February 18, 2026  
**Framework**: Laravel + Bootstrap 5  
**Primary Theme**: Maroon (#660000)
