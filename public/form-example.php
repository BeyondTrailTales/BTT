<?php
// Example Form Implementation
// Demonstrates form inputs with validation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Input & Validation Example - BackpackingTripTracker</title>
    
    <!-- Form Input Styles -->
    <link rel="stylesheet" href="css/form-inputs.css">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            padding: 2rem;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .form-row {
            display: grid;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row.two-cols {
            grid-template-columns: 1fr 1fr;
        }
        
        @media (max-width: 640px) {
            .form-row.two-cols {
                grid-template-columns: 1fr;
            }
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
        
        .demo-note {
            background: #fff3e0;
            border: 1px solid #ffcc80;
            color: #e65100;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>🎒 Trip Planning Form</h1>
            <p>Example form demonstrating input types and validation</p>
        </div>
        
        <div class="demo-note">
            ⚡ This form demonstrates client-side validation. Try submitting with empty required fields or invalid formats.
        </div>
        
        <form id="tripForm" data-validate>
            <!-- Basic Information Section -->
            <div class="form-section">
                <h2 class="section-title">Basic Information</h2>
                
                <div class="form-group">
                    <label for="tripName" class="form-label">
                        Trip Name
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="tripName" 
                           name="tripName" 
                           class="form-control" 
                           placeholder="e.g., Pacific Crest Trail Section Hike"
                           required
                           minlength="3"
                           maxlength="100">
                    <small class="form-text">Give your trip a memorable name</small>
                </div>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="startDate" class="form-label">
                            Start Date
                            <span class="required">*</span>
                        </label>
                        <input type="date" 
                               id="startDate" 
                               name="startDate" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endDate" class="form-label">
                            End Date
                            <span class="required">*</span>
                        </label>
                        <input type="date" 
                               id="endDate" 
                               name="endDate" 
                               class="form-control" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location" class="form-label">Location</label>
                    <div class="input-group">
                        <span class="input-group-text">📍</span>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               class="form-control" 
                               placeholder="Trail or park name">
                    </div>
                </div>
            </div>
            
            <!-- Trip Details Section -->
            <div class="form-section">
                <h2 class="section-title">Trip Details</h2>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="distance" class="form-label">
                            Total Distance (miles)
                        </label>
                        <input type="number" 
                               id="distance" 
                               name="distance" 
                               class="form-control" 
                               placeholder="0.0"
                               min="0"
                               max="5000"
                               step="0.1">
                    </div>
                    
                    <div class="form-group">
                        <label for="elevation" class="form-label">
                            Elevation Gain (feet)
                        </label>
                        <input type="number" 
                               id="elevation" 
                               name="elevation" 
                               class="form-control" 
                               placeholder="0"
                               min="0"
                               max="50000"
                               step="10">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="difficulty" class="form-label">Difficulty Level</label>
                    <select id="difficulty" name="difficulty" class="form-control">
                        <option value="">Select difficulty...</option>
                        <option value="easy">Easy - Well-maintained trails</option>
                        <option value="moderate">Moderate - Some challenging sections</option>
                        <option value="hard">Hard - Steep or technical terrain</option>
                        <option value="expert">Expert - Requires experience</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Trip Description</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="4" 
                              placeholder="Describe your planned route, highlights, and any special considerations..."
                              maxlength="500"></textarea>
                </div>
            </div>
            
            <!-- Contact Information Section -->
            <div class="form-section">
                <h2 class="section-title">Contact Information</h2>
                
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email Address
                            <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="your@email.com"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Emergency Contact Phone</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="(555) 123-4567">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="website" class="form-label">Trip Blog/Website</label>
                    <input type="url" 
                           id="website" 
                           name="website" 
                           class="form-control" 
                           placeholder="https://example.com">
                </div>
            </div>
            
            <!-- Preferences Section -->
            <div class="form-section">
                <h2 class="section-title">Preferences</h2>
                
                <div class="form-group">
                    <label class="form-label">Trip Type</label>
                    <div class="radio-group">
                        <div class="form-check">
                            <input type="radio" 
                                   id="typeBackpacking" 
                                   name="tripType" 
                                   value="backpacking"
                                   class="form-check-input">
                            <label for="typeBackpacking" class="form-check-label">
                                Backpacking
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" 
                                   id="typeDayHike" 
                                   name="tripType" 
                                   value="dayhike"
                                   class="form-check-input">
                            <label for="typeDayHike" class="form-check-label">
                                Day Hike
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" 
                                   id="typeThruHike" 
                                   name="tripType" 
                                   value="thruhike"
                                   class="form-check-input">
                            <label for="typeThruHike" class="form-check-label">
                                Thru-Hike
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Features Needed</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input type="checkbox" 
                                   id="featureWater" 
                                   name="features[]" 
                                   value="water"
                                   class="form-check-input">
                            <label for="featureWater" class="form-check-label">
                                Water Sources
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" 
                                   id="featureCamping" 
                                   name="features[]" 
                                   value="camping"
                                   class="form-check-input">
                            <label for="featureCamping" class="form-check-label">
                                Camping Spots
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" 
                                   id="featureResupply" 
                                   name="features[]" 
                                   value="resupply"
                                   class="form-check-input">
                            <label for="featureResupply" class="form-check-label">
                                Resupply Points
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               id="publicTrip" 
                               name="publicTrip" 
                               class="form-check-input"
                               role="switch">
                        <label for="publicTrip" class="form-check-label">
                            Make this trip public
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    Reset Form
                </button>
                <button type="submit" class="btn btn-primary">
                    Create Trip Plan
                </button>
            </div>
        </form>
    </div>
    
    <!-- Form Validation Script -->
    <script src="js/form-validation.js"></script>
    
    <script>
        // Custom validation for dates
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        if (startDate && endDate) {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            startDate.setAttribute('min', today);
            endDate.setAttribute('min', today);
            
            // Custom validator for end date
            window.formValidator.setCustomValidator(
                document.getElementById('tripForm'),
                endDate,
                (value) => {
                    if (value && startDate.value) {
                        if (new Date(value) < new Date(startDate.value)) {
                            return 'End date must be after start date';
                        }
                    }
                    return true;
                }
            );
        }
        
        // Custom submit handler
        window.formValidator.setSubmitHandler(
            document.getElementById('tripForm'),
            async (form, event) => {
                // Simulate API call
                const submitBtn = form.querySelector('[type="submit"]');
                submitBtn.textContent = 'Creating trip...';
                submitBtn.disabled = true;
                
                // Simulate delay
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Show success
                alert('Trip created successfully! (This is a demo)');
                
                // Reset button
                submitBtn.textContent = 'Create Trip Plan';
                submitBtn.disabled = false;
                
                // Reset form
                form.reset();
                window.clearFormValidation(form);
                
                return false; // Prevent actual submission
            }
        );
        
        // Reset form function
        function resetForm() {
            const form = document.getElementById('tripForm');
            if (confirm('Are you sure you want to reset all form data?')) {
                form.reset();
                window.clearFormValidation(form);
            }
        }
        
        // Initialize tooltips or other enhancements
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Form validation initialized');
        });
    </script>
</body>
</html>
