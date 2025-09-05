/**
 * Confetti.js - Lightweight Celebration Animation Library
 * 
 * Creates beautiful confetti explosions for achievement celebrations.
 * Supports multiple styles, colors, and animation patterns.
 */

(function(window) {
    'use strict';

    class Confetti {
        constructor(options = {}) {
            this.canvas = null;
            this.ctx = null;
            this.particles = [];
            this.animationId = null;
            this.defaults = {
                particleCount: 100,
                spread: 90,
                startVelocity: 45,
                decay: 0.9,
                gravity: 0.5,
                drift: 0,
                ticks: 200,
                x: 0.5,
                y: 0.5,
                shapes: ['square', 'circle'],
                colors: [
                    '#f44336', '#e91e63', '#9c27b0', '#673ab7',
                    '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4',
                    '#009688', '#4caf50', '#8bc34a', '#cddc39',
                    '#ffeb3b', '#ffc107', '#ff9800', '#ff5722'
                ],
                scalar: 1,
                duration: 2000,
                stagger: 0,
                perspective: 500
            };
            
            this.init();
        }

        init() {
            // Create and setup canvas
            this.canvas = document.createElement('canvas');
            this.canvas.id = 'confetti-canvas';
            this.canvas.style.position = 'fixed';
            this.canvas.style.top = '0';
            this.canvas.style.left = '0';
            this.canvas.style.width = '100%';
            this.canvas.style.height = '100%';
            this.canvas.style.pointerEvents = 'none';
            this.canvas.style.zIndex = '9999';
            
            this.ctx = this.canvas.getContext('2d');
            this.updateCanvasSize();
            
            // Handle window resize
            window.addEventListener('resize', () => this.updateCanvasSize());
        }

        updateCanvasSize() {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        }

        fire(options = {}) {
            const config = { ...this.defaults, ...options };
            
            // Add canvas to DOM if not already present
            if (!document.body.contains(this.canvas)) {
                document.body.appendChild(this.canvas);
            }
            
            // Create particles
            this.createParticles(config);
            
            // Start animation
            this.animate(config);
            
            // Remove canvas after animation
            setTimeout(() => {
                this.stop();
            }, config.duration);
        }

        createParticles(config) {
            const count = config.particleCount;
            const { x, y, spread, startVelocity, colors, shapes, scalar } = config;
            
            for (let i = 0; i < count; i++) {
                const particle = {
                    x: x * this.canvas.width,
                    y: y * this.canvas.height,
                    vx: (Math.random() - 0.5) * spread,
                    vy: -(Math.random() * startVelocity + startVelocity / 2),
                    shape: shapes[Math.floor(Math.random() * shapes.length)],
                    color: colors[Math.floor(Math.random() * colors.length)],
                    size: (Math.random() * 0.5 + 0.5) * 10 * scalar,
                    tilt: Math.random() * Math.PI,
                    tiltAngleIncrement: Math.random() * 0.1 + 0.05,
                    tiltAngle: 0,
                    rotation: Math.random() * Math.PI * 2,
                    rotationSpeed: (Math.random() - 0.5) * 0.2,
                    life: 1,
                    decay: config.decay
                };
                
                // Add stagger effect
                if (config.stagger > 0) {
                    particle.delay = Math.random() * config.stagger;
                } else {
                    particle.delay = 0;
                }
                
                this.particles.push(particle);
            }
        }

        animate(config) {
            const { gravity, drift, ticks } = config;
            let tick = 0;
            
            const update = () => {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                
                const activeParticles = [];
                
                for (const particle of this.particles) {
                    // Skip if delayed
                    if (particle.delay > 0) {
                        particle.delay--;
                        activeParticles.push(particle);
                        continue;
                    }
                    
                    // Update physics
                    particle.x += particle.vx + drift;
                    particle.y += particle.vy;
                    particle.vy += gravity;
                    particle.tiltAngle += particle.tiltAngleIncrement;
                    particle.rotation += particle.rotationSpeed;
                    particle.life *= particle.decay;
                    
                    // Apply decay to velocity
                    particle.vx *= config.decay;
                    particle.vy *= config.decay;
                    
                    // Draw particle
                    if (particle.life > 0.1) {
                        this.drawParticle(particle);
                        activeParticles.push(particle);
                    }
                }
                
                this.particles = activeParticles;
                
                tick++;
                
                if (this.particles.length > 0 && tick < ticks) {
                    this.animationId = requestAnimationFrame(update);
                } else {
                    this.stop();
                }
            };
            
            update();
        }

        drawParticle(particle) {
            const { x, y, color, size, shape, rotation, life, tiltAngle } = particle;
            
            this.ctx.save();
            this.ctx.globalAlpha = life;
            this.ctx.translate(x, y);
            this.ctx.rotate(rotation);
            
            // 3D tilt effect
            const tiltX = Math.cos(tiltAngle) * size;
            const tiltY = Math.sin(tiltAngle) * size * 0.5;
            
            this.ctx.fillStyle = color;
            this.ctx.strokeStyle = color;
            
            switch (shape) {
                case 'circle':
                    this.ctx.beginPath();
                    this.ctx.ellipse(0, 0, tiltX / 2, tiltY / 2, 0, 0, Math.PI * 2);
                    this.ctx.fill();
                    break;
                    
                case 'square':
                default:
                    this.ctx.fillRect(-tiltX / 2, -tiltY / 2, tiltX, tiltY);
                    break;
                    
                case 'star':
                    this.drawStar(0, 0, 5, size, size / 2);
                    break;
                    
                case 'triangle':
                    this.ctx.beginPath();
                    this.ctx.moveTo(0, -tiltY / 2);
                    this.ctx.lineTo(-tiltX / 2, tiltY / 2);
                    this.ctx.lineTo(tiltX / 2, tiltY / 2);
                    this.ctx.closePath();
                    this.ctx.fill();
                    break;
            }
            
            this.ctx.restore();
        }

        drawStar(cx, cy, spikes, outerRadius, innerRadius) {
            let rot = Math.PI / 2 * 3;
            let x = cx;
            let y = cy;
            const step = Math.PI / spikes;

            this.ctx.beginPath();
            this.ctx.moveTo(cx, cy - outerRadius);

            for (let i = 0; i < spikes; i++) {
                x = cx + Math.cos(rot) * outerRadius;
                y = cy + Math.sin(rot) * outerRadius;
                this.ctx.lineTo(x, y);
                rot += step;

                x = cx + Math.cos(rot) * innerRadius;
                y = cy + Math.sin(rot) * innerRadius;
                this.ctx.lineTo(x, y);
                rot += step;
            }

            this.ctx.lineTo(cx, cy - outerRadius);
            this.ctx.closePath();
            this.ctx.fill();
        }

        burst(options = {}) {
            // Burst effect - multiple fires with slight delays
            const burstCount = options.burstCount || 3;
            const burstDelay = options.burstDelay || 100;
            
            for (let i = 0; i < burstCount; i++) {
                setTimeout(() => {
                    this.fire({
                        ...options,
                        particleCount: Math.floor((options.particleCount || 100) / burstCount),
                        startVelocity: (options.startVelocity || 45) * (1 - i * 0.2),
                        spread: (options.spread || 90) * (1 + i * 0.2)
                    });
                }, i * burstDelay);
            }
        }

        cannon(options = {}) {
            // Cannon effect - directional burst
            this.fire({
                ...options,
                spread: options.spread || 30,
                startVelocity: options.startVelocity || 80,
                gravity: options.gravity || 0.8,
                shapes: options.shapes || ['square'],
                x: options.x || 0.5,
                y: options.y || 1
            });
        }

        shower(options = {}) {
            // Shower effect - continuous rain of particles
            const showerDuration = options.duration || 3000;
            const interval = options.interval || 100;
            let elapsed = 0;
            
            const showerInterval = setInterval(() => {
                this.fire({
                    ...options,
                    particleCount: options.particleCount || 10,
                    spread: options.spread || 180,
                    startVelocity: options.startVelocity || 10,
                    gravity: options.gravity || 0.3,
                    y: options.y || 0,
                    stagger: 0
                });
                
                elapsed += interval;
                if (elapsed >= showerDuration) {
                    clearInterval(showerInterval);
                }
            }, interval);
        }

        stop() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
            
            this.particles = [];
            
            if (this.canvas && document.body.contains(this.canvas)) {
                document.body.removeChild(this.canvas);
            }
        }

        // Preset configurations for different achievement rarities
        static presets = {
            common: {
                particleCount: 50,
                spread: 70,
                colors: ['#4caf50', '#8bc34a', '#cddc39']
            },
            rare: {
                particleCount: 100,
                spread: 90,
                colors: ['#2196f3', '#03a9f4', '#00bcd4'],
                shapes: ['square', 'circle']
            },
            epic: {
                particleCount: 150,
                spread: 120,
                colors: ['#9c27b0', '#673ab7', '#e91e63'],
                shapes: ['square', 'circle', 'star'],
                startVelocity: 55
            },
            legendary: {
                particleCount: 200,
                spread: 180,
                colors: ['#ff9800', '#ffc107', '#ffeb3b', '#f44336'],
                shapes: ['square', 'circle', 'star', 'triangle'],
                startVelocity: 65,
                scalar: 1.5,
                duration: 3000
            }
        };
    }

    // Create global instance
    window.confetti = new Confetti();
    
    // Export for modules
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = Confetti;
    }
    
})(window);