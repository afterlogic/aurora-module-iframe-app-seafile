<?php
/**
 * This code is licensed under Afterlogic Software License.
 * For full statements of the license see LICENSE file.
 */

namespace Aurora\Modules\IframeAppSeafile;

// use Aurora\System\Api;
// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\ConnectException;

/**
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2024, Afterlogic Corp.
 *
 * @package IframeAppSeafile
 * @subpackage Managers
 *
 * @property Module $oModule
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
    /**
     * @var string
     */
    private $sAdminAuthToken;

    /**
     * @var string
     */
    private $sUserAuthToken;

    public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
    {
        parent::__construct($oModule);
    }

    public function getAdminToken($bForce = false)
    {
        if (!$this->sAdminAuthToken || $bForce) {

            $sAdminLogin = $this->oModule->oModuleSettings->AdminLogin;
            $sAdminPassword = '';

            $sAdminPasswordEncrypted = $this->oModule->oModuleSettings->AdminPassword;

            // encrypt password and save it in case it's not encrypted yet
            if ($sAdminPasswordEncrypted) {
                $sAdminPassword = \Aurora\System\Utils::DecryptValue($sAdminPasswordEncrypted);

                if ($sAdminPassword === false) {
                    $this->oModule->setConfig('AdminPassword', \Aurora\System\Utils::EncryptValue($sAdminPasswordEncrypted));
                    $this->oModule->saveModuleConfig();
                    $sAdminPassword = $sAdminPasswordEncrypted;
                }
            }

            if ($sAdminLogin && $sAdminPassword) {
                $token = $this->authenticate($sAdminLogin, $sAdminPassword);

                if ($token) {
                    $this->sAdminAuthToken = $token;
                }
            }
        }

        return $this->sAdminAuthToken;
    }

    public function getUserToken($oUser, $bForce = false)
    {
        if (!$this->sUserAuthToken || $bForce) {

            $sLogin = $oUser->getExtendedProp($this->oModule->GetName() . '::Login');
            $sPassword = \Aurora\System\Utils::DecryptValue($oUser->getExtendedProp($this->oModule->GetName() . '::Password'));

            if ($sLogin && $sPassword) {
                $token = $this->authenticate($sLogin, $sPassword);

                if ($token) {
                    $this->sUserAuthToken = $token;
                }
            }
        }

        return $this->sUserAuthToken;
    }

    public function authenticate($sLogin, $sPassword)
    {
        $mResult = false;
        $sSeafileUrl = $this->oModule->oModuleSettings->Url;

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', $sSeafileUrl . '/api2/auth-token/', [
                'json' => [
                    'username' => $sLogin,
                    'password' => $sPassword,
                ],
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $oResponseBody = json_decode($response->getBody()->getContents());
                if (isset($oResponseBody->token)) {
                    $mResult = $oResponseBody->token;
                }
            }
        } catch (\Exception $oException) {
            \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
            \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
        }

        return $mResult;
    }

    public function getLoginLink($sToken)
    {
        $mResult = false;
        $sSeafileUrl = $this->oModule->oModuleSettings->Url;
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', $sSeafileUrl . '/api2/client-login/', [
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => 'Bearer ' . $sToken,
                ],
            ]);

            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $oResponseBody = json_decode($response->getBody()->getContents());
                if (isset($oResponseBody->token)) {
                    $mResult = $sSeafileUrl . '/client-login/?token=' . $oResponseBody->token;
                }
            }
        } catch (\Exception $oException) {
            \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
            \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
        }

        return $mResult;
    }

    public function createAccount($sLogin, $sPassword)
    {
        $mResult = false;

        $sAdminAuthToken = $this->getAdminToken();
        if ($sAdminAuthToken) {
            $sSeafileUrl = $this->oModule->oModuleSettings->Url;
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('POST', $sSeafileUrl . '/api/v2.1/admin/users/', [
                    'json' => [
                        'email' => $sLogin,
                        'login_id' => $sLogin,
                        'password' => $sPassword,
                    ],
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => 'Bearer ' . $sAdminAuthToken,
                        'content-type' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $mResult = json_decode($response->getBody()->getContents());
                }
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log('Create account Exception', \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }


        }

        return $mResult;
    }

    public function deleteAccount($sEmail)
    {
        $bResult = false;

        $sAdminAuthToken = $this->getAdminToken();
        if ($sAdminAuthToken) {
            $sSeafileUrl = $this->oModule->oModuleSettings->Url;
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('DELETE', $sSeafileUrl . '/api/v2.1/admin/users/' . $sEmail . '/', [
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => 'Bearer ' . $sAdminAuthToken,
                    ],
                ]);

                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $bResult = $response->getBody();
                }
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log('Delete account Exception', \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }
        }

        return $bResult;
    }

    public function getQuota($sEmail)
    {
        $mResult = false;

        $sAdminAuthToken = $this->getAdminToken();
        if ($sAdminAuthToken) {
            $sSeafileUrl = $this->oModule->oModuleSettings->Url;
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('GET', $sSeafileUrl . '/api/v2.1/admin/users/' . $sEmail . '/', [
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => 'Bearer ' . $sAdminAuthToken,
                        'content-type' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $oResponseBody = json_decode($response->getBody()->getContents());
                    if (isset($oResponseBody->quota_total)) {
                        $mResult = $oResponseBody->quota_total / 1000 / 1000;
                    }
                }
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log('Get user account info Exception', \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }
        }

        return $mResult;
    }

    public function setQuota($sLogin, int $iQuota)
    {
        $bResult = false;

        $sAdminAuthToken = $this->getAdminToken();
        if ($sAdminAuthToken) {
            $sSeafileUrl = $this->oModule->oModuleSettings->Url;
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('PUT', $sSeafileUrl . '/api/v2.1/admin/users/' . $sLogin . '/', [
                    'json' => [
                        'quota_total' => $iQuota,
                    ],
                    'headers' => [
                        'accept' => 'application/json',
                        'authorization' => 'Bearer ' . $sAdminAuthToken,
                        'content-type' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $bResult = true;
                }
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log('Update user account Exception', \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }
        }

        return $bResult;
    }

    public function getDownloadLink($link, $headers)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($link, [
            'headers' => $headers,
        ]);
        if ($response->getStatusCode() === 200) {
            $resource = $response->getBody();
            return trim($resource->read($resource->getSize()), '"');
        }
        return '';
    }
}
