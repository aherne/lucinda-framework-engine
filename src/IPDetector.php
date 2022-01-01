<?php
namespace Lucinda\Framework;

use Lucinda\STDOUT\Request;

/**
 * Detects client IP based on contents of STDOUT Request object
 */
class IPDetector
{
    private string $ip;
    
    /**
     * Kick-starts ip detection process
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->setIP($request);
    }
    
    /**
     * Performs IP detection and saves result.
     *
     * @param Request $request
     */
    private function setIP(Request $request): void
    {
        $headers = $request->headers();
        $ip_keys = array('Client-Ip', 'X-Forwarded-For', 'X-Forwarded', 'X-Cluster-Client-Ip', 'Forwarded-For', 'Forwarded');
        foreach ($ip_keys as $key) {
            if (!empty($headers[$key])) {
                // trim for safety measures
                $ip = trim(explode(',', $headers[$key])[0]);
                
                // attempt to validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $this->ip = $ip;
                    return;
                }
            }
        }
        $this->ip = $request->getClient()->getIP();
    }
    
    /**
     * Gets detected client IP address
     *
     * @return string
     */
    public function getIP(): string
    {
        return $this->ip;
    }
}
