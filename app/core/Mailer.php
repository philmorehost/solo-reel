<?php

namespace App\Core;

class Mailer {
    public static function send(string $to, string $subject, string $bodyHtml) {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body_html, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$to, $subject, $bodyHtml]);
    }
}
