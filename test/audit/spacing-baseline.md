# BTT Spacing Baseline Audit

## Date: September 1, 2025
## Current Issues Identified

### 1. Button & Icon Spacing Issues

#### public/backpacks.php
- **Line 19-22**: Button with inline icon spacing `<span class="btn-icon">🎒</span>` - no consistent gap
- **Line 59-61**: .btn-group missing proper gap between buttons
- **Line 113-118**: Buttons in card footer have inconsistent spacing, using btn-sm without proper margins

#### public/trips.php
- **Line 89-92**: Similar inline icon spacing issue
- **Line 298-300**: Form action buttons using inline styles for spacing

#### public/index.php
- **Line 23, 33**: Primary buttons with inconsistent padding

### 2. Card Structure Issues

#### All pages
- Cards using mix of:
  - `.card-header`, `.card-body`, `.card-footer` (inconsistent padding)
  - Inline styles for margins/padding
  - No standardized gap between card sections

#### public/backpacks.php
- **Line 94-120**: Card structure with ad-hoc spacing
  - `.card-stats` custom spacing (line 101)
  - `.stat-item` using inline margins (line 102-109)

### 3. Form Spacing Issues

#### public/trips.php
- **Line 114-199**: Form groups with inconsistent margins
- **Line 130-144**: Date range selector with inline gaps
- Missing consistent .form-group spacing

#### public/backpacks.php  
- **Line 43-57**: Form controls with varying margins
- **Line 59-62**: Form actions without proper button group spacing

### 4. Gamification Bar Issues

#### public/includes/gamification-bar.php
- Complex inline styles for positioning
- No consistent spacing tokens
- Mobile responsive issues with wrapping

### 5. Smart Packing Assistant Issues

#### public/includes/smart-packing-assistant.php
- **Line 134-148**: Fixed positioning with hard-coded values
- **Line 318-335**: Tab buttons with inline gaps
- **Line 377-390**: Quick actions without proper button group spacing

### 6. Header/Footer Issues

#### public/includes/header.php
- **Line 32-38**: Navigation using mix of padding on links instead of gap
- No consistent vertical rhythm

### 7. Modal Spacing Issues

#### public/backpacks.php & trips.php
- Modal content padding inconsistent
- Button groups in modals using ad-hoc spacing

## Current CSS Token Usage

### What's Missing:
- No component-level spacing aliases (everything references raw --space-* values)
- No button-specific spacing tokens
- No card-specific spacing tokens  
- No form-specific spacing tokens
- No consistent icon sizing tokens

### What's There:
- Good base spacing scale (--space-1 through --space-32)
- Good radius tokens but not consistently used
- Forest theme colors well-defined

## JavaScript Selectors to Preserve

### assets/js/app.js
- Uses `.btn`, `.form-control`, `.modal`, `.card` selectors
- BTTUtils functions expect: 
  - Modal structure with `.modal-content`
  - Toast container with `.toast-container`

### assets/js/gamification.js
- Expects `.gamification-bar` container
- Creates elements with `.xp-display`, `.level-display`, `.streak-display`
- Badge modals use `.badge-modal` class

## Recommendations

1. **Immediate Priorities:**
   - Add component-level spacing tokens to forest-tokens.css
   - Create spacing utility classes (.u-stack, .u-cluster, etc.)
   - Normalize button component with consistent padding/gaps
   - Fix card structure with proper header/body/footer spacing

2. **Secondary Priorities:**
   - Update form groups for consistent rhythm
   - Fix modal spacing
   - Normalize gamification bar layout
   - Clean up Smart Packing Assistant spacing

3. **ADA Compliance Fixes Needed:**
   - Ensure all buttons meet 44px minimum touch target
   - Add focus-visible states to all interactive elements
   - Mark decorative icons with aria-hidden="true"
   - Ensure proper contrast ratios maintained

## Files to Update

### CSS Files:
1. assets/css/forest-tokens.css - Add component aliases
2. assets/css/forest-components.css - Update component styles
3. assets/css/main.css - Add utility classes

### PHP Templates:
1. public/index.php
2. public/backpacks.php  
3. public/trips.php
4. public/includes/header.php
5. public/includes/footer.php
6. public/includes/gamification-bar.php
7. public/includes/smart-packing-assistant.php

### JavaScript (class updates only):
1. assets/js/app.js - Preserve existing selectors
2. assets/js/gamification.js - Preserve existing selectors
