# CSS Developer Guide - BeyondTrailTales

## Quick Reference

### BEM Naming Convention

```css
/* Block - Standalone component */
.c-card {}

/* Element - Part of a block */
.c-card__header {}
.c-card__title {}
.c-card__body {}

/* Modifier - Variation of block or element */
.c-card--featured {}
.c-card--compact {}

/* State - Temporary state */
.c-card.is-loading {}
.c-card.is-active {}
```

### Namespace Prefixes

- `o-` = Objects (layout primitives)
- `c-` = Components (UI components)
- `u-` = Utilities (single-purpose helpers)
- `t-` = Themes (theme-specific overrides)
- `p-` = Pages (page-specific styles)
- `is-` = State (temporary states)
- `js-` = JavaScript hooks (no styling)

### File Naming

```
_component-name.css  /* Partial file (imported) */
component-name.css   /* Standalone file */
```

## Writing CSS Rules

### ❌ DON'T

```css
/* Don't use IDs for styling */
#header { background: blue; }

/* Don't use !important (except utilities) */
.card { margin: 10px !important; }

/* Don't use inline styles */
<div style="margin: 10px;">

/* Don't nest too deeply */
.card .header .title .icon { }

/* Don't use generic class names */
.title { font-size: 24px; }
.content { padding: 10px; }

/* Don't mix concerns */
.c-card {
  /* Component styles */
  margin-bottom: 20px; /* This is layout, not component */
}
```

### ✅ DO

```css
/* Use custom properties */
.c-card {
  padding: var(--btt-space-4);
  background: var(--btt-color-surface);
}

/* Use BEM for relationships */
.c-card {}
.c-card__header {}
.c-card__title {}

/* Use utilities for spacing */
<div class="c-card u-mb-4">

/* Keep specificity low */
.c-card--featured {
  border: 2px solid var(--btt-color-primary);
}

/* Use semantic class names */
.c-button--primary {}
.c-alert--warning {}
```

## Common Patterns

### Container Pattern

```html
<!-- Page-level container -->
<div class="o-container o-container--xl">
  <!-- Content -->
</div>

<!-- Section container -->
<section class="c-section">
  <div class="o-container">
    <!-- Section content -->
  </div>
</section>
```

### Grid Pattern

```html
<!-- Basic grid -->
<div class="o-grid o-grid--cols-3 o-grid--gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div>

<!-- Responsive grid -->
<div class="o-grid o-grid--cols-1 o-grid--cols-md-2 o-grid--cols-lg-3">
  <!-- Items -->
</div>
```

### Component Pattern

```html
<!-- Card component -->
<article class="c-card c-card--featured">
  <div class="c-card__header">
    <h3 class="c-card__title">Title</h3>
  </div>
  <div class="c-card__body">
    <p>Content goes here...</p>
  </div>
  <div class="c-card__footer">
    <button class="c-button c-button--primary">Action</button>
  </div>
</article>
```

### State Management

```html
<!-- Loading state -->
<div class="c-card is-loading">
  <div class="c-skeleton"></div>
</div>

<!-- Active state -->
<button class="c-tab is-active">Active Tab</button>

<!-- Disabled state -->
<button class="c-button is-disabled" disabled>Disabled</button>
```

## CSS Custom Properties

### Using Variables

```css
/* Component using design tokens */
.c-button {
  /* Colors */
  background: var(--btt-color-primary);
  color: var(--btt-color-text-inverse);
  
  /* Spacing */
  padding: var(--btt-space-2) var(--btt-space-4);
  margin-bottom: var(--btt-space-4);
  
  /* Typography */
  font-size: var(--btt-text-base);
  font-weight: var(--btt-font-medium);
  
  /* Effects */
  border-radius: var(--btt-radius-md);
  transition: all var(--btt-duration-base) var(--btt-ease-out);
}
```

### Component-Specific Variables

```css
/* Define at component level */
.c-card {
  --card-padding: var(--btt-space-4);
  --card-background: var(--btt-color-surface);
  --card-radius: var(--btt-radius-lg);
  
  padding: var(--card-padding);
  background: var(--card-background);
  border-radius: var(--card-radius);
}

/* Override for specific instance */
.c-card--compact {
  --card-padding: var(--btt-space-2);
}
```

## Responsive Design

### Mobile-First Approach

```css
/* Base (mobile) styles */
.c-card {
  padding: var(--btt-space-3);
}

/* Tablet and up */
@media (min-width: 768px) {
  .c-card {
    padding: var(--btt-space-4);
  }
}

/* Desktop and up */
@media (min-width: 1024px) {
  .c-card {
    padding: var(--btt-space-6);
  }
}
```

### Responsive Utilities

```html
<!-- Hidden on mobile, visible on desktop -->
<div class="u-hidden u-block-lg">
  Desktop only content
</div>

<!-- Different spacing per breakpoint -->
<div class="u-p-2 u-p-md-4 u-p-lg-6">
  Responsive padding
</div>
```

## Common Mistakes to Avoid

### 1. Over-Specifying

```css
/* ❌ Too specific */
body .page-wrapper .container .card .header .title {
  font-size: 18px;
}

/* ✅ Just right */
.c-card__title {
  font-size: var(--btt-text-lg);
}
```

### 2. Magic Numbers

```css
/* ❌ Magic numbers */
.c-card {
  padding: 17px;
  margin-bottom: 23px;
}

/* ✅ Use spacing scale */
.c-card {
  padding: var(--btt-space-4);
  /* Use utility class for margin */
}
```

### 3. Mixing Concerns

```css
/* ❌ Component handles its own spacing */
.c-button {
  margin-right: 10px;
  margin-bottom: 20px;
}

/* ✅ Let parent or utilities handle spacing */
.c-button-group {
  display: flex;
  gap: var(--btt-space-2);
}
/* or */
<button class="c-button u-mr-2">Button</button>
```

### 4. Not Using Semantic HTML

```html
<!-- ❌ Div soup -->
<div class="c-card">
  <div class="c-card__header">
    <div class="c-card__title">Title</div>
  </div>
</div>

<!-- ✅ Semantic HTML -->
<article class="c-card">
  <header class="c-card__header">
    <h3 class="c-card__title">Title</h3>
  </header>
</article>
```

## Adding New Components

### 1. Check if it exists
- Search for similar components
- Check if it can extend existing component
- Consider if it's truly needed

### 2. Plan the structure
```css
/**
 * Component: Alert
 * 
 * Purpose: Display important messages to users
 * 
 * HTML Structure:
 * <div class="c-alert c-alert--warning">
 *   <div class="c-alert__icon"></div>
 *   <div class="c-alert__content">
 *     <h4 class="c-alert__title">Warning</h4>
 *     <p class="c-alert__message">Message here</p>
 *   </div>
 *   <button class="c-alert__close"></button>
 * </div>
 */
```

### 3. Write mobile-first CSS
```css
/* Base component */
.c-alert {
  position: relative;
  display: flex;
  gap: var(--btt-space-3);
  padding: var(--btt-space-3);
  background: var(--btt-color-surface);
  border-radius: var(--btt-radius-md);
  border: 1px solid var(--btt-color-border);
}

/* Elements */
.c-alert__icon {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
}

.c-alert__content {
  flex: 1;
}

/* Modifiers */
.c-alert--warning {
  border-color: var(--btt-color-warning);
  background: var(--btt-color-warning-surface);
}
```

### 4. Document usage
- Add examples to style guide
- Document all modifiers
- Note accessibility requirements

## Debugging CSS Issues

### 1. Check Specificity
```css
/* Use browser DevTools to see which rules are winning */
/* Look for !important overrides */
/* Check for ID selectors */
```

### 2. Validate HTML Structure
```html
<!-- Ensure BEM structure matches CSS expectations -->
<!-- Check for typos in class names -->
<!-- Verify nesting is correct -->
```

### 3. Check Custom Properties
```css
/* Verify variable is defined */
/* Check for typos in variable names */
/* Ensure variable is in scope */
```

### 4. Test Responsive Behavior
- Test at all breakpoints
- Check for horizontal scrolling
- Verify touch targets are large enough

## Performance Tips

1. **Avoid complex selectors**
   - Browser reads right-to-left
   - Keep selectors short and specific

2. **Use CSS custom properties wisely**
   - Don't over-nest calculations
   - Consider browser support

3. **Minimize repaints/reflows**
   - Use transform instead of position
   - Batch DOM updates
   - Use will-change sparingly

4. **Optimize critical CSS**
   - Inline critical styles
   - Lazy load non-critical CSS
   - Remove unused styles

## Resources

- [BEM Methodology](http://getbem.com/)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)
- [CSS Grid Guide](https://css-tricks.com/snippets/css/complete-guide-grid/)
- [Flexbox Guide](https://css-tricks.com/snippets/css/a-guide-to-flexbox/)
- [ITCSS Architecture](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/)

## Questions?

- Check existing components first
- Read the documentation
- Ask in #frontend-help channel
- Create a proof of concept
- Get code review before merging