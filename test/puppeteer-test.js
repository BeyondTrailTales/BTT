const { exec } = require('child_process');
const http = require('http');

// Kill existing Chrome processes
exec('taskkill /F /IM chrome.exe', (error) => {
    console.log('Killed existing Chrome processes');
    
    // Wait a moment then start Chrome with debugging
    setTimeout(() => {
        const chromePath = '"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"';
        const args = '--remote-debugging-port=9222 --disable-gpu --no-sandbox --disable-dev-shm-usage';
        const url = 'http://localhost/BTT/public/trips.php';
        
        exec(`${chromePath} ${args} ${url}`, (error, stdout, stderr) => {
            if (error) {
                console.error(`Error: ${error}`);
                return;
            }
            console.log('Chrome started with debugging on port 9222');
        });
        
        // Wait for Chrome to fully start
        setTimeout(() => {
            // Check if debugging port is open
            const options = {
                hostname: 'localhost',
                port: 9222,
                path: '/json',
                method: 'GET'
            };
            
            const req = http.request(options, (res) => {
                console.log(`Debugging port status: ${res.statusCode}`);
                let data = '';
                
                res.on('data', (chunk) => {
                    data += chunk;
                });
                
                res.on('end', () => {
                    console.log('Chrome debugging is ready!');
                    console.log('Available tabs:', JSON.parse(data).length);
                });
            });
            
            req.on('error', (e) => {
                console.error(`Problem with request: ${e.message}`);
            });
            
            req.end();
        }, 5000);
    }, 2000);
});
