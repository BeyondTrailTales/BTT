# Backpack Management Visual Mockups & Component Library

## Visual Component Specifications

### 1. Backpack Card Component

```jsx
// Backpack Card Structure
<BackpackCard>
  {/* Header Section */}
  <CardHeader>
    <TypeIndicator type={backpack.type} />
    <CardTitle>{backpack.name}</CardTitle>
    <ActionMenu>
      <IconButton icon="more-vertical" />
    </ActionMenu>
  </CardHeader>
  
  {/* Visual Backpack Preview */}
  <VisualPreview>
    <BackpackSilhouette type={backpack.type}>
      <FillIndicator percentage={capacityUsed} />
      <SectionDots sections={backpack.sections} />
    </BackpackSilhouette>
  </VisualPreview>
  
  {/* Stats Grid */}
  <StatsGrid>
    <Stat icon="weight" value={`${totalWeight}kg`} />
    <Stat icon="capacity" value={`${capacity}L`} />
    <Stat icon="items" value={itemCount} />
    <Stat icon="sections" value={sectionCount} />
  </StatsGrid>
  
  {/* Action Bar */}
  <ActionBar>
    <Button variant="secondary" size="small">Quick View</Button>
    <Button variant="primary" size="small">Configure</Button>
  </ActionBar>
</BackpackCard>
```

#### Visual Specifications

```css
/* Backpack Card Dimensions */
.backpack-card {
  width: 320px;
  height: 420px;
  border-radius: 24px;
  padding: 24px;
  
  /* Glass morphism */
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.1) 0%,
    rgba(255, 255, 255, 0.05) 100%
  );
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  box-shadow: 
    0 8px 32px 0 rgba(31, 38, 135, 0.15),
    inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}

/* Type Indicator Badge */
.type-indicator {
  position: absolute;
  top: -12px;
  left: 24px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  
  /* Type-specific gradients */
  &.daypack {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
  }
  &.weekend {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
  }
  &.thru-hike {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
  }
}

/* Visual Preview */
.visual-preview {
  height: 160px;
  margin: 20px 0;
  position: relative;
  
  .backpack-silhouette {
    width: 100%;
    height: 100%;
    position: relative;
    
    /* SVG paths for different backpack types */
    &.daypack {
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 240'%3E%3Cpath d='M100 20 Q60 20 50 50 L40 180 Q40 220 80 220 L120 220 Q160 220 160 180 L150 50 Q140 20 100 20' fill='%23e0e7ff'/%3E%3C/svg%3E");
    }
  }
  
  .fill-indicator {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: var(--type-gradient);
    mask-image: inherit;
    mask-size: contain;
    mask-repeat: no-repeat;
    mask-position: bottom;
    transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  }
}

/* Section Dots */
.section-dots {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  
  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
    
    &:hover {
      transform: scale(1.5);
      box-shadow: 0 0 8px currentColor;
    }
  }
}
```

### 2. Backpack Type Selector

```jsx
// Visual Type Selector Component
<TypeSelector value={selectedType} onChange={handleTypeChange}>
  <TypeGrid>
    {backpackTypes.map(type => (
      <TypeOption
        key={type.id}
        selected={selectedType === type.id}
        onClick={() => handleTypeChange(type.id)}
      >
        <TypeVisual>
          <BackpackIcon type={type.id} size="large" />
          <CapacityRange>{type.capacityRange}</CapacityRange>
        </TypeVisual>
        <TypeInfo>
          <TypeName>{type.name}</TypeName>
          <TypeDescription>{type.description}</TypeDescription>
        </TypeInfo>
        <TypeFeatures>
          {type.features.map(feature => (
            <FeatureBadge key={feature}>{feature}</FeatureBadge>
          ))}
        </TypeFeatures>
      </TypeOption>
    ))}
  </TypeGrid>
</TypeSelector>
```

#### Type Visual Specifications

```css
/* Type Option Card */
.type-option {
  padding: 24px;
  border-radius: 16px;
  border: 2px solid transparent;
  background: var(--glass-background);
  cursor: pointer;
  transition: all 0.3s ease;
  
  &:hover {
    border-color: var(--type-color);
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  }
  
  &.selected {
    border-color: var(--type-color);
    background: linear-gradient(
      135deg,
      rgba(var(--type-color-rgb), 0.1) 0%,
      rgba(var(--type-color-rgb), 0.05) 100%
    );
  }
}

/* Backpack Icons */
.backpack-icon {
  width: 80px;
  height: 100px;
  position: relative;
  
  /* Animated fill on hover */
  .icon-fill {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: 0;
    background: var(--type-gradient);
    mask-image: url('backpack-icon.svg');
    transition: height 0.5s ease;
  }
  
  &:hover .icon-fill {
    height: 100%;
  }
}
```

### 3. Section Management Interface

```jsx
// Section Tab Component
<SectionTabs>
  {sections.map((section, index) => (
    <SectionTab
      key={section.id}
      active={activeSection === section.id}
      onClick={() => setActiveSection(section.id)}
      style={{ '--section-color': section.color }}
    >
      <TabVisual>
        <SectionIcon>{section.icon}</SectionIcon>
        <CapacityBar>
          <CapacityFill percentage={section.utilization} />
        </CapacityBar>
      </TabVisual>
      <TabContent>
        <SectionName>{section.name}</SectionName>
        <SectionStats>
          <Weight>{(section.currentWeight / 1000).toFixed(1)}kg</Weight>
          <ItemCount>{section.items.length} items</ItemCount>
        </SectionStats>
      </TabContent>
      <TabIndicator active={activeSection === section.id} />
    </SectionTab>
  ))}
</SectionTabs>
```

#### Section Tab Specifications

```css
/* Section Tab Layout */
.section-tab {
  flex: 1;
  padding: 16px;
  border-radius: 12px;
  border: 2px solid transparent;
  background: var(--glass-background);
  cursor: pointer;
  position: relative;
  transition: all 0.3s ease;
  
  &:hover {
    background: rgba(var(--section-color-rgb), 0.1);
    border-color: var(--section-color);
  }
  
  &.active {
    background: linear-gradient(
      135deg,
      rgba(var(--section-color-rgb), 0.15) 0%,
      rgba(var(--section-color-rgb), 0.05) 100%
    );
    border-color: var(--section-color);
  }
}

/* Capacity Visualization */
.capacity-bar {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  margin: 8px 0;
  overflow: hidden;
  
  .capacity-fill {
    height: 100%;
    background: var(--section-color);
    border-radius: 2px;
    transition: width 0.5s ease;
    
    /* Pulse animation when near capacity */
    &.warning {
      animation: pulse 2s infinite;
    }
  }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}
```

### 4. Backpack Visualizer Component

```jsx
// 3D-style Backpack Visualizer
<BackpackVisualizer>
  <BackpackContainer>
    <BackpackSVG viewBox="0 0 300 400">
      {/* Main backpack shape */}
      <defs>
        <linearGradient id="backpackGradient">
          <stop offset="0%" stopColor="#1a1a2e" />
          <stop offset="100%" stopColor="#0f0f1e" />
        </linearGradient>
      </defs>
      
      {/* Backpack sections */}
      <g className="backpack-sections">
        {/* Bottom compartment */}
        <path
          d={bottomPath}
          fill={sections.bottom.color}
          opacity={sections.bottom.items.length > 0 ? 0.8 : 0.3}
          onClick={() => selectSection('bottom')}
        />
        
        {/* Main body */}
        <path
          d={mainBodyPath}
          fill={sections.mainBody.color}
          opacity={sections.mainBody.items.length > 0 ? 0.8 : 0.3}
          onClick={() => selectSection('mainBody')}
        />
        
        {/* Front pocket */}
        <path
          d={frontPocketPath}
          fill={sections.frontPocket.color}
          opacity={sections.frontPocket.items.length > 0 ? 0.8 : 0.3}
          onClick={() => selectSection('frontPocket')}
        />
        
        {/* Side pockets */}
        <path
          d={leftSidePocketPath}
          fill={sections.sidePockets.color}
          opacity={sections.sidePockets.items.length > 0 ? 0.8 : 0.3}
        />
        <path
          d={rightSidePocketPath}
          fill={sections.sidePockets.color}
          opacity={sections.sidePockets.items.length > 0 ? 0.8 : 0.3}
        />
        
        {/* Top lid */}
        <path
          d={topLidPath}
          fill={sections.topLid.color}
          opacity={sections.topLid.items.length > 0 ? 0.8 : 0.3}
          onClick={() => selectSection('topLid')}
        />
      </g>
      
      {/* Weight distribution indicator */}
      <circle
        cx={centerOfGravity.x}
        cy={centerOfGravity.y}
        r="8"
        fill="#fff"
        opacity="0.8"
        className="center-of-gravity"
      />
    </BackpackSVG>
  </BackpackContainer>
  
  <VisualizerControls>
    <RotateButton onClick={rotate3D}>
      <RotateIcon /> 3D View
    </RotateButton>
    <ResetButton onClick={resetView}>
      Reset
    </ResetButton>
  </VisualizerControls>
</BackpackVisualizer>
```

#### Visualizer Specifications

```css
/* 3D Backpack Visualizer */
.backpack-visualizer {
  width: 100%;
  max-width: 400px;
  height: 500px;
  position: relative;
  perspective: 1000px;
}

.backpack-container {
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  transition: transform 0.6s ease;
  
  &.rotating {
    animation: rotate3d 20s linear infinite;
  }
}

@keyframes rotate3d {
  from { transform: rotateY(0deg); }
  to { transform: rotateY(360deg); }
}

/* Interactive sections */
.backpack-sections path {
  cursor: pointer;
  transition: all 0.3s ease;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  
  &:hover {
    opacity: 1 !important;
    filter: 
      drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3))
      brightness(1.2);
    transform: translateZ(10px);
  }
  
  &.selected {
    stroke: #fff;
    stroke-width: 3;
    stroke-dasharray: 5, 5;
    animation: dash 0.5s linear infinite;
  }
}

@keyframes dash {
  to { stroke-dashoffset: -10; }
}

/* Center of gravity indicator */
.center-of-gravity {
  filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.8));
  animation: pulse 2s ease-in-out infinite;
  
  &.optimal {
    fill: #10b981;
  }
  
  &.warning {
    fill: #f59e0b;
  }
  
  &.critical {
    fill: #ef4444;
  }
}
```

### 5. Gear Selection Modal

```jsx
// Enhanced Gear Selector
<GearSelector>
  <SelectorHeader>
    <BackButton onClick={goBack} />
    <Title>Add to {section.name}</Title>
    <CloseButton onClick={close} />
  </SelectorHeader>
  
  <SearchSection>
    <SearchInput
      placeholder="Search gear..."
      value={searchQuery}
      onChange={handleSearch}
    />
    <FilterChips>
      <Chip active onClick={() => setFilter('suggested')}>
        Suggested
      </Chip>
      <Chip onClick={() => setFilter('essential')}>
        Essential
      </Chip>
      <Chip onClick={() => setFilter('lightweight')}>
        Lightweight
      </Chip>
    </FilterChips>
  </SearchSection>
  
  <CategoryGrid>
    {categories.map(category => (
      <CategoryCard
        key={category.id}
        selected={selectedCategory === category.id}
        onClick={() => selectCategory(category.id)}
      >
        <CategoryIcon>{category.icon}</CategoryIcon>
        <CategoryName>{category.name}</CategoryName>
        <ItemCount>{category.itemCount}</ItemCount>
      </CategoryCard>
    ))}
  </CategoryGrid>
  
  <GearList>
    {filteredGear.map(item => (
      <GearItemCard
        key={item.id}
        selected={selectedItems.includes(item.id)}
        onClick={() => toggleItem(item.id)}
      >
        <ItemCheckbox checked={selectedItems.includes(item.id)} />
        <ItemImage src={item.image} alt={item.name} />
        <ItemInfo>
          <ItemName>{item.name}</ItemName>
          <ItemDetails>
            <Weight>{item.weight}g</Weight>
            <Brand>{item.brand}</Brand>
          </ItemDetails>
        </ItemInfo>
        <SuggestedBadge show={item.suggested}>
          Recommended
        </SuggestedBadge>
      </GearItemCard>
    ))}
  </GearList>
  
  <SelectorFooter>
    <Summary>
      <SelectedCount>{selectedItems.length} items</SelectedCount>
      <TotalWeight>{totalWeight}g</TotalWeight>
    </Summary>
    <AddButton onClick={addItems}>
      Add to {section.name}
    </AddButton>
  </SelectorFooter>
</GearSelector>
```

#### Gear Selector Specifications

```css
/* Gear Selector Modal */
.gear-selector {
  width: 90vw;
  max-width: 800px;
  height: 90vh;
  max-height: 700px;
  background: var(--modal-background);
  border-radius: 24px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Category Grid */
.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
  padding: 16px;
  background: rgba(0, 0, 0, 0.05);
}

.category-card {
  padding: 16px;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  
  &:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-2px);
  }
  
  &.selected {
    background: var(--primary-color);
    color: white;
  }
}

/* Gear Item Card */
.gear-item-card {
  display: flex;
  align-items: center;
  padding: 16px;
  border-radius: 12px;
  background: var(--card-background);
  cursor: pointer;
  transition: all 0.2s ease;
  
  &:hover {
    background: rgba(255, 255, 255, 0.05);
  }
  
  &.selected {
    background: rgba(var(--primary-rgb), 0.1);
    border: 2px solid var(--primary-color);
  }
}

/* Suggested Badge */
.suggested-badge {
  padding: 4px 8px;
  border-radius: 12px;
  background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
  color: white;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
```

### 6. Mobile-Specific Components

```jsx
// Mobile Backpack Card
<MobileBackpackCard>
  <SwipeableCard onSwipe={handleSwipe}>
    <CardHeader>
      <TypeBadge type={backpack.type} />
      <Title>{backpack.name}</Title>
      <MenuButton onClick={toggleMenu} />
    </CardHeader>
    
    <CompactVisual>
      <MiniBackpackIcon type={backpack.type} />
      <CapacityRing percentage={capacityUsed} />
    </CompactVisual>
    
    <QuickStats>
      <StatPill icon="weight">{weight}kg</StatPill>
      <StatPill icon="items">{items}</StatPill>
      <StatPill icon="capacity">{capacity}L</StatPill>
    </QuickStats>
    
    <SwipeActions>
      <SwipeAction type="edit">Edit</SwipeAction>
      <SwipeAction type="duplicate">Copy</SwipeAction>
      <SwipeAction type="delete" danger>Delete</SwipeAction>
    </SwipeActions>
  </SwipeableCard>
</MobileBackpackCard>
```

#### Mobile Specifications

```css
/* Mobile Card Dimensions */
.mobile-backpack-card {
  width: 100%;
  min-height: 140px;
  margin-bottom: 16px;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
}

/* Swipeable interactions */
.swipeable-card {
  transform: translateX(0);
  transition: transform 0.3s ease;
  
  &.swiping {
    transition: none;
  }
  
  &.swiped-left {
    transform: translateX(-80px);
  }
  
  &.swiped-right {
    transform: translateX(80px);
  }
}

/* Compact Visual */
.compact-visual {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 60px;
  position: relative;
}

.capacity-ring {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: conic-gradient(
    var(--type-color) 0deg,
    var(--type-color) calc(var(--percentage) * 3.6deg),
    rgba(255, 255, 255, 0.1) calc(var(--percentage) * 3.6deg)
  );
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Touch-optimized buttons */
.touch-button {
  min-width: 44px;
  min-height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  -webkit-tap-highlight-color: transparent;
  
  &:active {
    transform: scale(0.95);
    background: rgba(255, 255, 255, 0.1);
  }
}
```

### 7. Loading States and Skeletons

```jsx
// Backpack Card Skeleton
<BackpackCardSkeleton>
  <SkeletonHeader>
    <SkeletonBadge />
    <SkeletonTitle />
    <SkeletonAction />
  </SkeletonHeader>
  
  <SkeletonVisual>
    <SkeletonBackpack />
  </SkeletonVisual>
  
  <SkeletonStats>
    <SkeletonStat />
    <SkeletonStat />
    <SkeletonStat />
    <SkeletonStat />
  </SkeletonStats>
  
  <SkeletonActions>
    <SkeletonButton />
    <SkeletonButton />
  </SkeletonActions>
</BackpackCardSkeleton>
```

#### Skeleton Specifications

```css
/* Skeleton animations */
@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

.skeleton {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.1) 50%,
    rgba(255, 255, 255, 0.05) 100%
  );
  background-size: 1000px 100%;
  animation: shimmer 2s infinite;
  border-radius: var(--skeleton-radius, 8px);
}

.skeleton-badge {
  width: 80px;
  height: 24px;
  --skeleton-radius: 12px;
}

.skeleton-title {
  width: 60%;
  height: 24px;
}

.skeleton-backpack {
  width: 120px;
  height: 150px;
  margin: 20px auto;
  --skeleton-radius: 16px;
}
```

### 8. Animation Specifications

```javascript
// Framer Motion animations
export const animations = {
  // Card entrance
  cardEntrance: {
    initial: { opacity: 0, y: 20 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.4, ease: [0.4, 0, 0.2, 1] }
  },
  
  // Section transitions
  sectionChange: {
    initial: { opacity: 0, x: -20 },
    animate: { opacity: 1, x: 0 },
    exit: { opacity: 0, x: 20 },
    transition: { duration: 0.3 }
  },
  
  // Item addition
  itemAdd: {
    initial: { scale: 0, opacity: 0 },
    animate: { scale: 1, opacity: 1 },
    transition: { type: "spring", stiffness: 500, damping: 30 }
  },
  
  // Weight update
  weightUpdate: {
    animate: { scale: [1, 1.1, 1] },
    transition: { duration: 0.3 }
  },
  
  // Capacity warning
  capacityWarning: {
    animate: { 
      scale: [1, 1.05, 1],
      borderColor: ["#ef4444", "#fbbf24", "#ef4444"]
    },
    transition: { duration: 1, repeat: Infinity }
  }
}

// GSAP animations for complex interactions
export const complexAnimations = {
  // 3D rotation
  rotate3D: (element) => {
    gsap.to(element, {
      rotateY: 360,
      duration: 20,
      ease: "none",
      repeat: -1
    })
  },
  
  // Gear transfer
  transferGear: (item, target) => {
    const timeline = gsap.timeline()
    
    timeline
      .to(item, {
        scale: 0.8,
        duration: 0.2
      })
      .to(item, {
        x: target.x - item.x,
        y: target.y - item.y,
        duration: 0.6,
        ease: "power2.inOut"
      })
      .to(item, {
        scale: 0,
        opacity: 0,
        duration: 0.2,
        onComplete: () => {
          // Update state
          addItemToSection(item, target)
        }
      })
  },
  
  // Section highlight
  highlightSection: (section) => {
    gsap.to(section, {
      strokeWidth: 4,
      strokeOpacity: 1,
      duration: 0.3,
      yoyo: true,
      repeat: 2
    })
  }
}
```

### 9. Responsive Breakpoints

```css
/* Responsive design system */
:root {
  /* Mobile first breakpoints */
  --mobile: 320px;
  --tablet: 768px;
  --desktop: 1024px;
  --wide: 1440px;
}

/* Backpack grid responsive */
.backpack-grid {
  display: grid;
  gap: 24px;
  padding: 24px;
  
  /* Mobile: 1 column */
  @media (max-width: 767px) {
    grid-template-columns: 1fr;
  }
  
  /* Tablet: 2 columns */
  @media (min-width: 768px) and (max-width: 1023px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  /* Desktop: 3 columns */
  @media (min-width: 1024px) and (max-width: 1439px) {
    grid-template-columns: repeat(3, 1fr);
  }
  
  /* Wide: 4 columns */
  @media (min-width: 1440px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Builder layout responsive */
.backpack-builder {
  display: grid;
  gap: 24px;
  
  /* Mobile: Stacked */
  @media (max-width: 767px) {
    grid-template-columns: 1fr;
    
    .visualizer { order: 1; }
    .sections { order: 2; }
    .settings { order: 3; }
  }
  
  /* Tablet: 2 columns */
  @media (min-width: 768px) and (max-width: 1023px) {
    grid-template-columns: 1fr 1fr;
    
    .visualizer { grid-column: 1 / -1; }
  }
  
  /* Desktop: 3 columns */
  @media (min-width: 1024px) {
    grid-template-columns: 300px 1fr 300px;
  }
}
```

### 10. Interaction States

```css
/* Interactive state specifications */

/* Hover states */
.interactive-element {
  /* Base state */
  transition: all 0.2s ease;
  
  /* Hover */
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  /* Active/pressed */
  &:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  /* Focus (keyboard navigation) */
  &:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
  }
  
  /* Disabled */
  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
  }
}

/* Drag and drop states */
.draggable-item {
  /* Dragging */
  &.dragging {
    opacity: 0.5;
    transform: scale(1.05);
    cursor: grabbing;
  }
  
  /* Drag over */
  &.drag-over {
    background: rgba(59, 130, 246, 0.1);
    border: 2px dashed #3b82f6;
  }
}

/* Loading states */
.loading-state {
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

/* Selection states */
.selectable-item {
  /* Multi-select mode */
  &.selection-mode {
    cursor: pointer;
    
    &:hover {
      background: rgba(59, 130, 246, 0.05);
    }
  }
  
  /* Selected */
  &.selected {
    background: rgba(59, 130, 246, 0.1);
    border: 2px solid #3b82f6;
  }
}
```

## Summary

This visual mockup guide provides detailed specifications for implementing the backpack management system's UI components. Key highlights include:

1. **Consistent Visual Language**: Glass morphism effects, gradient accents, and smooth animations
2. **Type-Based Color System**: Each backpack type has a unique color identity
3. **Interactive Feedback**: Clear hover, active, and focus states
4. **Mobile Optimization**: Touch-friendly targets and swipe gestures
5. **Performance Considerations**: Skeleton loading states and progressive enhancement
6. **Accessibility**: Proper focus indicators and ARIA support

The design system ensures a cohesive, modern, and user-friendly experience across all backpack management features.