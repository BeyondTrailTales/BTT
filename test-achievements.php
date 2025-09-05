<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/helpers/achievement_helper.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];

// Get unshown achievements
$unshownAchievements = get_unshown_achievements_safe($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Achievement System</title>
    <link rel="stylesheet" href="assets/css/btt-main.css">
    <style>
        body { padding: 2rem; background: #1e1e1e; color: white; }
        .test-container { max-width: 600px; margin: 0 auto; }
        .btn { 
            padding: 1rem 2rem; 
            margin: 0.5rem; 
            background: #10b981; 
            color: white; 
            border: none; 
            border-radius: 0.5rem; 
            cursor: pointer; 
            font-size: 1rem;
        }
        .btn:hover { background: #059669; }
        .achievement-list { margin-top: 2rem; }
        .achievement-item { 
            padding: 1rem; 
            margin: 0.5rem 0; 
            background: #2a2a2a; 
            border-radius: 0.5rem; 
        }
        .status { margin-top: 1rem; padding: 1rem; background: #2a2a2a; border-radius: 0.5rem; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Achievement System Test</h1>
        
        <h2>Unshown Achievements: <?php echo count($unshownAchievements); ?></h2>
        
        <div class="achievement-list">
            <?php foreach ($unshownAchievements as $achievement): ?>
                <div class="achievement-item">
                    <strong><?php echo htmlspecialchars($achievement['name']); ?></strong> - 
                    <?php echo htmlspecialchars($achievement['description']); ?>
                    (<?php echo $achievement['xp_reward']; ?> XP)
                </div>
            <?php endforeach; ?>
        </div>
        
        <h2>Test Actions</h2>
        <button class="btn" onclick="testFirstPack()">Create First Pack (should trigger achievement)</button>
        <button class="btn" onclick="showRandomAchievement()">Show Random Test Achievement</button>
        <button class="btn" onclick="checkUnshown()">Check Unshown Achievements</button>
        
        <div id="status" class="status"></div>
    </div>
    
    <script src="assets/js/confetti.js"></script>
    <script>
        // Set user ID for Achievement Manager
        window.BTT_USER_ID = <?php echo json_encode($user_id); ?>;
    </script>
    <script src="assets/js/achievement-manager.js"></script>
    <script>
        // Wait for Achievement Manager to initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Achievement Manager:', window.achievementManager);
        });
        
        function testFirstPack() {
            // Simulate creating a pack
            fetch('/BTT/ajax-handler.php?route=backpacks', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: 'Test Achievement Pack',
                    description: 'Testing achievement system',
                    capacity_l: 65,
                    type: 'custom'
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('status').innerHTML = 'Pack created: ' + JSON.stringify(data);
                if (data.achievements && data.achievements.length > 0) {
                    data.achievements.forEach(achievement => {
                        window.achievementManager.queueAchievement(achievement);
                    });
                }
            })
            .catch(error => {
                document.getElementById('status').innerHTML = 'Error: ' + error.message;
            });
        }
        
        function showRandomAchievement() {
            const testAchievement = {
                name: 'Test Achievement',
                description: 'This is a test achievement!',
                icon: '🏆',
                xp_reward: 100,
                rarity: 'epic'
            };
            window.achievementManager.queueAchievement(testAchievement);
        }
        
        function checkUnshown() {
            window.achievementManager.checkUnshownAchievements();
        }
    </script>
</body>
</html>