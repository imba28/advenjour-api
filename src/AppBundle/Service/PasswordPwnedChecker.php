<?php
namespace AppBundle\Service;

use Pimcore\Http\ClientFactory;
use Pimcore\Log\ApplicationLogger;

class PasswordPwnedChecker
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ApplicationLogger
     */
    private $logger;

    public function __construct(ClientFactory $clientFactory, ApplicationLogger $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Checks have-i-been-pwned api for possible breached password.
     * @param string $password
     * @return bool
     */
    public function isPwned(string $password, int $threshold = 2): bool
    {
        $hash = sha1($password);
        $partialHash = substr($hash, 0, 5);

        $client = $this->clientFactory->createClient();
        $result = $client->get("https://api.pwnedpasswords.com/range/{$partialHash}");

        if ($result->getStatusCode() !== 200) {
            return false;
        }

        $content = $result->getBody()->getContents();
        $hashSuffix = substr($hash, 5, 35);

        if (preg_match("/^{$hashSuffix}\:([\d]+)/im", $content, $m)) {
            if (intval($m[1]) > $threshold) {
                $this->logger->info("Successfully found a breached password (used {$m[1]} times)!");
                return true;
            }
        }

        return false;
    }
}