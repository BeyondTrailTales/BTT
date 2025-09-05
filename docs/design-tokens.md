# BeyondTrailTales Design Tokens Documentation

**Version**: 2.0.0  
**Last Updated**: 2025-09-04  
**Location**: `/assets/css/btt-tokens.css`  
**Interactive Guide**: [UI/UX Guide](http://localhost/BTT/ui-ux-guide.php)

## Overview

Design tokens are the visual design atoms of the design system — specifically, they are named entities that store visual design attributes. We use them in place of hard-coded values (such as hex values for color or pixel values for spacing) in order to maintain a scalable and consistent visual system.

## Token Categories

### 🎨 Color System

#### Primary Forest Palette
```css
--btt-forest-deep: #0a2818;     /* Main dark background */
--btt-forest-bright: #10b981;   /* Primary action color */
--btt-mint: #86efac;             /* Accent highlight */
```

**Usage Guidelines:**
- Use `--btt-forest-deep` for main dark backgrounds
- Use `--btt-forest-bright` for primary CTAs and important actions
- Use `--btt-mint` for hover states and highlights
- All colors meet WCAG AA contrast requirements

#### Semantic Colors
```css
--btt-success: #10b981;
--btt-warning: #f59e0b;
--btt-error: #ef4444;
--btt-info: #3b82f6;
```

**When to Use:**
- Success: Confirmations, completions, positive feedback
- Warning: Cautions, important notices, weight limits
- Error: Failures, validation errors, critical issues
- Info: Helpful tips, neutral information

### 📐 Typography System

#### Font Sizes (Fluid)
```css
--btt-text-base: clamp(1rem, 0.95rem + 0.25vw, 1.125rem);
--btt-text-xl: clamp(1.25rem, 1.15rem + 0.5vw, 1.5rem);
--btt-text-3xl: clamp(2rem, 1.75rem + 1.25vw, 2.5rem);
```

**Typography Scale:**
- `xs`: Small labels, helper text
- `sm`: Secondary text, metadata
- `base`: Body copy, standard UI text
- `lg`: Subheadings, emphasis
- `xl` to `4xl`: Headings, hero text

#### Font Weights
```css
--btt-font-normal: 400;
--btt-font-semibold: 600;
--btt-font-bold: 700;
```

### 📏 Spacing System (8pt Grid)

```css
--btt-space-1: 0.25rem;  /* 4px */
--btt-space-2: 0.5rem;   /* 8px */
--btt-space-4: 1rem;     /* 16px */
--btt-space-6: 2rem;     /* 32px */
--btt-space-8: 3rem;     /* 48px */
```

**Usage Patterns:**
- **Component internal spacing**: space-2 to space-4
- **Between components**: space-4 to space-6
- **Section spacing**: space-8 to space-10
- **Page margins**: space-4 on mobile, space-6 on desktop

### 🔲 Border Radius

```css
--btt-radius-sm: 0.375rem;   /* 6px - Inputs, small buttons */
--btt-radius-md: 0.625rem;   /* 10px - Cards, containers */
--btt-radius-lg: 1rem;       /* 16px - Modals, large cards */
--btt-radius-full: 9999px;   /* Pills, badges */
```

**Application:**
- Small elements: `radius-sm`
- Standard components: `radius-md`
- Large surfaces: `radius-lg`
- Circular/pill shapes: `radius-full`

### 🎭 Shadows (Elevation)

```css
--btt-shadow-sm: 0 2px 4px 0 rgb(0 0 0 / 0.06);
--btt-shadow-md: 0 4px 8px 0 rgb(0 0 0 / 0.08);
--btt-shadow-lg: 0 8px 16px 0 rgb(0 0 0 / 0.1);
```

**Elevation Hierarchy:**
- Flat/Base: No shadow
- Raised: `shadow-sm` (hover states)
- Floating: `shadow-md` (cards, dropdowns)
- Overlay: `shadow-lg` (modals, popovers)

### ⚡ Animation & Motion

```css
--btt-duration-fast: 150ms;
--btt-duration-normal: 200ms;
--btt-ease-out: cubic-bezier(0, 0, 0.2, 1);
--btt-ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
```

**Motion Principles:**
- Quick interactions: `duration-fast`
- Standard transitions: `duration-normal`
- Complex animations: `duration-slow`
- Always respect `prefers-reduced-motion`

### 📱 Breakpoints

```css
--btt-screen-sm: 30rem;   /* 480px */
--btt-screen-md: 48rem;   /* 768px */
--btt-screen-lg: 64rem;   /* 1024px */
--btt-screen-xl: 80rem;   /* 1280px */
```

**Responsive Strategy:**
- Mobile-first approach
- Test at 320px minimum width
- Major breakpoints at md and lg
- Fluid typography between breakpoints

## Implementation Examples

### Basic Component
```css
.btt-card {
  padding: var(--btt-card-padding);
  background: var(--btt-glass-white);
  border: 1px solid var(--btt-glass-border);
  border-radius: var(--btt-card-radius);
  box-shadow: var(--btt-card-shadow);
  transition: var(--btt-transition-all);
}

.btt-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--btt-shadow-lg);
  border-color: var(--btt-glass-border-hover);
}
```

### Typography Usage
```css
.heading-primary {
  font-size: var(--btt-text-3xl);
  font-weight: var(--btt-font-bold);
  line-height: var(--btt-leading-tight);
  color: var(--btt-mint);
  margin-bottom: var(--btt-space-4);
}

.body-text {
  font-size: var(--btt-text-base);
  line-height: var(--btt-leading-relaxed);
  color: var(--btt-text-secondary);
}
```

### Spacing Application
```css
.section {
  padding: var(--btt-space-6) var(--btt-space-4);
}

@media (min-width: 48rem) {
  .section {
    padding: var(--btt-space-8) var(--btt-space-6);
  }
}
```

## Token Usage Rules

### DO ✅
- Always use tokens instead of hard-coded values
- Reference semantic colors for UI states
- Apply consistent spacing using the 8pt grid
- Use fluid typography tokens for responsive text
- Test color combinations for WCAG compliance

### DON'T ❌
- Create one-off color values
- Use pixel values for spacing (except for borders)
- Override token values locally
- Mix different shadow systems
- Ignore accessibility tokens

## Migration Guide

### From Old System
```css
/* Old */
.element {
  color: #10b981;
  padding: 16px;
  font-size: 18px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* New */
.element {
  color: var(--btt-forest-bright);
  padding: var(--btt-space-4);
  font-size: var(--btt-text-lg);
  box-shadow: var(--btt-shadow-md);
}
```

## Accessibility Considerations

### Color Contrast
All color combinations meet WCAG AA standards:
- Normal text: 4.5:1 minimum
- Large text: 3:1 minimum
- UI components: 3:1 minimum

### Motion
```css
/* Automatically disabled for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
  /* All duration tokens become 0ms */
}
```

### Touch Targets
```css
--btt-btn-min-height: 44px; /* WCAG minimum */
--btt-input-min-height: 44px;
```

## Performance Notes

### Optimized Glassmorphism
```css
/* Use sparingly - expensive operation */
--btt-blur-sm: blur(4px);   /* Prefer this */
--btt-blur-xl: blur(16px);  /* Avoid when possible */
```

### CSS Variables Performance
- Tokens are computed once and cached
- Changes propagate efficiently through cascade
- Reduce file size by eliminating repetition

## Tools & Resources

- **Token File**: `/assets/css/btt-tokens.css`
- **Interactive Guide**: [http://localhost/BTT/ui-ux-guide.php](http://localhost/BTT/ui-ux-guide.php)
- **Color Contrast Checker**: [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- **Fluid Type Calculator**: [Utopia Type Scale](https://utopia.fyi/type/calculator)

## Version History

### v2.0.0 (2025-09-04)
- Complete token system overhaul
- Added fluid typography
- Implemented 8pt grid spacing
- WCAG AA compliance
- Performance optimizations
- Reduced motion support

### Future Enhancements
- [ ] Dark mode token variants
- [ ] Component-specific token sets
- [ ] Custom property fallbacks
- [ ] Print-specific tokens

---

**Questions?** Check the [UI/UX Guide](http://localhost/BTT/ui-ux-guide.php) for interactive examples.
