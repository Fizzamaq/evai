<?php
require_once __DIR__ . '/vendor/autoload.php'; // Google API Client

class CalendarManager {
    private $client;
    private $pdo;
    
    public function __construct($pdo) {
        $this->client = new Google_Client();
        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $this->client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->pdo = $pdo;
    }

    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }

    public function handleCallback($code) {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->storeToken($_SESSION['user_id'], $token);
            return true;
        } catch (Exception $e) {
            error_log("Calendar Auth Error: " . $e->getMessage());
            return false;
        }
    }

    public function createCalendarEvent($userId, $eventData) {
        try {
            $service = new Google_Service_Calendar($this->client);
            $this->client->setAccessToken($this->getToken($userId));

            $googleEvent = new Google_Service_Calendar_Event([
                'summary' => $eventData['title'],
                'description' => $eventData['description'],
                'start' => ['dateTime' => $eventData['start']],
                'end' => ['dateTime' => $eventData['end']],
                'attendees' => array_map(function($email) {
                    return ['email' => $email];
                }, $eventData['attendees'])
            ]);

            return $service->events->insert('primary', $googleEvent);
        } catch (Exception $e) {
            error_log("Calendar Event Error: " . $e->getMessage());
            return false;
        }
    }

    private function storeToken($userId, $token) {
        $this->pdo->prepare("
            INSERT INTO user_calendar_tokens 
            (user_id, access_token, refresh_token, expires_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                expires_at = VALUES(expires_at)
        ")->execute([
            $userId,
            $token['access_token'],
            $token['refresh_token'] ?? null,
            date('Y-m-d H:i:s', time() + $token['expires_in'])
        ]);
    }

    private function getToken($userId) {
        $stmt = $this->pdo->prepare("
            SELECT access_token, refresh_token, expires_at 
            FROM user_calendar_tokens 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (time() > strtotime($token['expires_at'])) {
            $this->client->refreshToken($token['refresh_token']);
            $newToken = $this->client->getAccessToken();
            $this->storeToken($userId, $newToken);
            return $newToken;
        }

        return $token['access_token'];
    }
}