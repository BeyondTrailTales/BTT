# Gear Integration in Beyond Trail Tales

## Overview
The BTT application now has a comprehensive gear management system with over 350 default items for 7-day backpacking trips.

## Navigation Structure

### Main Navigation
- **Trips** (`/trips.php`) - Plan and track adventures
- **Backpacks** (`/backpacks.php`) - Build and manage packs 
- **My Gear** (`/gear.php`) - Personal gear library ✅ NOW ADDED TO NAV

## How Gear Integrates

### 1. My Gear Page (`/gear.php`)
- **View all gear**: System gear (350+ items) + Custom gear
- **Add custom gear**: Personal items with brand, weight, notes
- **Categories**: Shelter, Sleep, Cooking, Water, Clothing, etc.
- **Search & Filter**: By name, category, weight
- **Drag & Drop**: Items can be dragged to backpack builder

### 2. Backpack Builder (`/backpacks.php`)
The backpack builder has 4 main sections:
- **My Packs**: View and manage saved backpacks
- **Build Pack**: Create new packs with sections
- **Quick Packs**: Pre-made templates
- **My Gear** (tab): Links to gear.php

#### How to add gear to backpacks:
1. Go to "Build Pack" tab
2. Open "Gear Library" panel
3. Search/filter for items
4. **Drag items** from library to pack sections
5. Or click "+" to add items
6. Items are organized in sections:
   - Main Compartment
   - Top Lid
   - Side Pockets
   - External attachments

### 3. Trip Planning (`/trips.php`)
Gear connects to trips through backpacks:
1. Create a trip
2. Select a backpack for the trip
3. The selected backpack includes all its gear
4. Track what you're bringing on each adventure

## Gear Data Structure

### System Gear (gear-default.json)
- **234+ items** across 14 categories
- All brandless, generic items
- Realistic weights in grams
- Categories:
  - Shelter (19 items)
  - Sleep System (14 items)
  - Cooking (30 items)
  - Water (14 items)
  - Clothing (32 items)
  - Footwear (6 items)
  - Navigation (8 items)
  - Hygiene (29 items)
  - First Aid (25 items)
  - Electronics (21 items)
  - Food (25 items)
  - Repair (4 items)
  - Food Storage (4 items)
  - Other (20+ items)

### Custom Gear (user_gear table)
- Personal items specific to each user
- Can include brand names
- Custom weights and notes
- Same categories as system gear

## API Endpoints

### Gear Management
- `GET /ajax-handler.php?route=gear` - Get all gear (system + custom)
- `POST /api/?route=gear` - Add custom gear
- `PUT /api/?route=gear&id={id}` - Update gear
- `DELETE /api/?route=gear&id={id}` - Delete custom gear

### Backpack Management
- `GET /ajax-handler.php?route=backpacks` - Get all backpacks
- `POST /ajax-handler.php?route=backpacks` - Create backpack with gear
- `PUT /ajax-handler.php?route=backpacks&id={id}` - Update backpack

## User Workflow

### Planning a 7-Day Trip:
1. **My Gear** → Browse/add personal gear items
2. **Backpacks** → Create a new pack
3. **Build Pack** → Drag gear from library to pack sections
4. **Save Pack** → Name it (e.g., "7-Day PCT Section")
5. **Trips** → Create new trip
6. **Select Backpack** → Choose the pack you created
7. **Go Adventure!** → Everything is organized and tracked

## Features

### Current Features ✅
- 350+ system gear items
- Custom gear management
- Drag-and-drop pack building
- Weight tracking
- Category organization
- Search and filtering
- Gear library in backpack builder
- Trip-backpack association

### Future Enhancements 🚀
- [ ] Gear wear tracking
- [ ] Shared gear for group trips
- [ ] Gear recommendations based on trip type
- [ ] Weight optimization suggestions
- [ ] Gear checklist for trips
- [ ] Export gear lists
- [ ] Community gear reviews
- [ ] Gear maintenance reminders

## Database Schema

### gear_items (System Gear)
- id, name, category, weight, icon, notes, tags

### user_gear (Custom Gear)
- id, user_id, name, category, weight_g, brand, notes

### backpack_gear (Items in Backpacks)
- backpack_id, gear_id/custom fields, quantity, section

### backpacks
- id, user_id, name, sections (with gear items)

### trips
- id, user_id, backpack_id (links to gear through backpack)

## Testing
- Test page: `/test/test-gear-library.php`
- Shows all loaded gear with stats
- Verifies JSON loading and API integration
