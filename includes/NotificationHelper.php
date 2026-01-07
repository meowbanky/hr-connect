<?php
// includes/NotificationHelper.php

require_once __DIR__ . '/../config/db.php';

class NotificationHelper {
    
    /**
     * Create a new in-app notification
     * 
     * @param int $userId The ID of the user to notify
     * @param string $type The type of notification (application, interview, system, message)
     * @param string $title The notification title
     * @param string $message The notification message body
     * @param string|null $actionUrl Optional URL to link the notification to
     * @return bool Success status
     */
    public static function create($userId, $type, $title, $message, $actionUrl = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, action_url, created_at, is_read) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
            return $stmt->execute([$userId, $type, $title, $message, $actionUrl]);
        } catch (PDOException $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark a notification as read
     */
    public static function markAsRead($notificationId, $userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Mark ALL notifications as read for a user
     */
    public static function markAllAsRead($userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Archive a notification
     */
    public static function archive($notificationId, $userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_archived = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Unarchive a notification
     */
    public static function unarchive($notificationId, $userId) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_archived = 0 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Delete a notification
     */
    public static function delete($notificationId, $userId) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }
}
?>
