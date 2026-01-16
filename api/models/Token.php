<?php
class Token
{
    private $pdo;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    public function save($portalDomain, $tokenData)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + 3500);

        $token = $this->getByPortal($portalDomain);
        if (!empty($token)) {
            return $this->updateAccessToken($portalDomain, $tokenData['access_token'], $tokenData['refresh_token'], $expiresAt);
        } else {
            $stmt = $this->pdo->prepare("
            INSERT INTO portal_oauth_tokens 
            (portal_domain, client_id, client_secret, access_token, 
             refresh_token, member_id, user_id, expires_at)
            VALUES (:portal, :client_id, :client_secret, :access_token, 
                    :refresh_token, :member_id, :user_id, :expires_at)
            ON DUPLICATE KEY UPDATE
                access_token = :access_token,
                refresh_token = :refresh_token,
                expires_at = :expires_at,
                client_id = :client_id,
                client_secret = :client_secret
        ");

            return $stmt->execute([
                ':portal' => $portalDomain,
                ':client_id' => $tokenData['client_id'],
                ':client_secret' => $tokenData['client_secret'],
                ':access_token' => $tokenData['access_token'],
                ':refresh_token' => $tokenData['refresh_token'],
                ':member_id' => $tokenData['member_id'],
                ':user_id' => $tokenData['user_id'] ?? 0,
                ':expires_at' => $expiresAt
            ]);
        }
    }

    public function getByPortal($portalDomain)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM portal_oauth_tokens 
            WHERE portal_domain = :portal
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':portal' => $portalDomain]);
        return $stmt->fetch();
    }

    public function updateAccessToken($portalDomain, $accessToken, $refresh_token, $expiresIn = 3600)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        $stmt = $this->pdo->prepare("
            UPDATE portal_oauth_tokens 
            SET access_token = :access_token,
                refresh_token = :refresh_token,
                expires_at = :expires_at,
                updated_at = NOW()
            WHERE portal_domain = :portal
        ");

        return $stmt->execute([
            ':portal' => $portalDomain,
            ':access_token' => $accessToken,
            ':expires_at' => $expiresAt,
            ':refresh_token' => $refresh_token
        ]);
    }

    public function isExpired($portalDomain)
    {
        $token = $this->getByPortal($portalDomain);
        if (!$token) {
            return true;
        }

        $expiresAt = strtotime($token['expires_at']);
        $currentTime = time();

        // Считаем токен просроченным за 5 минут до реального истечения
        return ($expiresAt - $currentTime) < 300;
    }
}
