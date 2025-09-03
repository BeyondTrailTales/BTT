# Trip Inline Builder Documentation

## Overview
The Trip Inline Builder is a modern, accessible, single-page application for managing backpacking trips. It replaces the legacy overlay-based system with an inline, tab-based interface that matches the Pack Builder design pattern.

## Architecture

### File Structure
```
BTT/
├── public/
│   ├── trips.php               # Main trip builder interface
│   ├── trips-old.php           # Legacy version (backup)
│   └── test/
│       └── trips-e2e.html      # End-to-end test suite
├── assets/
│   └── css/
│       ├── trip-builder.css           # Trip-specific styles
│       └── trip-builder-enhanced.css  # Enhanced trip features
└── docs/
    └── TRIPS-INLINE-BUILDER.md        # This documentation
```

### Key Features
- **No Overlays**: All functionality is inline, no modal overlays
- **Tab-Based Navigation**: Top-level tabs for My Trips and Trip Editor
- **Full CRUD Support**: Create, Read, Update, Delete operations
- **ADA Compliant**: WCAG 2.1 AA accessibility standards
- **Responsive Design**: Mobile, tablet, and desktop breakpoints
- **Real-time Updates**: Dynamic insights panel
- **Keyboard Navigation**: Full keyboard support with ARIA

## User Interface

### Layout Structure
1. **Action Bar**
   - Tab navigation (My Trips | Trip Editor)
   - Search bar with live filtering
   - New Trip button

2. **My Trips View**
   - Grid layout of trip cards
   - Sort options (Recent, Name, Date)
   - Empty state for new users
   - Click cards to edit

3. **Trip Editor View**
   - Left column: Tabbed form sections
   - Right column: Live insights panel
   - Form sections: Basics, Trail Info, Logistics, Conditions, Notes

### Trip Card Features
- **Visual Elements**
  - Cover image with alt text
  - Trip type badge
  - Location with pin icon
  - Date range display
  - Duration, distance, elevation stats
  - Status badges (Planning/Completed, Favorite)
  - Difficulty indicator

- **Interactive Elements**
  - Click card to edit
  - Action buttons (Edit, View, Delete)
  - Keyboard accessible (Tab, Enter, Space)
  - Focus indicators

## Technical Implementation

### JavaScript Architecture
```javascript
// State Management
const state = {
  trips: [],          // All trips
  filtered: [],       // Filtered/sorted trips
  backpacks: [],      // Available backpacks
  activeView: '',     // Current view
  formTab: '',        // Current form tab
  mode: '',           // create|edit|view
  currentId: null     // Current trip ID
};

// Core Functions
- init()              // Initialize app
- loadTrips()         // Fetch trips from API
- loadBackpacks()     // Fetch backpacks
- filterTrips()       // Search/filter
- sortTrips()         // Sort by criteria
- updateGrid()        // Render trip cards
- openEditor()        // Open trip editor
- onSave()           // Save trip
- onDelete()         // Delete trip
```

### API Integration
```javascript
// BTT API Methods Used
BTTApi.get('trips')           // List trips
BTTApi.get('trips', {id})     // Get single trip
BTTApi.post('trips', data)    // Create trip
BTTApi.put('trips', id, data) // Update trip
BTTApi.delete('trips', id)    // Delete trip
BTTApi.get('backpacks')       // List backpacks
```

### CSS Architecture
- **Base Styles**: `pack-builder.css` - Shared layout components
- **Trip Styles**: `trip-builder.css` - Trip-specific overrides
- **Enhancements**: `trip-builder-enhanced.css` - Visual effects

## Accessibility Features

### ARIA Implementation
- **Roles**: navigation, tablist, tab, tabpanel, search, status, alert
- **Properties**: aria-selected, aria-controls, aria-labelledby, aria-describedby
- **States**: aria-busy, aria-invalid, aria-hidden, aria-live

### Keyboard Navigation
- **Tab/Shift+Tab**: Navigate through focusable elements
- **Arrow Keys**: Navigate between tabs
- **Enter/Space**: Activate buttons and links
- **Escape**: Clear search field
- **Home/End**: Jump to first/last tab

### Screen Reader Support
- Live regions for status announcements
- Descriptive labels for all interactive elements
- Error announcements with assertive alerts
- Form validation connected via ARIA

### Visual Accessibility
- High contrast borders and text
- Focus indicators (2px outline)
- Reduced motion support
- Color-blind friendly palettes
- Minimum touch target size (44x44px)

## Form Validation

### Client-Side Validation
```javascript
// Required Fields
- Trip title (required)
- Alt text (required when image URL provided)

// Logic Validation
- End date must be after start date
- Numeric fields must be valid numbers

// Error Display
- Inline error messages
- ARIA-connected error descriptions
- Focus management to error container
```

### Server-Side Integration
- All data sanitized via BTTUtils.escapeHtml()
- API handles additional validation
- Toast notifications for success/error states

## Responsive Design

### Breakpoints
```css
/* Desktop: 1200px+ */
- Two-column layout in editor
- Full action bar
- Grid with multiple columns

/* Tablet: 768px - 1199px */
- Single column editor
- Condensed action bar
- 2-column grid

/* Mobile: < 768px */
- Stacked layout
- Sticky action bar
- Single column grid
- Full-width forms
```

### Performance Optimizations
- Debounced search (200ms)
- Event delegation for dynamic content
- CSS will-change for animations
- Reduced animations on low-end devices

## Testing

### E2E Test Suite (`trips-e2e.html`)
- **Core Functionality**: Page load, containers, API
- **Keyboard Navigation**: Tab order, arrow keys
- **CRUD Operations**: Create, edit, delete flows
- **Accessibility**: ARIA, focus, validation
- **Responsive Design**: Mobile, tablet, desktop viewports

### Manual Testing Checklist
- [ ] Create new trip with all fields
- [ ] Edit existing trip
- [ ] Delete trip with confirmation
- [ ] Search trips by name/location
- [ ] Sort by recent/name/date
- [ ] Keyboard-only navigation
- [ ] Screen reader compatibility
- [ ] Mobile touch interactions
- [ ] Form validation errors

## Migration Guide

### From Overlay Version
1. **Backup**: Keep `trips-old.php` for rollback
2. **Deploy**: Replace `trips.php` with new version
3. **Styles**: Add trip-builder CSS files
4. **Test**: Run E2E tests in `/test/trips-e2e.html`
5. **Monitor**: Check console for errors
6. **Cleanup**: Remove overlay references after stable

### Database Compatibility
- No database changes required
- All existing trip data compatible
- API endpoints unchanged

## Future Enhancements

### Planned Features
- [ ] List view toggle (grid/list)
- [ ] Bulk operations (delete multiple)
- [ ] Trip templates/presets
- [ ] Photo gallery per trip
- [ ] GPX file upload/download
- [ ] Weather integration
- [ ] Trail reviews/ratings
- [ ] Social sharing

### Performance Improvements
- [ ] Virtual scrolling for large datasets
- [ ] Image lazy loading
- [ ] Service worker caching
- [ ] Optimistic UI updates

## Troubleshooting

### Common Issues

**Issue**: Trips not loading
- Check API endpoint availability
- Verify authentication/session
- Check browser console for errors

**Issue**: Save fails
- Validate required fields (title)
- Check network connectivity
- Verify API response

**Issue**: Keyboard navigation broken
- Check tabindex attributes
- Verify ARIA roles
- Test focus management

**Issue**: Mobile layout issues
- Check viewport meta tag
- Test responsive breakpoints
- Verify touch target sizes

## Code Examples

### Adding a New Form Field
```javascript
// 1. Add HTML in trips.php
<input id="new_field" name="new_field" class="form-control" type="text" />

// 2. Cache element
els.newField = document.getElementById('new_field');

// 3. Populate from API
els.newField.value = trip.new_field || '';

// 4. Include in serialization
// Already handled by FormData

// 5. Add validation if needed
if (!els.newField.value) {
  errors.push('New field is required');
}
```

### Adding a New Trip Status
```javascript
// In renderTripCard()
const statusMap = {
  'planning': { class: 'status-planning', icon: '📝', label: 'Planning' },
  'completed': { class: 'status-completed', icon: '✓', label: 'Completed' },
  'cancelled': { class: 'status-cancelled', icon: '❌', label: 'Cancelled' } // New
};
```

## Support

For issues or questions:
1. Check this documentation
2. Review test results in `/test/trips-e2e.html`
3. Check browser console for errors
4. Review commits in version control

## License
Copyright © 2024 BeyondTrailTales. All rights reserved.
