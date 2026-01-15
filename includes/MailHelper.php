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
    /**
     * Send Status Change Notification
     */
    public static function sendStatusChange($email, $candidateName, $jobTitle, $status) {
        $subject = "";
        $body = "";
        $siteUrl = get_setting('site_url', 'https://hr.prismtechnologies.com.ng');

        switch ($status) {
            case 'reviewed':
                $subject = "Application Under Review: $jobTitle";
                $body = "Dear $candidateName,\n\n";
                $body .= "Your application for the position of <strong>$jobTitle</strong> is now being reviewed by our hiring team.\n";
                $body .= "We will notify you of any updates regarding your application status.\n";
                break;

            case 'shortlisted':
                $subject = "Good News! You've been Shortlisted: $jobTitle";
                $body = "Dear $candidateName,\n\n";
                $body .= "Congratulations! We are pleased to inform you that you have been shortlisted for the position of <strong>$jobTitle</strong> at Prism Technologies.\n";
                $body .= "Our team was impressed with your profile, and we will be in touch shortly regarding the next steps of the selection process.\n";
                break;

            case 'interviewed':
                $subject = "Interview Status Update: $jobTitle";
                $body = "Dear $candidateName,\n\n";
                $body .= "Thank you for taking the time to interview with us for the <strong>$jobTitle</strong> position.\n";
                $body .= "We are currently reviewing all candidate interviews and will get back to you with a final decision soon.\n";
                break;

            case 'offered':
                $subject = "Job Offer: $jobTitle";
                $body = "Dear $candidateName,\n\n";
                $body .= "Congratulations! We are excited to offer you the position of <strong>$jobTitle</strong> at Prism Technologies.\n";
                $body .= "Please check your dashboard or email for the official offer letter and further instructions.\n";
                $body .= "<br><a href='$siteUrl/dashboard' class='btn'>View Offer</a>";
                break;

            case 'hired':
                $subject = "Welcome to the Team!";
                $body = "Dear $candidateName,\n\n";
                $body .= "Welcome aboard! We are delighted to officially hire you for the <strong>$jobTitle</strong> position.\n";
                $body .= "We look forward to working with you. HR will be in touch regarding onboarding details.\n";
                break;

            case 'rejected':
                $subject = "Update on your application: $jobTitle";
                $body = "Dear $candidateName,\n\n";
                $body .= "Thank you for your interest in the <strong>$jobTitle</strong> position at Prism Technologies.\n";
                $body .= "After careful consideration, we regret to inform you that we will not be moving forward with your application at this time.\n";
                $body .= "We received many qualified applications, and this decision was not easy. We will keep your resume on file for future opportunities that match your skills.\n\n";
                $body .= "We wish you the best in your job search.\n";
                break;

            default:
                // Do not send email for unknown statuses or 'pending'
                return false;
        }

        $body .= "\n\nBest Regards,\nHR Team";

        return self::send($email, $subject, $body);
    }
    /**
     * Send Password Reset Email
     */
    public static function sendPasswordReset($email, $name, $resetLink) {
        $subject = "Password Reset Request";
        
        $body = "Dear $name,\n\n";
        $body .= "We received a request to reset your password for your account at Prism Technologies.\n";
        $body .= "Click the button below to reset it:\n";
        $body .= "<br><a href='$resetLink' class='btn'>Reset Password</a>\n\n";
        $body .= "If you did not request this, please ignore this email. This link will expire in 1 hour.\n";
        $body .= "Or paste this link into your browser: $resetLink";
        
        return self::send($email, $subject, $body);
    }
    /**
     * Send Interview Invitation Email
     */
    public static function sendInterviewInvitation($email, $name, $details) {
        $subject = "Interview Invitation: " . $details['job_title'];
        $date = date('l, F j, Y', strtotime($details['date']));
        $time = date('g:i A', strtotime($details['time']));
        
        // Google Calendar Link Generation
        // Format: https://calendar.google.com/calendar/render?action=TEMPLATE&text={title}&dates={start}/{end}&details={details}&location={location}
        $startTime = date('Ymd\THis', strtotime($details['date'] . ' ' . $details['time']));
        $endTime = date('Ymd\THis', strtotime($details['date'] . ' ' . $details['time'] . ' +1 hour')); // Assume 1 hour default
        
        $calTitle = urlencode("Interview for " . $details['job_title']);
        $calDetails = urlencode("Interview with Prism Technologies.\nVenue: " . $details['venue'] . "\nAddress: " . $details['address']);
        $calLocation = urlencode($details['venue'] . ', ' . $details['address']);
        
        $gCalLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$calTitle&dates=$startTime/$endTime&details=$calDetails&location=$calLocation";

        $body = "Dear $name,\n\n";
        $body .= "We are pleased to invite you for an interview for the position of <strong>" . $details['job_title'] . "</strong>.\n\n";
        $body .= "<strong>Date:</strong> $date\n";
        $body .= "<strong>Time:</strong> $time\n";
        $body .= "<strong>Venue:</strong> " . $details['venue'] . "\n";
        $body .= "<strong>Address:</strong> " . $details['address'] . "\n";
        
        if (!empty($details['map_link'])) {
            $body .= "<a href='" . $details['map_link'] . "'>View on Google Maps</a>\n";
        }
        
        $body .= "\n<br><a href='$gCalLink' class='btn' style='background-color:#4285F4;'>Add to Google Calendar</a>\n\n";
        $body .= "Please arrive 15 minutes early and bring a copy of your CV/Resume.\n";
        $body .= "On the day of the interview, please log in to your candidate portal to check in.\n";
        
        return self::send($email, $subject, $body);
    }
}
?>
