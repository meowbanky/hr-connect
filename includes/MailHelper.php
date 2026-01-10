<?php
// includes/MailHelper.php
// A simple helper to manage email sending (mocked for now, but ready for PHPMailer/SMTP)

class MailHelper {
    
    /**
     * Send an email (Mock implementation)
     * In production, this would use PHPMailer or a similar library.
     */
    /**
     * Send an email using PHPMailer
     */
    public static function send($to, $subject, $body, $isHtml = true) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // As per port 587
            $mail->Port       = $_ENV['SMTP_PORT'];

            // Recipients
            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($to);
            $mail->addReplyTo($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = self::getTemplate($subject, $body);
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Basic HTML Email Template
     */
    private static function getTemplate($title, $content) {
        $year = date('Y');
        $siteUrl = get_setting('site_url', 'https://hr.prismtechnologies.com.ng');
        $logoUrl = "https://via.placeholder.com/150x50.png?text=Prism+HR"; // Replace with actual logo if available
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #1a1a2e; padding: 20px; text-align: center; }
                .header img { max-height: 40px; }
                .header h1 { color: #fff; margin: 10px 0 0; font-size: 20px; }
                .content { padding: 30px; color: #555; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #999; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$title</h1>
                </div>
                <div class='content'>
                    " . nl2br($content) . "
                </div>
                <div class='footer'>
                    &copy; $year Prism Technologies. All rights reserved.<br>
                    <a href='$siteUrl' style='color:#999'>Visit Dashboard</a>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Send Application Success Email
     */
    public static function sendApplicationReceived($email, $candidateName, $jobTitle) {
        $subject = "Application Received: $jobTitle";
        
        $body = "Dear $candidateName,\n\n";
        $body .= "Thank you for applying for the position of <strong>$jobTitle</strong> at Prism Technologies.\n";
        $body .= "We have received your application and it is currently under review by our hiring team.\n\n";
        $body .= "You can track your application status in your candidate dashboard.\n";
        $body .= "<br><a href='" . get_setting('site_url', 'https://hr.prismtechnologies.com.ng') . "/dashboard' class='btn'>Track Application</a>\n\n";
        $body .= "Best Regards,\nHR Team";
        
        return self::send($email, $subject, $body);
    }
    
    /**
     * Send Admin New Application Notification
     */
    public static function sendAdminNewApplication($adminEmail, $candidateName, $jobTitle) {
        $subject = "New Application: $jobTitle";
        
        $body = "Dear Admin,\n\n";
        $body .= "A new application has been submitted for the position of <strong>$jobTitle</strong>.\n";
        $body .= "<strong>Candidate:</strong> $candidateName\n\n";
        $body .= "Please log in to the admin dashboard to review the application details and take action.\n";
        $body .= "<br><a href='" . get_setting('site_url', 'https://hr.prismtechnologies.com.ng') . "/admin' class='btn'>Review Application</a>";
        
        return self::send($adminEmail, $subject, $body);
    }
}
?>
