<?php
namespace AppBundle\Service;

use Firebase\JWT\JWT;
use Pimcore\Model\DataObject\User;
use Pimcore\Tool;

class JwtTokenGenerator
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /** Generate new JWT payload
     * @param User $user
     * @return string
     */
    public function getPayload(User $user)
    {
        $payload = [
            'iss' => Tool::getHostUrl(),
            'aud' => Tool::getHostUrl(),
            'iat' => time(),
            'sub' => 'user',
            'uid' => $user->getId(),
            'ip' => Tool::getClientIp(),
            'email' => $user->getEmail()
        ];

        return JWT::encode($payload, $this->secret);
    }

    public function decodePayload(string $token)
    {
        return JWT::decode($token, $this->secret, ['HS256']);
    }
}