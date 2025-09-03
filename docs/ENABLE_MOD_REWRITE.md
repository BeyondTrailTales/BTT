# How to Enable mod_rewrite in XAMPP

If the .htaccess routing is not working, you need to enable mod_rewrite in Apache:

## Steps to Enable mod_rewrite:

1. **Open Apache Configuration**
   - Navigate to: `C:\xampp2\apache\conf\httpd.conf`
   - Open in a text editor (as Administrator)

2. **Enable mod_rewrite Module**
   - Find this line (around line 154):
     ```
     #LoadModule rewrite_module modules/mod_rewrite.so
     ```
   - Remove the `#` to uncomment it:
     ```
     LoadModule rewrite_module modules/mod_rewrite.so
     ```

3. **Allow .htaccess Override**
   - Find the `<Directory>` section for your document root
   - Look for: `AllowOverride None`
   - Change it to: `AllowOverride All`
   
   Example:
   ```apache
   <Directory "C:/xampp2/htdocs">
       Options Indexes FollowSymLinks Includes ExecCGI
       AllowOverride All
       Require all granted
   </Directory>
   ```

4. **Restart Apache**
   - Open XAMPP Control Panel
   - Click "Stop" next to Apache
   - Click "Start" to restart

## Alternative: Use PHP Router

If you can't enable mod_rewrite or prefer not to modify Apache config, use the PHP router instead:

Access the app at:
- `http://localhost/beyondtrailtales/beyondtrailtales-app/dist/index.php`

The PHP router will handle all routes correctly without needing .htaccess or mod_rewrite.