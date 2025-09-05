/**
 * Duolingo-Style Confirmation Dialogs
 * Beautiful, animated confirmation dialogs for pack builder actions
 */

(function($) {
    'use strict';
    
    // Create confirmation dialog HTML structure
    const createConfirmationHTML = () => {
        const html = `
            <div class="duo-overlay" id="duo-overlay"></div>
            <div class="duo-confirmation" id="duo-confirmation">
                <div class="duo-conf-icon">
                    <span class="icon-emoji">✅</span>
                </div>
                <h3 class="duo-conf-title">Success!</h3>
                <p class="duo-conf-message">Your action was completed successfully.</p>
                <div class="duo-conf-progress">
                    <div class="duo-conf-progress-bar"></div>
                </div>
                <div class="duo-conf-actions">
                    <button class="duo-conf-btn duo-conf-btn-primary">Continue</button>
                    <button class="duo-conf-btn duo-conf-btn-secondary">View Details</button>
                </div>
                <div class="duo-conf-stats">
                    <div class="conf-stat">
                        <span class="conf-stat-icon">📦</span>
                        <span class="conf-stat-value">0</span>
                        <span class="conf-stat-label">Items</span>
                    </div>
                    <div class="conf-stat">
                        <span class="conf-stat-icon">⚖️</span>
                        <span class="conf-stat-value">0kg</span>
                        <span class="conf-stat-label">Weight</span>
                    </div>
                    <div class="conf-stat">
                        <span class="conf-stat-icon">⚡</span>
                        <span class="conf-stat-value">+50</span>
                        <span class="conf-stat-label">XP</span>
                    </div>
                </div>
            </div>
        `;
        
        if (!$('#duo-overlay').length) {
            $('body').append(html);
        }
    };
    
    // Duolingo Success Sound (simulated with Web Audio API)
    const playSuccessSound = () => {
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gainNode = context.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(context.destination);
            
            // Create a pleasant success sound
            oscillator.frequency.setValueAtTime(523.25, context.currentTime); // C5
            oscillator.frequency.setValueAtTime(659.25, context.currentTime + 0.1); // E5
            oscillator.frequency.setValueAtTime(783.99, context.currentTime + 0.2); // G5
            
            gainNode.gain.setValueAtTime(0.3, context.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
            
            oscillator.start(context.currentTime);
            oscillator.stop(context.currentTime + 0.5);
        } catch(e) {
            // Silently fail if audio is not supported
        }
    };
    
    // Show Duolingo-style confirmation
    window.showDuoConfirmation = function(options) {
        // Close any existing popup first to prevent stacking/blocking issues
        if ($('#duo-overlay').is(':visible') || $('#duo-confirmation').hasClass('show')) {
            closeConfirmation();
        }
        
        const defaults = {
            type: 'success', // success, warning, error, info
            title: 'Success!',
            message: 'Your action was completed successfully.',
            icon: '✅',
            items: null,
            weight: null,
            xp: 50,
            primaryAction: 'Continue',
            secondaryAction: null,
            onPrimary: null,
            onSecondary: null,
            autoClose: 3000,
            showProgress: true,
            playSound: true
        };
        
        const settings = $.extend({}, defaults, options);
        
        createConfirmationHTML();
        
        const $overlay = $('#duo-overlay');
        const $confirmation = $('#duo-confirmation');
        
        // Set content
        $confirmation.find('.icon-emoji').text(settings.icon);
        $confirmation.find('.duo-conf-title').text(settings.title);
        $confirmation.find('.duo-conf-message').text(settings.message);
        
        // Update stats if provided
        if (settings.items !== null) {
            $confirmation.find('.conf-stat-value').eq(0).text(settings.items);
        }
        if (settings.weight !== null) {
            $confirmation.find('.conf-stat-value').eq(1).text(settings.weight);
        }
        $confirmation.find('.conf-stat-value').eq(2).text('+' + settings.xp);
        
        // Set button text and visibility
        $confirmation.find('.duo-conf-btn-primary').text(settings.primaryAction);
        
        if (settings.secondaryAction) {
            $confirmation.find('.duo-conf-btn-secondary').text(settings.secondaryAction).show();
        } else {
            $confirmation.find('.duo-conf-btn-secondary').hide();
        }
        
        // Show/hide progress bar
        if (settings.showProgress && settings.autoClose) {
            $confirmation.find('.duo-conf-progress').show();
        } else {
            $confirmation.find('.duo-conf-progress').hide();
        }
        
        // Apply type-specific styling
        $confirmation.removeClass('success warning error info').addClass(settings.type);
        
        // Reset z-index and pointer-events before showing
        $overlay.css({
            'pointer-events': 'auto',
            'z-index': 9998
        });
        $confirmation.css({
            'pointer-events': 'auto',
            'z-index': 9999,
            'display': 'block'
        });
        
        // Show the confirmation with animation
        $overlay.fadeIn(200);
        $confirmation.addClass('show');
        
        // Play sound if enabled
        if (settings.playSound && settings.type === 'success') {
            playSuccessSound();
        }
        
        // Add sparkle animation for success
        if (settings.type === 'success') {
            createSparkles($confirmation.find('.duo-conf-icon'));
        }
        
        // Button handlers
        $confirmation.find('.duo-conf-btn-primary').off('click').on('click', function() {
            closeConfirmation();
            if (settings.onPrimary) settings.onPrimary();
        });
        
        $confirmation.find('.duo-conf-btn-secondary').off('click').on('click', function() {
            closeConfirmation();
            if (settings.onSecondary) settings.onSecondary();
        });
        
        // Auto-close with progress animation
        if (settings.autoClose) {
            const $progressBar = $confirmation.find('.duo-conf-progress-bar');
            $progressBar.css('width', '100%');
            
            setTimeout(() => {
                $progressBar.css({
                    'transition': `width ${settings.autoClose}ms linear`,
                    'width': '0%'
                });
            }, 50);
            
            setTimeout(() => {
                closeConfirmation();
            }, settings.autoClose);
        }
        
        // Failsafe: Always remove overlay after max time even if something fails
        setTimeout(() => {
            if ($('#duo-overlay').is(':visible')) {
                console.warn('Duo overlay failsafe cleanup triggered');
                closeConfirmation();
            }
        }, Math.max(settings.autoClose || 3000, 3000) + 1000);
        
        // Close on overlay click
        $overlay.off('click').on('click', closeConfirmation);
    };
    
    // Close confirmation
    const closeConfirmation = () => {
        $('#duo-overlay').fadeOut(200, function() {
            // Ensure overlay is completely hidden and not blocking clicks
            $(this).css({
                'display': 'none',
                'pointer-events': 'none',
                'z-index': -1
            });
        });
        $('#duo-confirmation').removeClass('show').css({
            'display': 'none',
            'pointer-events': 'none',
            'z-index': -1
        });
        $('.sparkle').remove();
        
        // Ensure body is clickable again
        $('body').css('pointer-events', 'auto');
    };
    
    // Create sparkle effects
    const createSparkles = ($container) => {
        for (let i = 0; i < 8; i++) {
            const sparkle = $('<div class="sparkle">✨</div>');
            sparkle.css({
                left: Math.random() * 100 + '%',
                top: Math.random() * 100 + '%',
                animationDelay: Math.random() * 0.5 + 's'
            });
            $container.append(sparkle);
        }
    };
    
    // Predefined confirmation types
    window.DuoConfirm = {
        packSaved: function(itemCount, weight) {
            showDuoConfirmation({
                type: 'success',
                title: 'Pack Saved!',
                message: 'Your pack has been saved to your collection.',
                icon: '🎒',
                items: itemCount + ' items',
                weight: weight,
                xp: 100,
                primaryAction: 'View My Packs',
                secondaryAction: 'Keep Editing',
                onPrimary: () => {
                    if (window.packBuilder) {
                        packBuilder.switchView('my-packs');
                    }
                },
                autoClose: 5000
            });
        },
        
        packUpdated: function(itemCount, weight) {
            showDuoConfirmation({
                type: 'success',
                title: 'Pack Updated!',
                message: 'Your changes have been saved.',
                icon: '✏️',
                items: itemCount + ' items',
                weight: weight,
                xp: 50,
                primaryAction: 'Continue',
                autoClose: 3000
            });
        },
        
        packExported: function(packName) {
            showDuoConfirmation({
                type: 'success',
                title: 'Pack Exported!',
                message: `${packName} has been downloaded as a JSON file.`,
                icon: '📤',
                xp: 25,
                primaryAction: 'OK',
                autoClose: 3000
            });
        },
        
        packDeleted: function(packName) {
            showDuoConfirmation({
                type: 'info',
                title: 'Pack Deleted',
                message: `${packName} has been removed from your collection.`,
                icon: '🗑️',
                xp: 0,
                primaryAction: 'OK',
                autoClose: 3000,
                playSound: false
            });
        },
        
        itemAdded: function(itemName) {
            // Mini confirmation for item additions
            const $mini = $('<div class="duo-mini-confirm">➕ ' + itemName + ' added!</div>');
            $('body').append($mini);
            $mini.addClass('show');
            
            setTimeout(() => {
                $mini.removeClass('show');
                setTimeout(() => $mini.remove(), 300);
            }, 2000);
        },
        
        error: function(message) {
            showDuoConfirmation({
                type: 'error',
                title: 'Oops!',
                message: message,
                icon: '❌',
                primaryAction: 'OK',
                autoClose: false,
                showProgress: false,
                playSound: false
            });
        }
    };
    
    // Add styles
    const styles = `
        <style>
        /* Overlay */
        .duo-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9998;
        }
        
        /* Confirmation Dialog */
        .duo-confirmation {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: linear-gradient(135deg, #2d5a3d 0%, #1a3d2e 100%);
            border: 2px solid rgba(88, 204, 2, 0.3);
            border-radius: 1.5rem;
            padding: 2rem;
            min-width: 400px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .duo-confirmation.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        /* Icon */
        .duo-conf-icon {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(88, 204, 2, 0.15);
            border: 3px solid #58cc02;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .icon-emoji {
            font-size: 2.5rem;
        }
        
        /* Title & Message */
        .duo-conf-title {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 800;
            color: white;
            margin: 0 0 0.5rem;
        }
        
        .duo-conf-message {
            text-align: center;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            margin: 0 0 1.5rem;
            line-height: 1.5;
        }
        
        /* Progress Bar */
        .duo-conf-progress {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            margin: 1.5rem 0;
            overflow: hidden;
        }
        
        .duo-conf-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #58cc02, #68d612);
            border-radius: 999px;
            width: 100%;
        }
        
        /* Stats */
        .duo-conf-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
        }
        
        .conf-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        
        .conf-stat-icon {
            font-size: 1.5rem;
        }
        
        .conf-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #58cc02;
        }
        
        .conf-stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Buttons */
        .duo-conf-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .duo-conf-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.625rem;
            font-weight: 600;
            font-size: 0.938rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            top: 0;
        }
        
        .duo-conf-btn-primary {
            background: #58cc02;
            color: white;
            box-shadow: 0 4px 0 #46a302;
        }
        
        .duo-conf-btn-primary:hover {
            background: #68d612;
            top: -2px;
            box-shadow: 0 6px 0 #46a302;
        }
        
        .duo-conf-btn-primary:active {
            top: 2px;
            box-shadow: 0 2px 0 #46a302;
        }
        
        .duo-conf-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .duo-conf-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: #58cc02;
            color: white;
        }
        
        /* Sparkles */
        .sparkle {
            position: absolute;
            font-size: 1rem;
            animation: sparkle 1s ease-out forwards;
            pointer-events: none;
        }
        
        @keyframes sparkle {
            0% {
                transform: translate(0, 0) scale(0);
                opacity: 1;
            }
            100% {
                transform: translate(var(--x, 50px), var(--y, -50px)) scale(1.5);
                opacity: 0;
            }
        }
        
        /* Mini Confirmation */
        .duo-mini-confirm {
            position: fixed;
            bottom: 100px;
            right: 20px;
            padding: 0.75rem 1.25rem;
            background: #58cc02;
            color: white;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.875rem;
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 9997;
            box-shadow: 0 4px 12px rgba(88, 204, 2, 0.4);
        }
        
        .duo-mini-confirm.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Type Variations */
        .duo-confirmation.error .duo-conf-icon {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }
        
        .duo-confirmation.error .conf-stat-value {
            color: #ef4444;
        }
        
        .duo-confirmation.warning .duo-conf-icon {
            background: rgba(245, 158, 11, 0.15);
            border-color: #f59e0b;
        }
        
        .duo-confirmation.info .duo-conf-icon {
            background: rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }
        
        /* Mobile Responsive */
        @media (max-width: 500px) {
            .duo-confirmation {
                min-width: 90%;
                margin: 0 5%;
            }
            
            .duo-conf-stats {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .conf-stat {
                flex-direction: row;
                gap: 0.5rem;
            }
        }
        </style>
    `;
    
    // Inject styles
    if (!$('#duo-confirmation-styles').length) {
        $('head').append(styles);
    }
    
})(jQuery);
