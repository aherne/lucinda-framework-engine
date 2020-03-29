<?php
namespace Lucinda\Framework;

/**
 * Detects client IP based on contents of STDOUT Request object
 */
class IPDetector
{
    private $ip;
    
    /**
     * Kick starts ip detection process
     *
     * @param \Lucinda\STDOUT\Request $request
     */
    public function __construct(\Lucinda\STDOUT\Request $request)
    {
        $this->setIP($request);
    }
    
    /**
     * Performs IP detection and saves result.
     *
     * @param \Lucinda\STDOUT\Request $request
     */
    private function setIP(\Lucinda\STDOUT\Request $request)
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
    public function getIP()
    {
        return $this->ip;
    }
}