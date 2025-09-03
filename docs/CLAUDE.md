# CLAUDE.md - BeyondTrailTales Development Guide

## Project Overview

BeyondTrailTales (HikePack Pro) is a comprehensive hiking and backpacking trip planning application. It helps outdoor enthusiasts plan trips, manage gear, create itineraries, and get AI-powered packing suggestions.

### Core Features
- Trip planning with templates (day hike, weekend, thru-hike, car camping)
- Smart packing list management with weight tracking
- Personal gear inventory (gear box)
- Daily itinerary planning with weather integration
- Photo gallery for trip memories
- AI assistant for packing recommendations
- Mobile-first responsive design

## Architecture Decisions

### Technology Stack
- **Frontend**: React 18+ with hooks
- **State Management**: Context API (migrating to Redux Toolkit)
- **Styling**: Tailwind-inspired utility classes (migrating to styled-components)
- **Icons**: Lucide React
- **Build Tool**: Vite
- **Language**: JavaScript (migrating to TypeScript)

### Key Patterns
1. **Component-Based Architecture**: Atomic design principles
2. **Context Pattern**: For global state management
3. **Custom Hooks**: For reusable logic
4. **Mobile-First Design**: All components optimized for touch
5. **Composition Pattern**: Reusable UI components

## Development Guidelines

### File Structure
```
src/
├── components/      # UI components
├── contexts/       # React contexts
├── hooks/         # Custom hooks
├── services/      # API services
├── utils/         # Helper functions
├── constants/     # App constants
└── types/         # TypeScript types
```

### Component Development
```javascript
// Component template
const ComponentName = ({ prop1, prop2, ...props }) => {
  // Hooks first
  const [state, setState] = useState(initialValue);
  const { contextValue } = useContext(SomeContext);
  
  // Event handlers
  const handleEvent = useCallback(() => {
    // Handler logic
  }, [dependencies]);
  
  // Effects
  useEffect(() => {
    // Side effects
  }, [dependencies]);
  
  // Render
  return (
    <div className="component-wrapper">
      {/* Component JSX */}
    </div>
  );
};
```

### Naming Conventions
- **Components**: PascalCase (e.g., `TripBuilder`)
- **Files**: PascalCase for components, camelCase for utilities
- **Variables**: camelCase
- **Constants**: UPPER_SNAKE_CASE
- **CSS Classes**: kebab-case
- **Event Handlers**: handleEventName

## Code Style and Conventions

### JavaScript/React
```javascript
// Prefer functional components
const MyComponent = () => { };

// Use destructuring
const { name, email } = user;

// Use optional chaining
const city = user?.address?.city;

// Use nullish coalescing
const displayName = name ?? 'Anonymous';

// Prefer early returns
if (!data) return null;

// Use semantic HTML
<button type="button" onClick={handleClick}>
  Click me
</button>
```

### Import Order
```javascript
// 1. React imports
import React, { useState, useEffect } from 'react';

// 2. Third-party imports
import * as Icons from 'lucide-react';

// 3. Context imports
import { useAuth } from '../contexts/AuthContext';

// 4. Component imports
import Button from '../components/common/Button';

// 5. Utility imports
import { formatWeight } from '../utils/formatters';

// 6. Constants
import { GEAR_CATEGORIES } from '../constants';

// 7. Types (when using TypeScript)
import type { Trip } from '../types';
```

## Component Patterns

### Common UI Components
```javascript
// Button component
<DuoButton 
  variant="primary" 
  size="md" 
  onClick={handleClick}
  icon={Icons.Plus}
>
  Add Item
</DuoButton>

// Card component
<MobileCard gradient padding="p-4">
  <h3>Card Title</h3>
  <p>Card content</p>
</MobileCard>

// Modal component
<MobileModal 
  isOpen={isOpen} 
  onClose={handleClose} 
  title="Modal Title"
>
  <ModalContent />
</MobileModal>
```

### Feature Components
```javascript
// List with search
const [searchQuery, setSearchQuery] = useState('');
const filteredItems = useMemo(() => 
  fuzzySearch(searchQuery, items),
  [searchQuery, items]
);

// Form with validation
const [formData, setFormData] = useState(initialData);
const [errors, setErrors] = useState({});

const validateForm = () => {
  const newErrors = {};
  if (!formData.name) newErrors.name = 'Name is required';
  setErrors(newErrors);
  return Object.keys(newErrors).length === 0;
};
```

## State Management

### Context Usage
```javascript
// Auth Context
const { user, login, logout, isAuthenticated } = useAuth();

// App Context  
const { trips, activeTrip, gearBox, addTrip, updateTrip } = useApp();

// AI Context
const { messages, sendMessage, isProcessing } = useAI();
```

### State Updates
```javascript
// Immutable updates
setTrips(prev => [...prev, newTrip]);
setTrip(prev => ({ ...prev, ...updates }));

// Array operations
setItems(prev => prev.filter(item => item.id !== id));
setItems(prev => prev.map(item => 
  item.id === id ? { ...item, ...updates } : item
));
```

## API Integration

### Service Pattern
```javascript
// API service
class TripService {
  static async getTrips() {
    try {
      const response = await apiClient.get('/trips');
      return response.data;
    } catch (error) {
      console.error('Failed to fetch trips:', error);
      throw error;
    }
  }
  
  static async createTrip(tripData) {
    return apiClient.post('/trips', tripData);
  }
}

// Usage in component
useEffect(() => {
  const fetchTrips = async () => {
    try {
      setLoading(true);
      const data = await TripService.getTrips();
      setTrips(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };
  
  fetchTrips();
}, []);
```

## Testing Guidelines

### Unit Tests
```javascript
// Component test
describe('TripCard', () => {
  it('displays trip name and duration', () => {
    const trip = { name: 'Test Trip', duration: 3 };
    render(<TripCard trip={trip} />);
    
    expect(screen.getByText('Test Trip')).toBeInTheDocument();
    expect(screen.getByText('3 days')).toBeInTheDocument();
  });
});

// Hook test
describe('usePackingList', () => {
  it('calculates total weight correctly', () => {
    const { result } = renderHook(() => usePackingList(items));
    expect(result.current.totalWeight).toBe(1500);
  });
});
```

### Integration Tests
```javascript
// Test user flow
it('allows user to create and save a trip', async () => {
  render(<App />);
  
  // Navigate to new trip
  userEvent.click(screen.getByText('New Trip'));
  
  // Fill form
  userEvent.type(screen.getByLabelText('Trip Name'), 'Test Trip');
  
  // Submit
  userEvent.click(screen.getByText('Create Trip'));
  
  // Verify
  await waitFor(() => {
    expect(screen.getByText('Test Trip')).toBeInTheDocument();
  });
});
```

## Performance Optimization

### Component Optimization
```javascript
// Memoize expensive computations
const totalWeight = useMemo(() => 
  items.reduce((sum, item) => sum + item.weight * item.quantity, 0),
  [items]
);

// Memoize callbacks
const handleItemToggle = useCallback((itemId) => {
  setItems(prev => prev.map(item =>
    item.id === itemId ? { ...item, packed: !item.packed } : item
  ));
}, []);

// Memoize components
const ExpensiveComponent = React.memo(({ data }) => {
  return <ComplexVisualization data={data} />;
});
```

### Code Splitting
```javascript
// Route-based splitting
const TripBuilder = lazy(() => import('./features/trips/TripBuilder'));

// Feature-based splitting
const AIAssistant = lazy(() => import('./features/ai/AIAssistant'));
```

## Backpack UX Guidelines

### Visual Design Principles
```javascript
// Backpack section colors
const BACKPACK_COLORS = {
  topLid: '#3b82f6',      // Blue - Quick access items
  mainBody: '#10b981',    // Green - Core gear
  frontPocket: '#f59e0b', // Orange - Navigation/safety
  sidePockets: '#8b5cf6', // Purple - Water/cooking
  bottom: '#ef4444'       // Red - Shelter/heavy items
};

// Capacity thresholds
const CAPACITY_STATES = {
  optimal: { max: 50, color: '#10b981' },    // Green
  warning: { max: 80, color: '#f59e0b' },    // Yellow
  critical: { max: 100, color: '#ef4444' }   // Red
};
```

### Animation Standards
```javascript
// Use consistent timing for all backpack animations
const ANIMATION_TIMING = {
  instant: 0,
  fast: 150,      // Quick UI responses
  normal: 300,    // Standard transitions
  smooth: 500,    // Weight redistribution
  dramatic: 800   // Major state changes
};

// Example animation implementation
const animateWeightChange = {
  transition: `all ${ANIMATION_TIMING.smooth}ms cubic-bezier(0.4, 0, 0.2, 1)`,
  willChange: 'transform, opacity'
};
```

### Mobile Interaction Patterns
```javascript
// Touch target sizes (minimum)
const TOUCH_TARGETS = {
  minimum: 44,     // iOS guideline
  preferred: 48,   // Material Design
  comfortable: 56  // For primary actions
};

// Gesture handlers
const handleSwipeAction = (direction) => {
  if (direction === 'left') markAsPacked();
  if (direction === 'right') removeFromList();
  // Provide haptic feedback where available
};
```

### Smart Packing Logic
```javascript
// Item categorization rules
const categorizeItem = (item) => {
  // Priority order for auto-assignment
  const categoryMap = {
    'water_bottle': 'sidePockets',
    'first_aid': 'frontPocket',
    'tent': 'bottom',
    'clothing': 'mainBody',
    'snacks': 'topLid'
  };
  
  return categoryMap[item.type] || autoSuggestCategory(item);
};

// Weight optimization algorithm
const optimizeWeight = (items) => {
  // Balance weight across sections
  // Heavy items to bottom
  // Frequently used items to top/front
  // Even left/right distribution
};
```

### Accessibility Requirements
```javascript
// ARIA labels for backpack sections
<BackpackSection
  role="region"
  aria-label={`${section.name} containing ${section.items.length} items`}
  aria-describedby={`${section.id}-description`}
  tabIndex={0}
>

// Keyboard navigation
const keyboardShortcuts = {
  'Tab': 'Navigate between sections',
  'Enter': 'Expand/collapse section',
  'Space': 'Toggle item packed state',
  'Delete': 'Remove selected item',
  'Cmd+Z': 'Undo last action'
};
```

### Performance Optimization
```javascript
// Virtualize long item lists
import { FixedSizeList } from 'react-window';

// Memoize expensive calculations
const sectionStats = useMemo(() => 
  calculateSectionStats(items),
  [items]
);

// Debounce real-time updates
const debouncedWeightUpdate = useMemo(
  () => debounce(updateWeight, 300),
  []
);
```

## Common Tasks

### Adding a New Feature
1. Create feature folder in `src/features/`
2. Add components, hooks, and services
3. Update routing if needed
4. Add to navigation
5. Write tests
6. Update documentation

### Adding a New Gear Category
1. Update `constants/gear.js` with new category
2. Add icon mapping in `utils/icons.js`
3. Update gear search filters
4. Add sample items if needed
5. Test gear selection flow

### Creating a New Context
```javascript
// 1. Create context file
const MyContext = createContext(null);

export const MyProvider = ({ children }) => {
  const [state, setState] = useState(initialState);
  
  const value = useMemo(() => ({
    state,
    setState,
    // other methods
  }), [state]);
  
  return (
    <MyContext.Provider value={value}>
      {children}
    </MyContext.Provider>
  );
};

export const useMyContext = () => {
  const context = useContext(MyContext);
  if (!context) {
    throw new Error('useMyContext must be used within MyProvider');
  }
  return context;
};
```

## Troubleshooting

### Common Issues

#### State Not Updating
```javascript
// Wrong - mutating state
items.push(newItem);
setItems(items);

// Correct - creating new array
setItems([...items, newItem]);
```

#### Memory Leaks
```javascript
useEffect(() => {
  const timer = setTimeout(() => {
    // Do something
  }, 1000);
  
  // Always cleanup
  return () => clearTimeout(timer);
}, []);
```

#### Performance Issues
- Check for missing dependencies in useMemo/useCallback
- Look for unnecessary re-renders with React DevTools
- Verify list components have proper keys
- Check for large data in component state

### Debugging Tips
```javascript
// Log renders
useEffect(() => {
  console.log('Component rendered');
});

// Track state changes
useEffect(() => {
  console.log('State changed:', state);
}, [state]);

// Performance profiling
console.time('expensive operation');
// ... operation
console.timeEnd('expensive operation');
```

## Future Considerations

### Planned Features
- Offline support with service workers
- Real-time collaboration on trips
- Trail recommendation engine
- Weather alerts integration
- Gear marketplace
- Social features (share trips, follow hikers)

### Architecture Evolution
- Migration to TypeScript (in progress)
- Redux Toolkit adoption
- GraphQL API integration
- React Native mobile app
- Micro-frontend architecture

### Performance Goals
- Sub-3s initial load time
- 60fps scrolling performance
- < 300KB initial bundle
- Progressive image loading
- Optimistic UI updates

## Best Practices Summary

1. **Keep components small and focused**
2. **Use custom hooks for reusable logic**
3. **Memoize expensive operations**
4. **Handle loading and error states**
5. **Write semantic, accessible HTML**
6. **Test critical user paths**
7. **Document complex logic**
8. **Follow established patterns**
9. **Optimize for mobile first**
10. **Consider performance early**

## Resources

- [React Documentation](https://react.dev)
- [Context7 Standards](internal-link)
- [Project Architecture](./architecture.md)
- [Task List](./tasklist.md)
- [API Documentation](./docs/api.md)

---

Remember: When in doubt, prioritize user experience, code maintainability, and performance. Happy coding!