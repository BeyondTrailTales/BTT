<?php
/**
 * Duolingo Forest Theme Navigation
 * Clean, modern, gamified navigation bar
 */

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get user data for gamification elements
$user_xp = $_SESSION['user_xp'] ?? 106;
$user_streak = $_SESSION['user_streak'] ?? 1;
$user_level = floor($user_xp / 500) + 1;
$user_name = $_SESSION['user_name'] ?? 'Explorer';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>

<!-- Duolingo Forest Navigation -->
<nav class="navbar duo-forest-nav" role="navigation" aria-label="Main navigation">
    <div class="nav-container">
        <!-- Logo/Brand -->
        <a href="/BTT/" class="nav-logo" aria-label="BeyondTrailTales Home">
            <span class="logo-icon">🏔️</span>
            <span class="logo-text">BeyondTrailTales</span>
        </a>
        
        <!-- Main Navigation Menu -->
        <ul class="nav-menu" role="menubar">
            <li class="nav-item" role="none">
                <a href="/BTT/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" role="menuitem">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="/BTT/trips.php" class="nav-link <?php echo $current_page === 'trips' ? 'active' : ''; ?>" role="menuitem">
                    <span class="nav-icon">🗺️</span>
                    <span>Trips</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="/BTT/backpacks.php" class="nav-link <?php echo $current_page === 'backpacks' ? 'active' : ''; ?>" role="menuitem">
                    <span class="nav-icon">🎒</span>
                    <span>Backpacks</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="/BTT/gear.php" class="nav-link <?php echo $current_page === 'gear' ? 'active' : ''; ?>" role="menuitem">
                    <span class="nav-icon">⛺</span>
                    <span>Gear</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="/BTT/achievements.php" class="nav-link <?php echo $current_page === 'achievements' ? 'active' : ''; ?>" role="menuitem">
                    <span class="nav-icon">🏆</span>
                    <span>Achievements</span>
                </a>
            </li>
        </ul>
        
        <!-- User Menu (Right Side) -->
        <div class="nav-user">
            <!-- Streak Counter -->
            <div class="nav-streak" title="Current streak">
                <span class="streak-fire">🔥</span>
                <span class="streak-number"><?php echo $user_streak; ?></span>
            </div>
            
            <!-- XP Display -->
            <div class="nav-xp" title="Experience points">
                <span class="xp-star">⭐</span>
                <span class="xp-number"><?php echo number_format($user_xp); ?> XP</span>
            </div>
            
            <!-- User Profile -->
            <div class="nav-profile dropdown">
                <button class="profile-button" aria-label="User menu" aria-expanded="false">
                    <span class="nav-avatar"><?php echo $user_initial; ?></span>
                    <span class="profile-name"><?php echo e($user_name); ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-menu" role="menu">
                    <div class="dropdown-header">
                        <span class="dropdown-level">Level <?php echo $user_level; ?></span>
                        <div class="level-progress">
                            <div class="level-bar" style="width: <?php echo ($user_xp % 500) / 5; ?>%"></div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="/BTT/profile.php" class="dropdown-item" role="menuitem">
                        <span>👤</span> Profile
                    </a>
                    <a href="/BTT/achievements.php" class="dropdown-item" role="menuitem">
                        <span>🏆</span> Achievements
                    </a>
                    <a href="/BTT/settings.php" class="dropdown-item" role="menuitem">
                        <span>⚙️</span> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/BTT/logout.php" class="dropdown-item dropdown-item--danger" role="menuitem">
                        <span>🚪</span> Sign Out
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
    </div>
</nav>

<!-- Navigation Styles -->
<style>
/* Duolingo Forest Navigation Styles */
.duo-forest-nav {
    background: rgba(15, 56, 35, 0.98) !important;
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(74, 222, 128, 0.15);
    position: sticky;
    top: 0;
    z-index: 1030;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    overflow: visible;
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    overflow: visible;
}

/* Logo */
.nav-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: #58cc02;
    font-size: 1.25rem;
    font-weight: 800;
    transition: transform 0.2s ease;
}

.nav-logo:hover {
    transform: scale(1.05);
}

.logo-icon {
    font-size: 1.5rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.logo-text {
    background: linear-gradient(135deg, #58cc02, #68d612);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation Menu */
.nav-menu {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1.5px solid transparent;
    border-radius: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    position: relative;
}

.nav-link:hover {
    background: rgba(88, 204, 2, 0.1);
    border-color: rgba(88, 204, 2, 0.3);
    color: white;
    transform: translateY(-2px);
}

.nav-link.active {
    background: #58cc02;
    color: white;
    box-shadow: 0 4px 12px rgba(88, 204, 2, 0.3);
    border-color: #58cc02;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #58cc02;
}

.nav-icon {
    font-size: 1.125rem;
}

/* User Menu */
.nav-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Streak */
.nav-streak {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: rgba(255, 200, 0, 0.15);
    border: 1.5px solid #ffc800;
    border-radius: 999px;
    font-weight: 700;
    color: #ffc800;
    font-size: 0.875rem;
    animation: pulse-gold 2s infinite;
}

@keyframes pulse-gold {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 200, 0, 0); }
    50% { box-shadow: 0 0 0 6px rgba(255, 200, 0, 0.1); }
}

.streak-fire {
    animation: flame 1s ease-in-out infinite;
}

@keyframes flame {
    0%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.1) rotate(-5deg); }
    75% { transform: scale(1.1) rotate(5deg); }
}

/* XP Display */
.nav-xp {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: rgba(28, 176, 246, 0.15);
    border: 1.5px solid #1cb0f6;
    border-radius: 999px;
    font-weight: 700;
    color: #1cb0f6;
    font-size: 0.875rem;
}

.xp-star {
    animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.2) rotate(180deg); }
}

/* Profile Button */
.nav-profile {
    position: relative;
}

.profile-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem 0.25rem 0.25rem;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid transparent;
    border-radius: 999px;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600;
    font-size: 0.875rem;
}

.profile-button:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: #58cc02;
}

.nav-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #58cc02, #68d612);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
    font-size: 1rem;
}

.profile-name {
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dropdown-arrow {
    font-size: 0.625rem;
    opacity: 0.7;
    transition: transform 0.2s ease;
}

.profile-button[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

/* Dropdown Menu */
.dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    min-width: 220px;
    max-width: calc(100vw - 2rem);
    background: rgba(15, 56, 35, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(74, 222, 128, 0.2);
    border-radius: 0.75rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 9999;
}

.nav-profile:hover .dropdown-menu,
.nav-profile:focus-within .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.dropdown-level {
    display: block;
    font-weight: 700;
    color: #58cc02;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.level-progress {
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
    overflow: hidden;
}

.level-bar {
    height: 100%;
    background: linear-gradient(90deg, #58cc02, #68d612);
    border-radius: 999px;
    transition: width 0.3s ease;
}

.dropdown-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: rgba(88, 204, 2, 0.1);
    color: white;
}

.dropdown-item--danger:hover {
    background: rgba(255, 75, 75, 0.1);
    color: #ff4b4b;
}

/* Mobile Toggle */
.nav-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.nav-toggle-bar {
    width: 24px;
    height: 2px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-menu {
        position: fixed;
        left: -100%;
        top: 64px;
        width: 100%;
        background: rgba(15, 56, 35, 0.98);
        flex-direction: column;
        padding: 1rem;
        transition: left 0.3s ease;
        border-bottom: 1px solid rgba(74, 222, 128, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .nav-menu.active {
        left: 0;
    }
    
    .nav-link {
        width: 100%;
        justify-content: center;
    }
    
    .nav-user {
        gap: 0.5rem;
    }
    
    .profile-name {
        display: none;
    }
    
    .nav-toggle {
        display: flex;
    }
    
    .nav-toggle[aria-expanded="true"] .nav-toggle-bar:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    
    .nav-toggle[aria-expanded="true"] .nav-toggle-bar:nth-child(2) {
        opacity: 0;
    }
    
    .nav-toggle[aria-expanded="true"] .nav-toggle-bar:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }
}
</style>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.nav-toggle');
    const menu = document.querySelector('.nav-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !isExpanded);
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-container')) {
            menu?.classList.remove('active');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
