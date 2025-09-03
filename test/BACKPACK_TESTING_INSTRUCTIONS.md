# Backpack Testing Instructions

## Overview
The backpack functionality is now properly user-scoped. Each user can only see and manage their own backpacks.

## To Test Backpacks on Frontend:

1. **Start your local server** (Apache with PHP)

2. **Navigate to the login page:**
   ```
   http://localhost/BTT/public/auth/login.php
   ```

3. **Login with test account:**
   - Username: `admin`
   - Password: `Admin123!`
   
   Alternative test accounts:
   - `testuser` / `Test123!`
   - `demo` / `Demo123!`

4. **Once logged in, navigate to backpacks:**
   ```
   http://localhost/BTT/public/backpacks.php
   ```

5. **You should see:**
   - The admin user has 5 backpacks: DJ, AJ, Greg, Jack, DJ (duplicate name)
   - Each backpack shows its name, capacity, and item count
   - You can click on any backpack to view/edit it

## Backend API Testing:

The backpack API endpoints are now user-scoped:
- `GET /api/index.php?route=backpacks` - Returns only the logged-in user's backpacks
- `GET /api/index.php?route=backpacks&id={id}` - Returns backpack only if owned by user
- `POST /api/index.php?route=backpacks` - Creates backpack for logged-in user
- `PUT /api/index.php?route=backpacks&id={id}` - Updates only if user owns the backpack
- `DELETE /api/index.php?route=backpacks&id={id}` - Deletes only if user owns the backpack

## Important Notes:

1. **Authentication Required**: You MUST be logged in to access backpacks
2. **User Isolation**: Each user only sees their own backpacks
3. **Session Management**: The system uses PHP sessions for authentication
4. **Test Data**: The admin user already has 5 test backpacks created

## Troubleshooting:

If you don't see backpacks:
1. Make sure you're logged in (check for username in header)
2. Clear browser cache and cookies
3. Try logging out and back in
4. Check browser console for JavaScript errors
5. Ensure Apache and PHP are running

## Database Check:

To verify backpacks in database:
```bash
php test/test-backpack-api.php
```

This will show all backpacks for the admin user directly from the database.
