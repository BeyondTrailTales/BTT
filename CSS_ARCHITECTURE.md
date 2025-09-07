# BTT CSS Architecture Documentation

## Overview

The BeyondTrailTales CSS architecture has been redesigned to eliminate the chaos of 130+ CSS files and 3400+ !important declarations. The new system follows a modular, scalable approach with clear hierarchy and naming conventions.

## Directory Structure

```
assets/css/
├── core/               # Foundation styles
│   ├── reset.css      # CSS reset/normalize
│   ├── variables.css  # CSS custom properties
│   ├── base.css       # Base element styles
│   └── typography.css # Typography system
├── layouts/           # Layout components
│   ├── container.css  # Container system
│   ├── grid.css       # Grid and flex utilities
│   └── spacing.css    # Margin/padding utilities
├── components/        # UI components
│   ├── navigation.css # Navigation styles
│   ├── buttons.css    # Button components
│   ├── forms.css      # Form elements
│   └── cards.css      # Card components
├── pages/             # Page-specific styles
│   ├── dashboard.css
│   ├── trips.css
│   ├── gear.css
│   └── backpacks.css
├── utilities/         # Helper classes
│   ├── helpers.css    # Utility classes
│   └── animations.css # Animation utilities
└── legacy/           # Temporary migration support
    └── migration.css  # Override fixes during transition
```

## CSS Loading Order

The AssetLoader system ensures CSS files load in the correct cascade order:

1. **Reset** (Priority: 100) - Normalize browser defaults
2. **Variables** (Priority: 200) - CSS custom properties
3. **Base** (Priority: 300) - Base HTML elements
4. **Layout** (Priority: 400) - Layout systems
5. **Components** (Priority: 500) - Reusable components
6. **Utilities** (Priority: 600) - Helper classes
7. **Page-specific** (Priority: 700) - Page overrides
8. **Legacy** (Priority: 900) - Temporary migration fixes

## Using the Asset Loader

The Asset Loader is automatically initialized in `template-header.php`:

```php
// Initialize asset loader
$loader = asset_loader();

// Add page-specific CSS
$loader->addPageCss('dashboard');

// Add custom CSS with priority
$loader->addCss('css/custom.css', AssetLoader::CSS_PRIORITY_PAGE);

// Render all CSS
echo $loader->renderCss();
```

## CSS Variables

All design tokens are defined as CSS custom properties in `variables.css`:

### Colors
- `--color-forest-[950-50]` - Forest theme palette
- `--color-success/warning/error/info` - Semantic colors
- `--bg-primary/secondary/tertiary` - Background colors
- `--text-primary/secondary/tertiary` - Text colors

### Typography
- `--font-primary` - Main font stack
- `--text-[xs-5xl]` - Font sizes
- `--font-[light-extrabold]` - Font weights
- `--leading-[tight-loose]` - Line heights

### Spacing
- `--space-[0-32]` - Consistent spacing scale (4px base)

### Layout
- `--container-[sm-2xl]` - Container widths
- `--nav-height` - Navigation height
- `--radius-[sm-full]` - Border radius scale
- `--shadow-[sm-2xl]` - Box shadow scale

## Component Structure

Components follow a consistent naming pattern:

```css
/* Block */
.card { }

/* Element */
.card-header { }
.card-body { }
.card-footer { }

/* Modifier */
.card-elevated { }
.card-sm { }
.card-interactive { }
```

## Utility Classes

Utility classes provide single-purpose styling:

```css
/* Display */
.d-none, .d-flex, .d-grid

/* Spacing */
.m-4, .p-6, .mx-auto

/* Text */
.text-center, .text-muted, .font-bold

/* Responsive */
.md:d-none, .lg:grid-cols-3
```

## Migration Strategy

### Phase 1: Foundation (Current)
- ✅ Create new CSS architecture
- ✅ Implement AssetLoader system
- ✅ Create core CSS files
- ✅ Add migration.css for immediate fixes

### Phase 2: Component Migration
- Port existing components to new structure
- Remove !important declarations
- Test each component thoroughly

### Phase 3: Page Migration
- Update page-specific styles
- Remove redundant CSS files
- Update all page references

### Phase 4: Cleanup
- Remove legacy CSS files
- Remove migration.css
- Performance optimization

## Best Practices

### DO:
- Use CSS variables for all values
- Follow the established naming conventions
- Keep specificity low
- Use utility classes for one-off styles
- Test responsive behavior

### DON'T:
- Use !important (except in utilities)
- Create deeply nested selectors
- Use inline styles
- Add vendor prefixes manually
- Create "fix" files

## Versioning

The AssetLoader automatically handles cache busting:

- **Development**: Uses timestamp for aggressive cache busting
- **Production**: Uses file modification time or app version

## Adding New Styles

### 1. Component CSS
Create in `components/` directory:
```css
/* components/new-component.css */
.new-component {
  /* Use variables */
  padding: var(--space-4);
  color: var(--text-primary);
}
```

### 2. Page CSS
Create in `pages/` directory:
```css
/* pages/new-page.css */
[data-page="new-page"] .specific-style {
  /* Page-specific overrides */
}
```

### 3. Register in AssetLoader
Update `asset-loader.php` if needed:
```php
$coreCssFiles[] = [
  'file' => 'css/components/new-component.css',
  'priority' => self::CSS_PRIORITY_COMPONENTS
];
```

## Debugging

Enable debug mode to see:
- Which CSS files are loaded
- Loading order
- Container sizes

Add `debug-mode` class to body:
```html
<body class="debug-mode">
```

## Performance Considerations

1. **File Consolidation**: Core files are small and focused
2. **Critical CSS**: Essential styles load first
3. **Lazy Loading**: Page-specific CSS loads only when needed
4. **Minification**: Will be added in build process
5. **HTTP/2**: Optimized for multiple small files

## Browser Support

- Modern browsers (last 2 versions)
- CSS Grid and Flexbox required
- CSS Custom Properties required
- No IE support

## Future Enhancements

1. **CSS-in-JS**: Evaluate for component isolation
2. **PostCSS**: Add for autoprefixing and optimization
3. **PurgeCSS**: Remove unused styles in production
4. **CSS Modules**: Consider for true component isolation
5. **Dark Mode**: Full theme switching support