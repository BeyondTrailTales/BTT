/**
 * Gamified Dashboard JavaScript
 * Interactive components and animations for Duolingo-inspired dashboard
 * ADA compliant with reduced motion support
 */

(function() {
  'use strict';

  // Check for reduced motion preference
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /**
   * Initialize progress rings with animated fill
   */
  function initProgressRings() {
    const rings = document.querySelectorAll('.ring-progress');
    
    if (!rings.length) return;

    // Use IntersectionObserver for performance
    const observerOptions = {
      threshold: 0.5,
      rootMargin: '0px'
    };

    const ringObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const circle = entry.target;
          const progress = parseFloat(circle.dataset.progress) || 0;
          animateRing(circle, progress);
          ringObserver.unobserve(circle); // Only animate once
        }
      });
    }, observerOptions);

    rings.forEach(ring => {
      // Set initial state
      const radius = ring.getAttribute('r');
      const circumference = 2 * Math.PI * radius;
      ring.style.strokeDasharray = `${circumference} ${circumference}`;
      ring.style.strokeDashoffset = circumference;
      
      // Start observing
      ringObserver.observe(ring);
    });
  }

  /**
   * Animate a progress ring to its target value
   */
  function animateRing(circle, progress) {
    const radius = circle.getAttribute('r');
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (progress / 100) * circumference;
    
    if (prefersReducedMotion) {
      // Instant fill for reduced motion
      circle.style.strokeDashoffset = offset;
    } else {
      // Animate the fill
      requestAnimationFrame(() => {
        circle.style.transition = 'stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        circle.style.strokeDashoffset = offset;
      });
    }

    // Update ARIA value
    const container = circle.closest('.progress-ring');
    if (container) {
      container.setAttribute('aria-valuenow', Math.round(progress));
    }
  }

  /**
   * Initialize streak counter with celebration animation
   */
  function initStreakCounter() {
    const streakElements = document.querySelectorAll('.streak');
    
    streakElements.forEach(streak => {
      const count = streak.querySelector('.streak-count');
      if (!count) return;

      const targetValue = parseInt(count.textContent) || 0;
      
      if (!prefersReducedMotion && targetValue > 0) {
        // Animate counter from 0 to target
        animateCounter(count, 0, targetValue, 1000);
        
        // Add celebration effect
        streak.classList.add('streak-celebrate');
        setTimeout(() => {
          streak.classList.remove('streak-celebrate');
        }, 1500);
      }
    });
  }

  /**
   * Animate a counter from start to end value
   */
  function animateCounter(element, start, end, duration) {
    const range = end - start;
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Ease-out cubic
      const easeProgress = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(start + range * easeProgress);
      
      element.textContent = current;
      
      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      }
    }
    
    requestAnimationFrame(updateCounter);
  }

  /**
   * Initialize skill tree navigation
   */
  function initSkillTree() {
    const skillNodes = document.querySelectorAll('.skill-node');
    
    skillNodes.forEach(node => {
      // Keyboard navigation
      node.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          handleSkillNodeClick(node);
        }
      });

      // Mouse interaction
      node.addEventListener('click', () => {
        handleSkillNodeClick(node);
      });

      // Add tooltips for locked nodes
      if (node.classList.contains('is-locked')) {
        node.setAttribute('title', 'Complete previous skills to unlock');
        node.setAttribute('aria-label', node.querySelector('.skill-title').textContent + ' - Locked');
      }
    });
  }

  /**
   * Handle skill node interaction
   */
  function handleSkillNodeClick(node) {
    if (node.classList.contains('is-locked')) {
      // Shake animation for locked nodes
      if (!prefersReducedMotion) {
        node.classList.add('shake');
        setTimeout(() => node.classList.remove('shake'), 500);
      }
      
      // Announce to screen readers
      announceToScreenReader('This skill is locked. Complete previous skills to unlock.');
      return;
    }

    // Navigate to skill details or related page
    const skillTitle = node.querySelector('.skill-title').textContent;
    console.log('Navigate to:', skillTitle);
    
    // Add ripple effect
    if (!prefersReducedMotion) {
      addRippleEffect(node);
    }
  }

  /**
   * Add ripple effect to element
   */
  function addRippleEffect(element) {
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    element.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
  }

  /**
   * Initialize intersection animations
   */
  function initIntersectionAnimations() {
    if (prefersReducedMotion) return;

    const animatedElements = document.querySelectorAll(
      '.card, .achievement-badge, .quick-action-tile, .activity-item'
    );

    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const animationObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          // Stagger animations
          setTimeout(() => {
            entry.target.classList.add('fade-in-up');
          }, index * 50);
          
          animationObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    animatedElements.forEach(element => {
      element.style.opacity = '0';
      element.style.transform = 'translateY(20px)';
      animationObserver.observe(element);
    });
  }

  /**
   * Initialize achievement badges
   */
  function initAchievementBadges() {
    const badges = document.querySelectorAll('.achievement-badge');
    
    badges.forEach(badge => {
      badge.addEventListener('click', () => {
        if (badge.classList.contains('locked')) {
          showAchievementHint(badge);
        } else if (badge.classList.contains('earned')) {
          showAchievementDetails(badge);
        }
      });

      // Add keyboard support
      badge.setAttribute('tabindex', '0');
      badge.setAttribute('role', 'button');
      
      badge.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          badge.click();
        }
      });
    });
  }

  /**
   * Show achievement hint for locked badges
   */
  function showAchievementHint(badge) {
    const hint = 'Complete more trips to unlock this achievement!';
    showTooltip(badge, hint);
  }

  /**
   * Show achievement details for earned badges
   */
  function showAchievementDetails(badge) {
    const name = badge.querySelector('.badge-name').textContent;
    const message = `Congratulations! You've earned the ${name} achievement!`;
    showTooltip(badge, message);
  }

  /**
   * Show tooltip near element
   */
  function showTooltip(element, message) {
    // Remove existing tooltips
    document.querySelectorAll('.tooltip-popup').forEach(t => t.remove());

    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-popup';
    tooltip.textContent = message;
    tooltip.setAttribute('role', 'tooltip');
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    tooltip.style.position = 'absolute';
    tooltip.style.left = `${rect.left + rect.width / 2}px`;
    tooltip.style.top = `${rect.top - 10}px`;
    tooltip.style.transform = 'translate(-50%, -100%)';
    
    // Auto-remove after delay
    setTimeout(() => tooltip.remove(), 3000);
    
    // Remove on click outside
    document.addEventListener('click', function removeTooltip(e) {
      if (!tooltip.contains(e.target)) {
        tooltip.remove();
        document.removeEventListener('click', removeTooltip);
      }
    });
  }

  /**
   * Initialize XP animations
   */
  function initXPAnimations() {
    const xpChip = document.querySelector('.xp-chip');
    
    if (!xpChip || prefersReducedMotion) return;

    xpChip.addEventListener('click', () => {
      // Simulate XP gain animation
      const xpValue = xpChip.querySelector('.xp-value');
      const currentXP = parseInt(xpValue.textContent.replace(/[^0-9]/g, '')) || 0;
      const gainedXP = 50;
      
      // Add floating XP indicator
      const floater = document.createElement('span');
      floater.className = 'xp-floater';
      floater.textContent = `+${gainedXP} XP`;
      xpChip.appendChild(floater);
      
      // Animate the XP value
      animateCounter(xpValue, currentXP, currentXP + gainedXP, 500);
      
      // Remove floater after animation
      setTimeout(() => floater.remove(), 1500);
    });
  }

  /**
   * Announce message to screen readers
   */
  function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.className = 'sr-only';
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    setTimeout(() => announcement.remove(), 1000);
  }

  /**
   * Add CSS for animations
   */
  function addAnimationStyles() {
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .fade-in-up {
        animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
      }
      
      @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
      }
      
      .shake {
        animation: shake 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      }
      
      .ripple {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: translate(-50%, -50%);
        animation: rippleEffect 0.6s ease-out;
        pointer-events: none;
      }
      
      @keyframes rippleEffect {
        to {
          width: 200px;
          height: 200px;
          opacity: 0;
        }
      }
      
      .streak-celebrate {
        animation: celebrate 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      }
      
      @keyframes celebrate {
        0%, 100% { transform: scale(1); }
        25% { transform: scale(1.1) rotate(-5deg); }
        75% { transform: scale(1.1) rotate(5deg); }
      }
      
      .xp-floater {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: var(--sunset-gold);
        font-weight: bold;
        font-size: 1.25rem;
        animation: floatUp 1.5s ease-out forwards;
        pointer-events: none;
      }
      
      @keyframes floatUp {
        to {
          transform: translate(-50%, -250%);
          opacity: 0;
        }
      }
      
      .tooltip-popup {
        background: var(--forest-deep, #0a2818);
        color: var(--text-on-glass);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        max-width: 200px;
        text-align: center;
        animation: tooltipIn 0.3s ease-out;
      }
      
      @keyframes tooltipIn {
        from {
          opacity: 0;
          transform: translate(-50%, -90%);
        }
        to {
          opacity: 1;
          transform: translate(-50%, -100%);
        }
      }
    `;
    
    document.head.appendChild(style);
  }

  /**
   * Initialize all components
   */
  function init() {
    // Add animation styles
    addAnimationStyles();
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initComponents);
    } else {
      initComponents();
    }
  }

  /**
   * Initialize all dashboard components
   */
  function initComponents() {
    initProgressRings();
    initStreakCounter();
    initSkillTree();
    initIntersectionAnimations();
    initAchievementBadges();
    initXPAnimations();
    
    // Log initialization
    console.log('Gamified Dashboard initialized successfully');
  }

  // Start initialization
  init();

  // Export for external use if needed
  window.GamifiedDashboard = {
    initProgressRings,
    initStreakCounter,
    initSkillTree,
    initIntersectionAnimations,
    animateCounter
  };
})();
