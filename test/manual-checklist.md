# BeyondTrailTales - Manual Test Checklist

## Setup
- [ ] XAMPP Apache is running
- [ ] Navigate to http://localhost/BTT/public/
- [ ] Run seed script: http://localhost/BTT/test/seed.php

## Backpack Management

### Create Backpack
- [ ] Click "Create New Backpack"
- [ ] Modal opens with focus on first field
- [ ] Enter name (required field validation)
- [ ] Enter description (optional)
- [ ] Enter base weight (numeric validation, no negatives)
- [ ] Submit form
- [ ] Success toast appears
- [ ] New backpack appears in list

### Edit Backpack
- [ ] Click "Edit" on existing backpack
- [ ] Modal opens with existing data pre-filled
- [ ] Modify fields
- [ ] Submit form
- [ ] Success toast appears
- [ ] Changes reflected in list

### Delete Backpack
- [ ] Delete backpack not attached to trips - succeeds
- [ ] Delete backpack attached to trips - warning shown
- [ ] Confirm deletion with force - trips unlinked

## Trip Management

### Create Trip
- [ ] Click "Plan New Trip"
- [ ] Modal opens with focus on first field
- [ ] Enter title (required field validation)
- [ ] Enter location, dates, description (all optional)
- [ ] Select backpack from dropdown
- [ ] Submit without photo - succeeds
- [ ] Create another trip with photo:
  - [ ] Select image file (JPG/PNG only, max 4MB)
  - [ ] Alt text field appears and becomes required
  - [ ] Cannot submit without alt text
  - [ ] Submit with alt text - succeeds

### Edit Trip
- [ ] Click "Edit" on existing trip
- [ ] Modal opens with existing data pre-filled
- [ ] Modify fields
- [ ] Upload new photo - old photo replaced
- [ ] Submit form
- [ ] Success toast appears
- [ ] Changes reflected in list

### Delete Trip
- [ ] Click "Delete" on trip
- [ ] Confirmation dialog appears
- [ ] Confirm deletion
- [ ] Trip removed from list
- [ ] If trip had photo, verify file deleted from assets/img/trips/

## Accessibility (ADA Compliance)

### Keyboard Navigation
- [ ] Tab through all interactive elements
- [ ] Enter/Space activates buttons
- [ ] Escape closes modals
- [ ] Focus visible on all elements
- [ ] Skip to content link works

### Screen Reader
- [ ] All form fields have labels
- [ ] Required fields indicated
- [ ] Images have alt text
- [ ] Success/error messages announced
- [ ] Modal open/close announced

### Visual
- [ ] Color contrast meets WCAG AA
- [ ] Text readable at 200% zoom
- [ ] No information conveyed by color alone

## Responsive Design

### Mobile (< 768px)
- [ ] Navigation stacks vertically
- [ ] Cards stack in single column
- [ ] Modals fit screen width
- [ ] Forms remain usable
- [ ] Touch targets adequate size (44x44px)

### Desktop
- [ ] Grid layout for cards
- [ ] Proper spacing and alignment
- [ ] Modals centered
- [ ] Hover states visible

## API Testing

### Health Check
- [ ] http://localhost/BTT/api/index.php returns health status

### Backpacks API
- [ ] GET all backpacks works
- [ ] GET single backpack works
- [ ] POST creates new backpack
- [ ] PUT updates backpack
- [ ] DELETE removes backpack

### Trips API
- [ ] GET all trips works
- [ ] GET single trip works
- [ ] GET trips filtered by backpack_id
- [ ] POST creates new trip
- [ ] PUT updates trip
- [ ] DELETE removes trip

## Error Handling
- [ ] Invalid file type shows error
- [ ] File too large (>4MB) shows error
- [ ] Network errors show toast
- [ ] Form validation errors displayed
- [ ] API errors handled gracefully

## Performance
- [ ] Pages load in < 3 seconds
- [ ] No console errors
- [ ] Images load properly
- [ ] No broken links

## Data Persistence
- [ ] Data persists after page refresh
- [ ] JSON files created in storage/json/
- [ ] Uploaded photos saved in assets/img/trips/

## Browser Compatibility
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (if available)

## Notes
- Record any bugs or issues found:
  - 
  - 
  - 
