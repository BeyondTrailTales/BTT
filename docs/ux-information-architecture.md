# BeyondTrailTales - Task-First Information Architecture

## Overview
Redesigned to focus on backpacker tasks rather than data management. Every screen should answer: "What is the user trying to accomplish?"

## Primary Navigation (Task-Based)

### 1. 🏔️ Trailhead (Dashboard)
- **Purpose**: Command center for trip planning and gear management
- **URL**: `/` or `/dashboard`
- **Key Actions**:
  - Quick Start (prominent CTAs)
  - Next Trip Overview
  - Pack Status
  - Recent Activity

### 2. 🗺️ Plan Trip (Trips)
- **Purpose**: Plan and track backpacking adventures
- **URL**: `/trips`
- **Key Actions**:
  - New Trip (wizard)
  - Copy Last Trip
  - Use Template
  - View Upcoming Trips
  - Browse Past Adventures

### 3. 🎒 Pack & Gear (Backpack Builder)
- **Purpose**: Build and optimize pack configurations
- **URL**: `/backpacks` or `/packs`
- **Key Actions**:
  - Quick Pack (smart suggestions)
  - Build Custom Pack
  - Weight Calculator
  - Pack Templates

### 4. 📦 My Gear (Gear Library)
- **Purpose**: Manage personal gear inventory
- **URL**: `/gear`
- **Key Actions**:
  - Add New Gear
  - Browse by Category
  - Mark Favorites
  - Track Wear & Replacement

## Secondary Navigation (User Menu)

### User Profile Dropdown:
- 👤 Profile
- 🏆 Achievements & Stats
- ⚙️ Settings
- ❓ Help & Tips
- 🚪 Sign Out

## Global Quick Actions

### ➕ Quick Add (FAB Button)
Floating action button with quick access to:
- Start New Trip
- Quick Pack
- Add Gear Item

## Breadcrumb Structure

Examples:
- `Trailhead`
- `Trailhead › Plan Trip › Weekend in Yosemite`
- `Trailhead › Pack & Gear › Summer Pack`
- `Trailhead › My Gear › Shelter`

## Mobile Navigation (Bottom Bar)

Fixed bottom navigation with 5 primary actions:
1. 🏔️ Trailhead
2. 🗺️ Trips
3. ➕ (Quick Add)
4. 🎒 Packs
5. 📦 Gear

## Language Map (Technical → Backpacker)

### General Terms
- Dashboard → Trailhead
- Records → Items
- Inventory → My Gear
- Weight (grams) → Weight
- Backpack Items → Packed Gear
- Categories → Gear Types

### Trip Terms
- Trip Segments → Trail Sections
- Logistics → Getting There
- Conditions → Trail Intel
- Distance/Elevation → Trail Stats

### Pack Terms
- Base Weight → Pack Weight (without water/food)
- Worn Weight → What You're Wearing
- Consumables → Food & Water
- Pack Sections → Pack Compartments

### Actions
- Create → Start/Add
- Delete → Remove
- Update → Change/Edit
- Submit → Save & Continue
- Cancel → Go Back

## User Flows

### Primary User Journey: Plan a Trip
1. **Trailhead** → See upcoming trips or quick start
2. **Start Trip** → Simple wizard (name, dates, distance)
3. **Pack Selection** → Choose existing or quick pack
4. **Review** → See checklist and stats
5. **Go!** → Trip saved and ready

### Quick Pack Flow
1. **Quick Pack** button from anywhere
2. **Choose Trip Type** → Day hike, overnight, weekend, etc.
3. **Auto-suggest Gear** → Based on trip type and season
4. **Adjust & Save** → Fine-tune and name the pack

### Gear Management Flow
1. **My Gear** → See all owned items
2. **Filter/Search** → Find specific items quickly
3. **Batch Actions** → Add multiple to pack, mark favorites
4. **Quick Edit** → Inline weight/notes updates

## Success Metrics
- Time to create first trip: < 2 minutes
- Clicks to common tasks: ≤ 3
- Mobile task completion rate: 95%+
- User understanding of navigation: Immediate

## Visual Hierarchy

### Primary (High Emphasis)
- Quick action buttons
- Next trip card
- Primary CTAs (Start Trip, Quick Pack)

### Secondary (Medium Emphasis)
- Section headers
- Stats and metrics
- Navigation items

### Tertiary (Low Emphasis)
- Help text
- Timestamps
- Advanced options

## Progressive Disclosure Strategy

### Show First
- Essential trip info (name, dates, distance)
- Total pack weight
- Quick actions

### Show on Demand
- Detailed trail conditions
- Individual gear weights
- Historical data
- Advanced settings

### Hide Until Needed
- Admin functions
- Data export
- Detailed analytics
- System settings
