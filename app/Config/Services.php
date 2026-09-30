<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Email\Email;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Email as EmailConfig;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * The Email class provides email delivery capabilities.
     *
     * In test execution mode (authorized strictly via an ephemeral server-side
     * secret token file in WRITEPATH and matching request secret), a safe mock
     * transport is returned to prevent live external SMTP relay during automated
     * regression testing.
     *
     * In production (ENVIRONMENT === 'production') or normal client HTTP usage,
     * the mock transport can NEVER be activated by external headers alone;
     * standard Email with Brevo SMTP is always returned.
     *
     * @param EmailConfig|array|null $config
     */
    public static function email($config = null, bool $getShared = true)
    {
        // 1. Strictly forbid mock transport in production environment
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            if ($getShared) {
                return static::getSharedInstance('email', $config);
            }
            if (empty($config) || (! is_array($config) && ! $config instanceof EmailConfig)) {
                $config = config(EmailConfig::class);
            }
            return new Email($config);
        }

        // 2. Determine whether safe test mock is legitimately authorized.
        // Requires a server-side flag file containing a dynamic secret token
        // created by a local test runner with filesystem access.
        $isMockAuthorized = false;
        $flagFile = defined('WRITEPATH') ? rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . '.test_email_mock' : null;

        if ($flagFile && is_file($flagFile)) {
            $flagMtime = @filemtime($flagFile);
            // Flag file must have been generated recently (< 300 seconds TTL)
            if ($flagMtime && (time() - $flagMtime) < 300) {
                $expectedSecret = trim((string) @file_get_contents($flagFile));
                if ($expectedSecret !== '') {
                    try {
                        $request = service('request');
                        if ($request && method_exists($request, 'getHeaderLine')) {
                            $providedSecret = trim($request->getHeaderLine('X-MeetSpace-Test-Secret'));
                            if ($providedSecret !== '' && hash_equals($expectedSecret, $providedSecret)) {
                                $isMockAuthorized = true;
                            }
                        }
                    } catch (\Throwable $e) {
                        $isMockAuthorized = false;
                    }
                }
            }
        }

        if ($isMockAuthorized) {
            if (empty($config) || (! is_array($config) && ! $config instanceof EmailConfig)) {
                $config = config(EmailConfig::class);
            }

            return new class($config) extends MockEmail {
                public function send($autoClear = true)
                {
                    $result = parent::send($autoClear);

                    // Record delivery attempt metadata for automated test assertions
                    $deliveryRecord = [
                        'to'      => $this->recipients,
                        'subject' => $this->archive['subject'] ?? '',
                        'body'    => $this->archive['body'] ?? ($this->body ?? ''),
                        'time'    => date('Y-m-d H:i:s'),
                    ];

                    if (defined('WRITEPATH')) {
                        @file_put_contents(WRITEPATH . 'test_email_delivery.json', json_encode($deliveryRecord));
                    }

                    return $result;
                }
            };
        }

        if ($getShared) {
            return static::getSharedInstance('email', $config);
        }

        if (empty($config) || (! is_array($config) && ! $config instanceof EmailConfig)) {
            $config = config(EmailConfig::class);
        }

        return new Email($config);
    }
}
