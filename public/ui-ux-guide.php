<?php
/**
 * BeyondTrailTales Forest UI/UX Design System
 * A gamified, nature-inspired design language inspired by Duolingo
 * 
 * @version 1.0.0
 * @author BeyondTrailTales Team
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeyondTrailTales - Forest UI Design System</title>
    <meta name="description" content="A comprehensive design system for BeyondTrailTales with forest-inspired gamification">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/BTT/assets/css/forest-tokens.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-base.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-components.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-animations.css">
    <style>
        /* Guide-specific styles */
        .guide-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--forest-canopy);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--forest-border);
        }
        
        .guide-sidebar {
            position: sticky;
            top: 80px;
            height: calc(100vh - 100px);
            overflow-y: auto;
            padding: var(--space-4);
        }
        
        .component-preview {
            background: var(--forest-ground);
            border: 1px solid var(--forest-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin: var(--space-4) 0;
        }
        
        .code-block {
            background: var(--forest-shadow);
            border-radius: var(--radius-md);
            padding: var(--space-4);
            overflow-x: auto;
            margin: var(--space-3) 0;
        }
        
        .color-swatch {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            display: inline-block;
            margin: var(--space-2);
            position: relative;
            transition: transform var(--transition-fast);
        }
        
        .color-swatch:hover {
            transform: scale(1.1);
        }
        
        .token-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--space-4);
            margin: var(--space-4) 0;
        }
    </style>
</head>
<body class="forest-theme">
    
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="visually-hidden focus:not-sr-only">Skip to main content</a>
    
    <!-- Navigation -->
    <nav class="guide-nav" role="navigation" aria-label="Main navigation">
        <div class="container">
            <div class="nav-content">
                <div class="nav-brand">
                    <span class="logo" aria-label="BeyondTrailTales">🌲</span>
                    <h1 class="nav-title">Forest UI System</h1>
                </div>
                <div class="nav-actions">
                    <button class="btn-icon" aria-label="Toggle theme" onclick="toggleTheme()">
                        <span class="icon-sun" aria-hidden="true">☀️</span>
                    </button>
                    <button class="btn-icon" aria-label="Toggle reduced motion" onclick="toggleMotion()">
                        <span class="icon-motion" aria-hidden="true">🎬</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="guide-layout">
            
            <!-- Sidebar -->
            <aside class="guide-sidebar" role="complementary" aria-label="Guide navigation">
                <nav aria-label="Section navigation">
                    <ul class="sidebar-nav">
                        <li><a href="#principles">🌿 Design Principles</a></li>
                        <li><a href="#tokens">🎨 Design Tokens</a></li>
                        <li><a href="#typography">📝 Typography</a></li>
                        <li><a href="#colors">🌈 Colors</a></li>
                        <li><a href="#spacing">📐 Spacing</a></li>
                        <li><a href="#components">🧩 Components</a>
                            <ul>
                                <li><a href="#buttons">Buttons</a></li>
                                <li><a href="#cards">Cards</a></li>
                                <li><a href="#forms">Forms</a></li>
                                <li><a href="#navigation">Navigation</a></li>
                                <li><a href="#progress">Progress</a></li>
                                <li><a href="#feedback">Feedback</a></li>
                                <li><a href="#modals">Modals</a></li>
                            </ul>
                        </li>
                        <li><a href="#gamification">🎮 Gamification</a></li>
                        <li><a href="#animations">✨ Animations</a></li>
                        <li><a href="#accessibility">♿ Accessibility</a></li>
                        <li><a href="#patterns">🔄 Patterns</a></li>
                    </ul>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <main id="main-content" class="guide-content" role="main">
                
                <!-- Hero Section -->
                <section class="hero-forest">
                    <div class="hero-background">
                        <div class="forest-parallax layer-1"></div>
                        <div class="forest-parallax layer-2"></div>
                        <div class="forest-parallax layer-3"></div>
                    </div>
                    <div class="hero-content">
                        <h1 class="hero-title">
                            <span class="text-gradient">BeyondTrailTales</span>
                            <span class="hero-subtitle">Forest UI Design System</span>
                        </h1>
                        <p class="hero-description">
                            A playful, encouraging design language inspired by nature and gamification.
                            Built for accessibility, delight, and performance.
                        </p>
                        <div class="hero-badges">
                            <span class="badge badge-moss">v1.0.0</span>
                            <span class="badge badge-sky">WCAG 2.1 AA</span>
                            <span class="badge badge-berry">Mobile First</span>
                        </div>
                    </div>
                </section>
                
                <!-- Design Principles -->
                <section id="principles" class="guide-section">
                    <h2 class="section-title">🌿 Design Principles</h2>
                    
                    <div class="principle-cards">
                        <div class="card-forest card-hover">
                            <div class="card-icon">🌱</div>
                            <h3>Growth Mindset</h3>
                            <p>Every step forward is progress. Celebrate small wins and encourage exploration.</p>
                        </div>
                        
                        <div class="card-forest card-hover">
                            <div class="card-icon">🦌</div>
                            <h3>Natural Flow</h3>
                            <p>Interactions should feel organic, like a walk through the forest - intuitive and calming.</p>
                        </div>
                        
                        <div class="card-forest card-hover">
                            <div class="card-icon">🏔️</div>
                            <h3>Clear Horizons</h3>
                            <p>Users should always know where they are and where they can go next.</p>
                        </div>
                        
                        <div class="card-forest card-hover">
                            <div class="card-icon">🔥</div>
                            <h3>Warm Encouragement</h3>
                            <p>Like a campfire on a cold night, provide comfort and motivation through positive reinforcement.</p>
                        </div>
                    </div>
                    
                    <div class="principle-philosophy">
                        <h3>Our Philosophy</h3>
                        <blockquote class="forest-quote">
                            "Like Duolingo teaches languages through play, we make backpacking preparation 
                            delightful through forest-inspired gamification. Every packed item earns XP, 
                            every completed trip unlocks badges, and streaks keep adventurers engaged."
                        </blockquote>
                    </div>
                </section>
                
                <!-- Design Tokens -->
                <section id="tokens" class="guide-section">
                    <h2 class="section-title">🎨 Design Tokens</h2>
                    
                    <p class="section-description">
                        Design tokens are the atomic building blocks of our design system. 
                        They ensure consistency across all components and themes.
                    </p>
                    
                    <div class="token-category">
                        <h3>Core Tokens</h3>
                        <div class="code-block">
                            <pre><code class="language-css">:root {
  /* Forest Color Palette */
  --forest-deep: #0a2818;      /* Deep forest green */
  --forest-moss: #2d5a3d;       /* Moss green */
  --forest-fern: #5cb85c;       /* Fern green */
  --forest-leaf: #8bc34a;       /* Fresh leaf */
  --forest-bark: #3e2723;       /* Tree bark brown */
  --forest-soil: #2c1810;       /* Rich soil */
  --forest-fog: #f5f5f5;        /* Morning fog */
  --forest-sky: #87ceeb;        /* Clear sky blue */
  --forest-berry: #d32f2f;      /* Wild berry red */
  --forest-honey: #ffc107;      /* Golden honey */
  
  /* Semantic Colors */
  --color-primary: var(--forest-fern);
  --color-secondary: var(--forest-moss);
  --color-accent: var(--forest-honey);
  --color-danger: var(--forest-berry);
  --color-success: var(--forest-leaf);
  --color-info: var(--forest-sky);
  
  /* Typography Scale - Fluid */
  --text-xs: clamp(0.75rem, 2vw, 0.875rem);
  --text-sm: clamp(0.875rem, 2.5vw, 1rem);
  --text-base: clamp(1rem, 3vw, 1.125rem);
  --text-lg: clamp(1.125rem, 3.5vw, 1.25rem);
  --text-xl: clamp(1.25rem, 4vw, 1.5rem);
  --text-2xl: clamp(1.5rem, 5vw, 2rem);
  --text-3xl: clamp(2rem, 6vw, 3rem);
  --text-4xl: clamp(2.5rem, 8vw, 4rem);
  
  /* Spacing Scale (4pt system) */
  --space-1: 0.25rem;  /* 4px */
  --space-2: 0.5rem;   /* 8px */
  --space-3: 0.75rem;  /* 12px */
  --space-4: 1rem;     /* 16px */
  --space-5: 1.25rem;  /* 20px */
  --space-6: 1.5rem;   /* 24px */
  --space-8: 2rem;     /* 32px */
  --space-10: 2.5rem;  /* 40px */
  --space-12: 3rem;    /* 48px */
  --space-16: 4rem;    /* 64px */
  
  /* Border Radius */
  --radius-xs: 0.125rem;
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-xl: 1rem;
  --radius-2xl: 1.5rem;
  --radius-pill: 9999px;
  
  /* Elevation (Glassmorphism) */
  --elevation-low: 0 2px 8px rgba(10, 40, 24, 0.1);
  --elevation-medium: 0 4px 16px rgba(10, 40, 24, 0.15);
  --elevation-high: 0 8px 32px rgba(10, 40, 24, 0.2);
  --elevation-glow: 0 0 24px rgba(92, 184, 92, 0.3);
  
  /* Animation */
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-spring: 600ms cubic-bezier(0.68, -0.55, 0.265, 1.55);
}</code></pre>
                        </div>
                    </div>
                </section>
                
                <!-- Colors -->
                <section id="colors" class="guide-section">
                    <h2 class="section-title">🌈 Color Palette</h2>
                    
                    <div class="color-category">
                        <h3>Forest Palette</h3>
                        <div class="color-grid">
                            <div class="color-item">
                                <div class="color-swatch" style="background: var(--forest-deep)"></div>
                                <p>Deep Forest<br><small>#0a2818</small></p>
                            </div>
                            <div class="color-item">
                                <div class="color-swatch" style="background: var(--forest-moss)"></div>
                                <p>Moss<br><small>#2d5a3d</small></p>
                            </div>
                            <div class="color-item">
                                <div class="color-swatch" style="background: var(--forest-fern)"></div>
                                <p>Fern<br><small>#5cb85c</small></p>
                            </div>
                            <div class="color-item">
                                <div class="color-swatch" style="background: var(--forest-leaf)"></div>
                                <p>Leaf<br><small>#8bc34a</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contrast-table">
                        <h3>WCAG Contrast Ratios</h3>
                        <table class="table-forest">
                            <thead>
                                <tr>
                                    <th>Foreground</th>
                                    <th>Background</th>
                                    <th>Ratio</th>
                                    <th>WCAG Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Forest Fog</td>
                                    <td>Forest Deep</td>
                                    <td>15.3:1</td>
                                    <td><span class="badge badge-success">AAA</span></td>
                                </tr>
                                <tr>
                                    <td>Forest Fern</td>
                                    <td>Forest Deep</td>
                                    <td>7.2:1</td>
                                    <td><span class="badge badge-success">AA</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <!-- Components -->
                <section id="components" class="guide-section">
                    <h2 class="section-title">🧩 Components</h2>
                    
                    <!-- Buttons -->
                    <div id="buttons" class="component-section">
                        <h3>Buttons</h3>
                        
                        <div class="component-preview">
                            <div class="button-grid">
                                <button class="btn btn-primary">
                                    <span class="btn-icon">🌲</span>
                                    Primary Forest
                                </button>
                                <button class="btn btn-secondary">
                                    <span class="btn-icon">🪵</span>
                                    Secondary Bark
                                </button>
                                <button class="btn btn-accent">
                                    <span class="btn-icon">🍯</span>
                                    Accent Honey
                                </button>
                                <button class="btn btn-danger">
                                    <span class="btn-icon">🍄</span>
                                    Danger Berry
                                </button>
                                <button class="btn btn-glass">
                                    <span class="btn-icon">💎</span>
                                    Glass Effect
                                </button>
                            </div>
                        </div>
                        
                        <div class="code-block">
                            <pre><code class="language-html">&lt;button class="btn btn-primary"&gt;
  &lt;span class="btn-icon"&gt;🌲&lt;/span&gt;
  Primary Forest
&lt;/button&gt;</code></pre>
                        </div>
                    </div>
                    
                    <!-- Cards -->
                    <div id="cards" class="component-section">
                        <h3>Cards</h3>
                        
                        <div class="component-preview">
                            <div class="card-grid">
                                <div class="card-forest card-hover">
                                    <div class="card-header">
                                        <h4>Forest Card</h4>
                                        <span class="badge badge-moss">Active</span>
                                    </div>
                                    <div class="card-body">
                                        <p>A glassmorphic card with forest theming and subtle animations.</p>
                                    </div>
                                    <div class="card-footer">
                                        <button class="btn btn-sm btn-primary">Explore</button>
                                    </div>
                                </div>
                                
                                <div class="card-forest card-glow">
                                    <div class="card-body">
                                        <div class="stat-display">
                                            <span class="stat-icon">🎒</span>
                                            <span class="stat-value">24</span>
                                            <span class="stat-label">Items Packed</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Components -->
                    <div id="progress" class="component-section">
                        <h3>Progress & Gamification</h3>
                        
                        <div class="component-preview">
                            <!-- XP Bar -->
                            <div class="xp-bar">
                                <div class="xp-header">
                                    <span class="xp-level">Level 12</span>
                                    <span class="xp-points">2,450 / 3,000 XP</span>
                                </div>
                                <div class="xp-track">
                                    <div class="xp-fill" style="width: 82%">
                                        <span class="xp-glow"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Streak Counter -->
                            <div class="streak-display">
                                <div class="streak-flame">🔥</div>
                                <div class="streak-count">7</div>
                                <div class="streak-label">Day Streak</div>
                            </div>
                            
                            <!-- Step Progress -->
                            <div class="step-progress">
                                <div class="step completed">
                                    <span class="step-icon">✓</span>
                                    <span class="step-label">Choose Pack</span>
                                </div>
                                <div class="step active">
                                    <span class="step-icon">2</span>
                                    <span class="step-label">Add Gear</span>
                                </div>
                                <div class="step">
                                    <span class="step-icon">3</span>
                                    <span class="step-label">Review</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Gamification -->
                <section id="gamification" class="guide-section">
                    <h2 class="section-title">🎮 Gamification System</h2>
                    
                    <div class="gamification-overview">
                        <div class="card-forest">
                            <h3>Experience Points (XP)</h3>
                            <ul class="xp-actions">
                                <li>Create backpack: <span class="xp-value">+50 XP</span></li>
                                <li>Add item: <span class="xp-value">+10 XP</span></li>
                                <li>Complete trip: <span class="xp-value">+200 XP</span></li>
                                <li>Daily login: <span class="xp-value">+25 XP</span></li>
                            </ul>
                        </div>
                        
                        <div class="card-forest">
                            <h3>Achievements</h3>
                            <div class="badge-grid">
                                <div class="achievement-badge earned">
                                    <span class="badge-icon">🏃</span>
                                    <span class="badge-name">First Steps</span>
                                </div>
                                <div class="achievement-badge earned">
                                    <span class="badge-icon">🎒</span>
                                    <span class="badge-name">Pack Master</span>
                                </div>
                                <div class="achievement-badge">
                                    <span class="badge-icon">🏔️</span>
                                    <span class="badge-name">Summit Seeker</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Animations -->
                <section id="animations" class="guide-section">
                    <h2 class="section-title">✨ Animations</h2>
                    
                    <div class="animation-demos">
                        <div class="animation-item">
                            <button class="btn btn-primary" onclick="this.classList.add('leaf-bounce')">
                                Leaf Bounce
                            </button>
                        </div>
                        
                        <div class="animation-item">
                            <div class="firefly-container">
                                <span class="firefly"></span>
                                <span class="firefly"></span>
                                <span class="firefly"></span>
                            </div>
                        </div>
                        
                        <div class="animation-item">
                            <button class="btn btn-success success-pulse">
                                Success Pulse
                            </button>
                        </div>
                    </div>
                    
                    <div class="code-block">
                        <pre><code class="language-css">/* Respect prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}</code></pre>
                    </div>
                </section>
                
                <!-- Accessibility -->
                <section id="accessibility" class="guide-section">
                    <h2 class="section-title">♿ Accessibility</h2>
                    
                    <div class="a11y-checklist">
                        <div class="card-forest">
                            <h3>WCAG 2.1 AA Compliance</h3>
                            <ul class="checklist">
                                <li class="checked">✅ Color contrast ratios ≥ 4.5:1 for normal text</li>
                                <li class="checked">✅ Color contrast ratios ≥ 3:1 for large text</li>
                                <li class="checked">✅ All interactive elements keyboard accessible</li>
                                <li class="checked">✅ Focus indicators visible and clear</li>
                                <li class="checked">✅ ARIA labels for icon buttons</li>
                                <li class="checked">✅ Skip links for navigation</li>
                                <li class="checked">✅ Semantic HTML structure</li>
                                <li class="checked">✅ Form labels and error messages</li>
                                <li class="checked">✅ Alternative text for images</li>
                                <li class="checked">✅ Reduced motion support</li>
                            </ul>
                        </div>
                        
                        <div class="card-forest">
                            <h3>Screen Reader Support</h3>
                            <div class="code-block">
                                <pre><code class="language-html">&lt;!-- Visually hidden but screen reader accessible --&gt;
&lt;span class="visually-hidden"&gt;Loading...&lt;/span&gt;

&lt;!-- Skip to main content link --&gt;
&lt;a href="#main" class="skip-link"&gt;Skip to main content&lt;/a&gt;

&lt;!-- Proper ARIA labels --&gt;
&lt;button aria-label="Close dialog" class="btn-icon"&gt;
  &lt;span aria-hidden="true"&gt;✕&lt;/span&gt;
&lt;/button&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Patterns -->
                <section id="patterns" class="guide-section">
                    <h2 class="section-title">🔄 Design Patterns</h2>
                    
                    <div class="pattern-examples">
                        <div class="pattern-card">
                            <h3>Empty States</h3>
                            <div class="empty-state">
                                <div class="empty-icon">🎒</div>
                                <h4 class="empty-title">No backpacks yet</h4>
                                <p class="empty-description">Start your adventure by creating your first backpack!</p>
                                <button class="btn btn-primary">Create Backpack</button>
                            </div>
                        </div>
                        
                        <div class="pattern-card">
                            <h3>Loading States</h3>
                            <div class="skeleton-loader">
                                <div class="skeleton skeleton-title"></div>
                                <div class="skeleton skeleton-text"></div>
                                <div class="skeleton skeleton-text"></div>
                            </div>
                        </div>
                        
                        <div class="pattern-card">
                            <h3>Toast Notifications</h3>
                            <div class="toast toast-success">
                                <span class="toast-icon">✓</span>
                                <span class="toast-message">Backpack saved successfully!</span>
                            </div>
                        </div>
                    </div>
                </section>
                
            </main>
        </div>
    </div>
    
    <!-- JavaScript for interactions -->
    <script>
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'forest-dark' ? 'forest-light' : 'forest-dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
        
        // Motion toggle
        function toggleMotion() {
            const body = document.body;
            body.classList.toggle('reduce-motion');
            localStorage.setItem('reduceMotion', body.classList.contains('reduce-motion'));
        }
        
        // Load preferences
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'forest-dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const reduceMotion = localStorage.getItem('reduceMotion') === 'true';
            if (reduceMotion) {
                document.body.classList.add('reduce-motion');
            }
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
