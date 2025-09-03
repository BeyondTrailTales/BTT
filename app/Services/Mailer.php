<?php
/**
 * BeyondTrailTales - Email Service
 * 
 * Handles all email sending functionality with development fallback
 * Following Context7 best practices
 */

namespace App\Services;

use Exception;

class Mailer {
    
    private $config;
    private $isDevelopment;
    
    public function __construct() {
        $this->config = $this->getConfig();
        $this->isDevelopment = defined('BTT_DEBUG') && BTT_DEBUG;
    }
    
    /**
     * Get email configuration
     */
    private function getConfig() {
        return [
            'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : '',
            'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
            'smtp_encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls',
            'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
            'smtp_password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
            'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@beyondtrailtales.local',
            'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'BeyondTrailTales',
        ];
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $username, $token) {
        $verifyUrl = BASE_URL . '/public/auth/verify-email.php?token=' . urlencode($token);
        
        $subject = 'Verify Your BeyondTrailTales Account';
        
        // Load template
        $body = $this->getEmailTemplate('verify_email', [
            'username' => $username,
            'verify_url' => $verifyUrl,
            'expires_in' => '24 hours'
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $username, $token) {
        $resetUrl = BASE_URL . '/public/auth/reset-password.php?token=' . urlencode($token);
        
        $subject = 'Reset Your BeyondTrailTales Password';
        
        // Load template
        $body = $this->getEmailTemplate('reset_password', [
            'username' => $username,
            'reset_url' => $resetUrl,
            'expires_in' => '1 hour'
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send share invitation email
     */
    public function sendShareInvitation($email, $tripName, $inviterName, $shareCode) {
        $redeemUrl = BASE_URL . '/public/share/redeem.php?code=' . urlencode($shareCode);
        
        $subject = $inviterName . ' invited you to collaborate on a trip';
        
        $body = $this->getEmailTemplate('share_invitation', [
            'trip_name' => $tripName,
            'inviter_name' => $inviterName,
            'redeem_url' => $redeemUrl,
            'share_code' => $shareCode
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $variables = []) {
        $templatePath = BASE_PATH . '/assets/email/' . $template . '.html.php';
        
        // Create default template if it doesn't exist
        if (!file_exists($templatePath)) {
            return $this->getDefaultTemplate($template, $variables);
        }
        
        // Extract variables for use in template
        extract($variables);
        
        // Capture template output
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Get default email template
     */
    private function getDefaultTemplate($template, $variables) {
        switch ($template) {
            case 'verify_email':
                return $this->getVerifyEmailTemplate($variables);
            case 'reset_password':
                return $this->getResetPasswordTemplate($variables);
            case 'share_invitation':
                return $this->getShareInvitationTemplate($variables);
            default:
                return $this->getBasicTemplate($variables);
        }
    }
    
    /**
     * Verify email template
     */
    private function getVerifyEmailTemplate($vars) {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a; color: #e0e0e0;">
    <div style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div style="background-color: #2a2a2a; border-radius: 8px; padding: 40px; border: 1px solid #4ade80;">
            <h1 style="color: #4ade80; margin-top: 0;">🏔️ Welcome to BeyondTrailTales!</h1>
            
            <p>Hi {$vars['username']},</p>
            
            <p>Thanks for creating an account! Please verify your email address by clicking the button below:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$vars['verify_url']}" 
                   style="display: inline-block; padding: 12px 30px; background-color: #4ade80; 
                          color: #1a1a1a; text-decoration: none; border-radius: 4px; 
                          font-weight: bold; font-size: 16px;">
                    Verify Email Address
                </a>
            </div>
            
            <p style="color: #9ca3af; font-size: 14px;">
                Or copy and paste this link into your browser:<br>
                <span style="word-break: break-all; color: #4ade80;">{$vars['verify_url']}</span>
            </p>
            
            <p style="color: #9ca3af; font-size: 14px; margin-top: 30px;">
                This link will expire in {$vars['expires_in']}.
            </p>
            
            <hr style="border: none; border-top: 1px solid #3a3a3a; margin: 30px 0;">
            
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                If you didn't create an account, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Reset password template
     */
    private function getResetPasswordTemplate($vars) {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a; color: #e0e0e0;">
    <div style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div style="background-color: #2a2a2a; border-radius: 8px; padding: 40px; border: 1px solid #4ade80;">
            <h1 style="color: #4ade80; margin-top: 0;">🔐 Password Reset Request</h1>
            
            <p>Hi {$vars['username']},</p>
            
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$vars['reset_url']}" 
                   style="display: inline-block; padding: 12px 30px; background-color: #4ade80; 
                          color: #1a1a1a; text-decoration: none; border-radius: 4px; 
                          font-weight: bold; font-size: 16px;">
                    Reset Password
                </a>
            </div>
            
            <p style="color: #9ca3af; font-size: 14px;">
                Or copy and paste this link into your browser:<br>
                <span style="word-break: break-all; color: #4ade80;">{$vars['reset_url']}</span>
            </p>
            
            <p style="color: #9ca3af; font-size: 14px; margin-top: 30px;">
                This link will expire in {$vars['expires_in']}.
            </p>
            
            <hr style="border: none; border-top: 1px solid #3a3a3a; margin: 30px 0;">
            
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                If you didn't request a password reset, please ignore this email. 
                Your password won't be changed unless you click the link above.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Share invitation template
     */
    private function getShareInvitationTemplate($vars) {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a; color: #e0e0e0;">
    <div style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div style="background-color: #2a2a2a; border-radius: 8px; padding: 40px; border: 1px solid #4ade80;">
            <h1 style="color: #4ade80; margin-top: 0;">🏕️ You're Invited to Collaborate!</h1>
            
            <p><strong>{$vars['inviter_name']}</strong> has invited you to collaborate on the trip:</p>
            
            <div style="background-color: #1a1a1a; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <h2 style="color: #4ade80; margin: 0;">{$vars['trip_name']}</h2>
            </div>
            
            <p>To accept this invitation and start collaborating:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$vars['redeem_url']}" 
                   style="display: inline-block; padding: 12px 30px; background-color: #4ade80; 
                          color: #1a1a1a; text-decoration: none; border-radius: 4px; 
                          font-weight: bold; font-size: 16px;">
                    Accept Invitation
                </a>
            </div>
            
            <p style="color: #9ca3af; font-size: 14px;">
                Or use this share code: <strong style="color: #4ade80; font-family: monospace;">{$vars['share_code']}</strong>
            </p>
            
            <hr style="border: none; border-top: 1px solid #3a3a3a; margin: 30px 0;">
            
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                If you don't want to collaborate on this trip, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Basic template fallback
     */
    private function getBasicTemplate($vars) {
        $content = isset($vars['content']) ? $vars['content'] : 'No content provided';
        $subject = isset($vars['subject']) ? $vars['subject'] : 'BeyondTrailTales Notification';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a; color: #e0e0e0;">
    <div style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div style="background-color: #2a2a2a; border-radius: 8px; padding: 40px; border: 1px solid #4ade80;">
            <h1 style="color: #4ade80; margin-top: 0;">🏔️ BeyondTrailTales</h1>
            {$content}
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Send email (with development fallback)
     */
    private function send($to, $subject, $htmlBody) {
        try {
            // In development mode or if SMTP not configured, log email instead
            if ($this->isDevelopment || empty($this->config['smtp_host'])) {
                return $this->logEmail($to, $subject, $htmlBody);
            }
            
            // TODO: Implement actual SMTP sending with PHPMailer
            // For now, we'll use the development fallback
            return $this->logEmail($to, $subject, $htmlBody);
            
        } catch (Exception $e) {
            btt_log("Email send failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Log email for development
     */
    private function logEmail($to, $subject, $body) {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/mail.log';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'sent' => true
        ];
        
        // Log the email details
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // In development, also save the HTML for viewing
        if ($this->isDevelopment) {
            $emailDir = BASE_PATH . '/test/emails';
            if (!is_dir($emailDir)) {
                mkdir($emailDir, 0755, true);
            }
            
            $emailFile = $emailDir . '/' . date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $subject) . '.html';
            file_put_contents($emailFile, $body);
            
            // Extract and display magic links in console/test pages
            $this->extractMagicLinks($subject, $body);
        }
        
        btt_log("Email queued: $subject to $to", 'INFO');
        return true;
    }
    
    /**
     * Extract and display magic links for development
     */
    private function extractMagicLinks($subject, $body) {
        $links = [];
        
        // Extract verification links
        if (preg_match('/verify-email\.php\?token=([a-zA-Z0-9]+)/', $body, $matches)) {
            $links['verification'] = BASE_URL . '/public/auth/verify-email.php?token=' . $matches[1];
        }
        
        // Extract reset links
        if (preg_match('/reset-password\.php\?token=([a-zA-Z0-9]+)/', $body, $matches)) {
            $links['reset'] = BASE_URL . '/public/auth/reset-password.php?token=' . $matches[1];
        }
        
        // Extract share codes
        if (preg_match('/share code: <strong[^>]*>([A-Z0-9]+)<\/strong>/', $body, $matches)) {
            $links['share_code'] = $matches[1];
        }
        
        if (!empty($links)) {
            $magicFile = BASE_PATH . '/test/magic-links.json';
            $existing = [];
            
            if (file_exists($magicFile)) {
                $existing = json_decode(file_get_contents($magicFile), true) ?: [];
            }
            
            $existing[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'subject' => $subject,
                'links' => $links
            ];
            
            // Keep only last 10 entries
            $existing = array_slice($existing, -10);
            
            file_put_contents($magicFile, json_encode($existing, JSON_PRETTY_PRINT));
        }
    }
}
