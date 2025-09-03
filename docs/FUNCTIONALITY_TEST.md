# BeyondTrailTales Functionality Test Checklist

Use this checklist to verify all features are working properly.

## Setup
- [ ] Access app at: http://localhost/beyondtrailtales/beyondtrailtales-app/dist/index.php
- [ ] Clear browser cache and localStorage if testing fresh install

## 1. Authentication Flow
- [ ] Homepage loads without errors
- [ ] Navigate to Login page
- [ ] "Use Demo Account" button fills credentials
- [ ] Login with demo@beyondtrailtales.com / demo123
- [ ] Redirected to trips page after login
- [ ] User name appears in header
- [ ] Logout button works
- [ ] After logout, protected routes redirect to login

## 2. Navigation
- [ ] All nav links work (My Trips, Gear Box, Profile, Settings)
- [ ] Mobile menu opens and closes
- [ ] Direct URL access works (e.g., /trips, /gear)
- [ ] Browser back/forward buttons work
- [ ] Logo returns to home

## 3. Trip Management
### Create New Trip
- [ ] "New Trip" button opens form
- [ ] Form validation works (required fields)
- [ ] Date picker works
- [ ] Trip template selector works
- [ ] Create trip successfully
- [ ] Redirected to trip detail page

### Trip Gallery
- [ ] All trips display in grid
- [ ] Trip cards show correct info
- [ ] Click trip card opens detail view
- [ ] Empty state shows when no trips

### Trip Detail
- [ ] All tabs display (Packing List, Itinerary, Photos, Notes)
- [ ] Trip info shows correctly
- [ ] Edit trip info works
- [ ] Delete trip works (with confirmation)

## 4. Gear Management
### Packing List (in Trip)
- [ ] Search gear works
- [ ] Fuzzy search returns results
- [ ] Add item to list
- [ ] Remove item from list
- [ ] Toggle packed status
- [ ] Weight calculations update
- [ ] Custom item can be added

### Gear Box (Personal Inventory)
- [ ] Navigate to Gear Box
- [ ] View saved gear items
- [ ] Add new personal gear
- [ ] Edit gear details
- [ ] Delete gear items

## 5. Itinerary
- [ ] Add new day to itinerary
- [ ] Edit day details
- [ ] Add activities to day
- [ ] Delete day
- [ ] Weather widget displays (if API key set)

## 6. Photos
- [ ] Photo gallery displays
- [ ] Upload photo button visible
- [ ] Photos display in grid

## 7. AI Assistant
- [ ] AI chat opens
- [ ] Can type and send message
- [ ] UI updates properly
- [ ] Close chat works

## 8. Settings
- [ ] Settings page loads
- [ ] Theme toggle works (if implemented)
- [ ] Data export works
- [ ] Data import works
- [ ] Clear data works

## 9. Responsive Design
- [ ] Test on mobile viewport (375px)
- [ ] Test on tablet viewport (768px)
- [ ] Test on desktop (1200px+)
- [ ] All features accessible on mobile

## 10. Data Persistence
- [ ] Create a trip and refresh - trip persists
- [ ] Add gear items and refresh - items persist
- [ ] Logout and login - data remains
- [ ] Check localStorage has data

## 11. Error Handling
- [ ] 404 page works for invalid routes
- [ ] Form validation messages appear
- [ ] API errors show user-friendly messages
- [ ] No console errors during normal use

## 12. Performance
- [ ] Initial page load < 3 seconds
- [ ] Route transitions are smooth
- [ ] No UI freezing during operations
- [ ] Images load properly

## Known Issues to Check
- [ ] Redux persist not implemented (data only in localStorage)
- [ ] Real API not connected (using mocks)
- [ ] Photo upload not functional (UI only)
- [ ] Weather requires API key configuration
- [ ] Some features are UI-only

## Test Results
- **Date Tested**: ___________
- **Tester**: ___________
- **Browser**: ___________
- **Pass Rate**: _____ / _____ checks passed

## Notes
_Add any bugs found or observations here:_

---

## Quick Smoke Test (5 min)
If you only have 5 minutes, test these critical paths:
1. [ ] Login with demo account
2. [ ] Create a new trip
3. [ ] Add 3 items to packing list
4. [ ] Navigate between pages
5. [ ] Logout

If these work, the core functionality is operational.