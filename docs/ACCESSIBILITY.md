# Accessibility Guidelines for BeyondTrailTales

## Overview

BeyondTrailTales is committed to making outdoor adventures accessible to everyone. This document outlines our accessibility standards and implementation guidelines.

## WCAG 2.1 Compliance

We aim for WCAG 2.1 Level AA compliance across all features.

### Key Principles

1. **Perceivable** - Information and UI components must be presentable in ways users can perceive
2. **Operable** - UI components and navigation must be operable
3. **Understandable** - Information and UI operation must be understandable
4. **Robust** - Content must be robust enough for interpretation by a wide variety of user agents

## Implementation Guidelines

### 1. Semantic HTML

Always use semantic HTML elements:
```tsx
// ✅ Good
<nav aria-label="Main navigation">
  <ul>
    <li><a href="/trips">Trips</a></li>
  </ul>
</nav>

// ❌ Bad
<div class="navigation">
  <div class="nav-item">Trips</div>
</div>
```

### 2. ARIA Labels and Descriptions

Provide context with ARIA attributes:
```tsx
// Button with icon only
<button aria-label="Delete trip">
  <TrashIcon aria-hidden="true" />
</button>

// Form field with error
<input
  id="email"
  type="email"
  aria-describedby="email-error"
  aria-invalid={hasError}
  aria-required="true"
/>
<span id="email-error" role="alert">Invalid email format</span>
```

### 3. Keyboard Navigation

All interactive elements must be keyboard accessible:
- Use `tabindex="0"` for focusable non-interactive elements
- Use `tabindex="-1"` for programmatically focusable elements
- Implement arrow key navigation for complex widgets
- Provide skip links for repetitive content

```tsx
// Skip navigation link
<a href="#main-content" className="skip-link">
  Skip to main content
</a>
```

### 4. Focus Management

Manage focus appropriately:
```tsx
// Modal focus trap
useEffect(() => {
  if (isOpen) {
    const firstFocusable = modalRef.current?.querySelector(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    firstFocusable?.focus()
  }
}, [isOpen])
```

### 5. Color Contrast

Maintain sufficient color contrast ratios:
- Normal text: 4.5:1 minimum
- Large text (18pt+): 3:1 minimum
- UI components: 3:1 minimum

Our forest theme colors:
- Background: `#0d1810` (dark pine)
- Primary text: `#e8f5e8` (light moss)
- Secondary text: `#a8c0a8` (medium moss)

### 6. Form Accessibility

Make forms accessible:
```tsx
<form aria-label="Trip planning form">
  <fieldset>
    <legend>Trip Details</legend>
    
    <label htmlFor="trip-name">
      Trip Name <span aria-label="required">*</span>
    </label>
    <input
      id="trip-name"
      type="text"
      required
      aria-describedby="trip-name-help"
    />
    <span id="trip-name-help">
      Enter a memorable name for your trip
    </span>
  </fieldset>
</form>
```

### 7. Error Handling

Provide clear error messages:
```tsx
// Error summary at form top
<div role="alert" aria-live="assertive">
  <h2>There are 3 errors in this form</h2>
  <ul>
    <li><a href="#email">Email is required</a></li>
    <li><a href="#password">Password is too short</a></li>
  </ul>
</div>
```

### 8. Loading States

Announce loading states:
```tsx
<div aria-live="polite" aria-busy={isLoading}>
  {isLoading ? 'Loading trips...' : 'Trips loaded'}
</div>
```

### 9. Images and Icons

Provide appropriate alt text:
```tsx
// Decorative image
<img src="pattern.svg" alt="" role="presentation" />

// Informative image
<img 
  src="trail-map.jpg" 
  alt="Trail map showing 5-mile loop with 500ft elevation gain" 
/>

// Icon buttons
<button aria-label="Add to favorites">
  <HeartIcon aria-hidden="true" />
</button>
```

### 10. Tables

Make data tables accessible:
```tsx
<table>
  <caption>Your gear inventory</caption>
  <thead>
    <tr>
      <th scope="col">Item</th>
      <th scope="col" aria-sort="ascending">Weight</th>
      <th scope="col">Category</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">Backpack</th>
      <td>2.5 lbs</td>
      <td>Gear</td>
    </tr>
  </tbody>
</table>
```

## Testing Checklist

### Manual Testing
- [ ] Navigate entire app using only keyboard
- [ ] Test with screen reader (NVDA/JAWS on Windows, VoiceOver on Mac)
- [ ] Verify all content is readable at 200% zoom
- [ ] Check color contrast ratios
- [ ] Test with Windows High Contrast mode
- [ ] Verify focus indicators are visible

### Automated Testing
- [ ] Run axe DevTools extension
- [ ] Include jest-axe in unit tests
- [ ] Run Lighthouse accessibility audit
- [ ] Use React Testing Library with accessibility queries

### Screen Reader Announcements
- [ ] Form submissions announce success/error
- [ ] Route changes announce new page
- [ ] Dynamic content updates are announced
- [ ] Loading states are announced

## Component-Specific Guidelines

### Trip Planner
- Announce when items are added/removed from packing list
- Provide weight summaries for screen readers
- Make drag-and-drop keyboard accessible

### Gear Management
- Table sortable columns announce sort direction
- Bulk actions announce number of items selected
- Filter results announce count changes

### Map Features
- Provide text alternatives for trail maps
- Include elevation/distance in accessible format
- Keyboard controls for map navigation

## Mobile Accessibility

### Touch Targets
- Minimum 44x44px touch targets
- Adequate spacing between targets
- Gesture alternatives for all actions

### Mobile Screen Readers
- Test with TalkBack (Android)
- Test with VoiceOver (iOS)
- Ensure swipe gestures work properly

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM Resources](https://webaim.org/resources/)
- [React Accessibility Docs](https://react.dev/reference/react-dom/components/common#accessibility-attributes)

## Accessibility Statement

BeyondTrailTales is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone and applying the relevant accessibility standards.

If you encounter any accessibility barriers, please contact us at accessibility@beyondtrailtales.com.