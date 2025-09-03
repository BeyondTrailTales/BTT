/**
 * BeyondTrailTales Gamification System
 * XP, Levels, Badges, Streaks, and Achievements
 * Forest-themed progression system inspired by Duolingo
 */

(function() {
    'use strict';

    // Gamification Configuration
    const GAMIFICATION_CONFIG = {
        xp: {
            // XP rewards for different actions
            rewards: {
                createBackpack: 50,
                createTrip: 100,
                completeTrip: 250,
                uploadPhoto: 25,
                addGearItem: 10,
                planItinerary: 75,
                shareTrip: 50,
                dailyLogin: 20,
                weekStreak: 200,
                monthStreak: 1000
            },
            // Level progression (XP required for each level)
            levels: [
                { level: 1, xp: 0, title: "Trail Newbie 🌱", icon: "🥾" },
                { level: 2, xp: 100, title: "Day Hiker 🚶", icon: "🎒" },
                { level: 3, xp: 300, title: "Weekend Wanderer 🏕️", icon: "⛺" },
                { level: 4, xp: 600, title: "Trail Explorer 🗺️", icon: "🧭" },
                { level: 5, xp: 1000, title: "Backcountry Adventurer 🏔️", icon: "🏔️" },
                { level: 6, xp: 1500, title: "Peak Bagger ⛰️", icon: "🦅" },
                { level: 7, xp: 2500, title: "Wilderness Expert 🌲", icon: "🌲" },
                { level: 8, xp: 4000, title: "Trail Master 🏆", icon: "🏆" },
                { level: 9, xp: 6000, title: "Expedition Leader 🚁", icon: "🚁" },
                { level: 10, xp: 10000, title: "Mountain Legend 👑", icon: "👑" }
            ]
        },
        badges: [
            // Milestone badges
            { id: 'first_trip', name: 'First Steps', icon: '👣', description: 'Complete your first trip', condition: 'trips_completed >= 1' },
            { id: 'five_trips', name: 'Trail Regular', icon: '🥾', description: 'Complete 5 trips', condition: 'trips_completed >= 5' },
            { id: 'ten_trips', name: 'Adventure Seeker', icon: '🎯', description: 'Complete 10 trips', condition: 'trips_completed >= 10' },
            { id: 'twenty_trips', name: 'Trail Veteran', icon: '🎖️', description: 'Complete 20 trips', condition: 'trips_completed >= 20' },
            
            // Distance badges
            { id: 'marathon', name: 'Marathon Hiker', icon: '🏃', description: 'Hike 26+ miles in a single trip', condition: 'max_distance >= 26' },
            { id: 'century', name: 'Century Club', icon: '💯', description: 'Hike 100+ total miles', condition: 'total_distance >= 100' },
            
            // Elevation badges
            { id: 'climber', name: 'Mountain Climber', icon: '🧗', description: 'Gain 3000+ ft elevation in one trip', condition: 'max_elevation >= 3000' },
            { id: 'high_altitude', name: 'High Altitude', icon: '🏔️', description: 'Reach 10,000+ ft elevation', condition: 'max_altitude >= 10000' },
            
            // Streak badges
            { id: 'week_streak', name: 'Week Warrior', icon: '🔥', description: 'Maintain a 7-day streak', condition: 'streak >= 7' },
            { id: 'month_streak', name: 'Dedicated Explorer', icon: '💪', description: 'Maintain a 30-day streak', condition: 'streak >= 30' },
            { id: 'season_streak', name: 'Season Champion', icon: '🏆', description: 'Maintain a 90-day streak', condition: 'streak >= 90' },
            
            // Special badges
            { id: 'photographer', name: 'Trail Photographer', icon: '📸', description: 'Upload 25+ trip photos', condition: 'photos_uploaded >= 25' },
            { id: 'gear_master', name: 'Gear Master', icon: '🎒', description: 'Create 5+ backpack configurations', condition: 'backpacks_created >= 5' },
            { id: 'planner', name: 'Master Planner', icon: '📋', description: 'Plan 10+ detailed itineraries', condition: 'itineraries_planned >= 10' },
            { id: 'all_seasons', name: 'Four Seasons', icon: '🍂', description: 'Complete trips in all 4 seasons', condition: 'seasons_hiked == 4' },
            { id: 'night_owl', name: 'Night Owl', icon: '🦉', description: 'Complete an overnight trip', condition: 'overnight_trips >= 1' },
            { id: 'early_bird', name: 'Early Bird', icon: '🌅', description: 'Start a trip before sunrise', condition: 'sunrise_starts >= 1' }
        ],
        streaks: {
            minActivityForStreak: 1, // Minimum activities per day to maintain streak
            gracePeriodHours: 36, // Hours before streak is lost
            freezeAvailable: 2, // Number of streak freezes available per month
        }
    };

    // User Progress Manager
    class UserProgress {
        constructor() {
            this.apiBase = '/BTT/api/routes/gamification.php';
            this.data = {
                xp: 0,
                level: 1,
                streak: 0,
                badges: [],
                stats: {}
            };
            this.loadProgress();
            this.setupPolling();
        }

        async loadProgress() {
            try {
                const response = await fetch(`${this.apiBase}?action=status`);
                if (response.ok) {
                    const data = await response.json();
                    this.data = {
                        xp: data.xp || 0,
                        level: data.level || 1,
                        streak: data.streak_days || 0,
                        badges: data.badges || [],
                        stats: data.stats || {},
                        xp_progress: data.xp_progress || 0,
                        xp_to_next_level: data.xp_to_next_level || 100
                    };
                    this.updateUI();
                }
            } catch (error) {
                console.error('Failed to load gamification data:', error);
            }
        }

        setupPolling() {
            // Refresh on page visibility change
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.loadProgress();
                }
            });
            
            // Refresh every 60 seconds
            setInterval(() => this.loadProgress(), 60000);
        }

        async saveProgress() {
            // No longer save to localStorage
            this.updateUI();
        }

        addXP(amount, reason) {
            const previousLevel = this.getLevel();
            this.data.xp += amount;
            const newLevel = this.getLevel();
            
            // Show XP notification
            this.showXPNotification(amount, reason);
            
            // Check for level up
            if (newLevel.level > previousLevel.level) {
                this.onLevelUp(newLevel);
            }
            
            this.saveProgress();
            return newLevel;
        }

        getLevel() {
            const levels = GAMIFICATION_CONFIG.xp.levels;
            let currentLevel = levels[0];
            
            for (let i = levels.length - 1; i >= 0; i--) {
                if (this.data.xp >= levels[i].xp) {
                    currentLevel = levels[i];
                    break;
                }
            }
            
            // Calculate progress to next level
            const nextLevelIndex = Math.min(currentLevel.level, levels.length - 1);
            const nextLevel = levels[nextLevelIndex];
            const xpForCurrentLevel = currentLevel.xp;
            const xpForNextLevel = nextLevel.xp;
            const progressXP = this.data.xp - xpForCurrentLevel;
            const neededXP = xpForNextLevel - xpForCurrentLevel;
            const progressPercent = neededXP > 0 ? (progressXP / neededXP) * 100 : 100;
            
            return {
                ...currentLevel,
                progressXP,
                neededXP,
                progressPercent,
                nextLevel
            };
        }

        updateStreak() {
            const now = new Date();
            const lastActivity = this.data.lastActivity ? new Date(this.data.lastActivity) : null;
            
            if (lastActivity) {
                const hoursSinceLastActivity = (now - lastActivity) / (1000 * 60 * 60);
                
                if (hoursSinceLastActivity <= 24) {
                    // Continue streak
                    this.data.streak++;
                } else if (hoursSinceLastActivity <= GAMIFICATION_CONFIG.streaks.gracePeriodHours) {
                    // Within grace period, maintain streak
                    // Don't increment
                } else {
                    // Streak broken
                    this.data.streak = 1;
                    this.showStreakLostNotification();
                }
            } else {
                // First activity
                this.data.streak = 1;
            }
            
            this.data.lastActivity = now.toISOString();
            this.checkStreakBadges();
            this.saveProgress();
        }

        checkBadges() {
            const stats = this.data.stats;
            const earnedBadges = [];
            
            GAMIFICATION_CONFIG.badges.forEach(badge => {
                if (!this.data.badges.includes(badge.id)) {
                    // Evaluate condition
                    const condition = badge.condition;
                    if (this.evaluateCondition(condition, stats)) {
                        this.data.badges.push(badge.id);
                        earnedBadges.push(badge);
                    }
                }
            });
            
            // Show notifications for earned badges
            earnedBadges.forEach(badge => {
                this.showBadgeEarnedNotification(badge);
            });
            
            if (earnedBadges.length > 0) {
                this.saveProgress();
            }
            
            return earnedBadges;
        }

        evaluateCondition(condition, stats) {
            // Simple condition evaluator
            // In production, use a safer evaluation method
            try {
                const parts = condition.split(' ');
                const stat = stats[parts[0]] || this.data[parts[0]] || 0;
                const operator = parts[1];
                const value = parseInt(parts[2]);
                
                switch(operator) {
                    case '>=': return stat >= value;
                    case '>': return stat > value;
                    case '==': return stat == value;
                    case '<=': return stat <= value;
                    case '<': return stat < value;
                    default: return false;
                }
            } catch (e) {
                console.error('Failed to evaluate condition:', condition, e);
                return false;
            }
        }

        checkStreakBadges() {
            this.checkBadges(); // Reuse badge checking with streak data
        }

        updateStats(statUpdates) {
            Object.keys(statUpdates).forEach(key => {
                if (this.data.stats.hasOwnProperty(key)) {
                    this.data.stats[key] = statUpdates[key];
                }
            });
            this.checkBadges();
            this.saveProgress();
        }

        showXPNotification(amount, reason) {
            const notification = document.createElement('div');
            notification.className = 'xp-notification';
            notification.innerHTML = `
                <div class="xp-popup">
                    <span class="xp-amount">+${amount} XP</span>
                    <span class="xp-reason">${reason}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        showBadgeEarnedNotification(badge) {
            const notification = document.createElement('div');
            notification.className = 'badge-notification';
            notification.innerHTML = `
                <div class="badge-popup">
                    <div class="badge-earned-header">🎉 Badge Earned!</div>
                    <div class="badge-earned-content">
                        <span class="badge-earned-icon">${badge.icon}</span>
                        <span class="badge-earned-name">${badge.name}</span>
                        <span class="badge-earned-desc">${badge.description}</span>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }

        showStreakLostNotification() {
            BTTUtils.showToast('😢 Your streak was broken! Start a new one today!', 'warning');
        }

        onLevelUp(newLevel) {
            const notification = document.createElement('div');
            notification.className = 'levelup-notification';
            notification.innerHTML = `
                <div class="levelup-popup">
                    <div class="levelup-header">🎊 LEVEL UP!</div>
                    <div class="levelup-content">
                        <span class="levelup-icon">${newLevel.icon}</span>
                        <span class="levelup-level">Level ${newLevel.level}</span>
                        <span class="levelup-title">${newLevel.title}</span>
                    </div>
                    <div class="levelup-particles"></div>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Play level up sound if available
            this.playSound('levelup');
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }

        playSound(soundType) {
            // Placeholder for sound effects
            // In production, add actual sound files
            console.log(`Playing ${soundType} sound`);
        }

        updateUI() {
            // Update XP bar if it exists
            const xpBar = document.querySelector('.user-xp-bar');
            if (xpBar) {
                const level = this.getLevel();
                xpBar.innerHTML = this.renderXPBar(level);
            }
            
            // Update streak display if it exists
            const streakDisplay = document.querySelector('.user-streak');
            if (streakDisplay) {
                streakDisplay.innerHTML = this.renderStreak();
            }
            
            // Update badges display if it exists
            const badgesDisplay = document.querySelector('.user-badges');
            if (badgesDisplay) {
                badgesDisplay.innerHTML = this.renderBadges();
            }
        }

        renderXPBar(level) {
            return `
                <div class="xp-bar">
                    <div class="xp-header">
                        <span class="xp-level">
                            <span class="level-icon">${level.icon}</span>
                            Level ${level.level}: ${level.title}
                        </span>
                        <span class="xp-points">${this.data.xp} XP</span>
                    </div>
                    <div class="xp-track">
                        <div class="xp-fill" style="width: ${level.progressPercent}%">
                            <div class="xp-glow"></div>
                        </div>
                    </div>
                    <div class="xp-footer">
                        <span class="xp-progress">${level.progressXP} / ${level.neededXP} to Level ${level.level + 1}</span>
                    </div>
                </div>
            `;
        }

        renderStreak() {
            const streakClass = this.data.streak >= 7 ? 'streak-fire' : '';
            return `
                <div class="streak-display ${streakClass}">
                    <span class="streak-flame">🔥</span>
                    <span class="streak-count">${this.data.streak}</span>
                    <span class="streak-label">Day Streak</span>
                </div>
            `;
        }

        renderBadges() {
            const allBadges = GAMIFICATION_CONFIG.badges;
            const earnedBadgeIds = this.data.badges;
            
            let html = '<div class="badges-grid">';
            allBadges.forEach(badge => {
                const earned = earnedBadgeIds.includes(badge.id);
                const earnedClass = earned ? 'earned' : 'locked';
                html += `
                    <div class="achievement-badge ${earnedClass}" title="${badge.description}">
                        <span class="badge-icon">${earned ? badge.icon : '🔒'}</span>
                        <span class="badge-name">${badge.name}</span>
                    </div>
                `;
            });
            html += '</div>';
            
            return html;
        }
    }

    // Initialize gamification system
    window.BTTGamification = new UserProgress();

    // Hook into existing BTT events
    document.addEventListener('DOMContentLoaded', () => {
        // Add XP display to header if it doesn't exist
        const header = document.querySelector('.header-content');
        if (header && !document.querySelector('.user-xp-bar')) {
            const xpContainer = document.createElement('div');
            xpContainer.className = 'user-progress-container';
            xpContainer.innerHTML = `
                <div class="user-xp-bar"></div>
                <div class="user-streak"></div>
            `;
            header.appendChild(xpContainer);
            BTTGamification.updateUI();
        }
        
        // Update streak on page load
        BTTGamification.updateStreak();
    });

    // Export reward functions for use in other scripts
    window.rewardXP = {
        createBackpack: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.createBackpack, 'Created a backpack'),
        createTrip: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.createTrip, 'Planned a new trip'),
        completeTrip: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.completeTrip, 'Completed a trip'),
        uploadPhoto: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.uploadPhoto, 'Uploaded a photo'),
        addGearItem: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.addGearItem, 'Added gear item'),
        planItinerary: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.planItinerary, 'Planned itinerary'),
        shareTrip: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.shareTrip, 'Shared a trip'),
        dailyLogin: () => BTTGamification.addXP(GAMIFICATION_CONFIG.xp.rewards.dailyLogin, 'Daily login')
    };

})();
