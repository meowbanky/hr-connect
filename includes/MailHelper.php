<?php
// includes/MailHelper.php
// A simple helper to manage email sending (mocked for now, but ready for PHPMailer/SMTP)

class MailHelper {
    
    /**
     * Send an email (Mock implementation)
     * In production, this would use PHPMailer or a similar library.
     */
    public static function send($to, $subject, $body) {
        // For development, we'll log the email to a file instead of sending it.
        // This prevents errors if no mail server is configured locally.
        
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . 'mail.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "[$timestamp] To: $to | Subject: $subject\r\n";
        $logEntry .= "Body: $body\r\n";
        $logEntry .= "--------------------------------------------------\r\n";
        
        // Try file_put_contents
        if (file_put_contents($logFile, $logEntry, FILE_APPEND) === false) {
             // Fallback or error logging
             error_log("Failed to write to mail log: $logFile");
             return false;
        }
        
        return true; 
    }
    
    /**
     * Send Application Success Email
     */
    public static function sendApplicationReceived($email, $candidateName, $jobTitle) {
        $subject = "Application Received: $jobTitle";
        $body = "Dear $candidateName,\n\n";
        $body .= "Thank you for applying for the position of $jobTitle at OOUTH HR.\n";
        $body .= "We have received your application and it is currently under review.\n\n";
        $body .= "You can track your application status in your dashboard: " . get_setting('site_url', 'https://hr.prismtechnologies.com.ng') . "/dashboard\n\n";
        $body .= "Best Regards,\nHR Team";
        
        return self::send($email, $subject, $body);
    }
    
    /**
     * Send Admin New Application Notification
     */
    public static function sendAdminNewApplication($adminEmail, $candidateName, $jobTitle) {
        $subject = "New Application Received: $jobTitle";
        $body = "Dear Admin,\n\n";
        $body .= "A new application has been submitted for the position of **$jobTitle**.\n";
        $body .= "Candidate: $candidateName\n\n";
        $body .= "Please log in to the admin dashboard to review the application details.\n\n";
        $body .= "Regards,\nSystem";
        
        return self::send($adminEmail, $subject, $body);
    }
}
?>
