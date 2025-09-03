<?php
/**
 * BeyondTrailTales - Public Landing Page
 * 
 * Shows welcome page for unauthenticated users
 */

// Page metadata
$pageTitle = 'Welcome to BeyondTrailTales';
$pageId = 'landing';
$pageDescription = 'Your ultimate companion for planning outdoor adventures and managing gear';
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-tokens.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-base.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-components.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-animations.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/auth-nav.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #0a2818 0%, #1a3d2e 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .landing-nav {
            background: rgba(10, 40, 24, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(74, 222, 128, 0.2);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #4ade80;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav-link {
            color: #e0e0e0;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary {
            background: #4ade80;
            color: #0a2818;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: #22c55e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 222, 128, 0.3);
        }
        
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
        }
        
        .hero-content {
            max-width: 800px;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: #9ca3af;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .btn-hero {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-hero-primary {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: #0a2818;
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(74, 222, 128, 0.4);
        }
        
        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #4ade80;
            border: 2px solid #4ade80;
        }
        
        .btn-hero-secondary:hover {
            background: rgba(74, 222, 128, 0.1);
            transform: translateY(-2px);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 1rem;
            backdrop-filter: blur(10px);
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            color: #4ade80;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .feature-desc {
            color: #9ca3af;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .forest-decoration {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%230a2818" fill-opacity="0.3" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            pointer-events: none;
            z-index: 0;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- Navigation -->
        <nav class="landing-nav">
            <div class="nav-container">
                <a href="<?php echo BTT_BASE_URL; ?>" class="logo">
                    <span>🌲</span>
                    <span>BeyondTrailTales</span>
                </a>
                <div class="nav-links">
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/login.php" class="nav-link">Login</a>
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/register.php" class="btn-primary">Get Started</a>
                </div>
            </div>
        </nav>
        
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">Plan Your Perfect Adventure</h1>
                <p class="hero-subtitle">
                    Organize your trips, manage your gear, and track your outdoor adventures 
                    with BeyondTrailTales - your ultimate backpacking companion.
                </p>
                
                <div class="hero-actions">
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/register.php" class="btn-hero btn-hero-primary">
                        Start Your Journey
                    </a>
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/login.php" class="btn-hero btn-hero-secondary">
                        Sign In
                    </a>
                </div>
                
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">🗺️</div>
                        <h3 class="feature-title">Trip Planning</h3>
                        <p class="feature-desc">Plan every detail of your outdoor adventures</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🎒</div>
                        <h3 class="feature-title">Gear Management</h3>
                        <p class="feature-desc">Organize and track all your backpacking gear</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">📊</div>
                        <h3 class="feature-title">Weight Tracking</h3>
                        <p class="feature-desc">Optimize your pack weight for any trip</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🏆</div>
                        <h3 class="feature-title">Achievements</h3>
                        <p class="feature-desc">Track progress and earn rewards</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <div class="forest-decoration"></div>
</body>
</html>
