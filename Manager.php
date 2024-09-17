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

            $sEmail = $oUser->getExtendedProp($this->oModule->GetName() . '::Email');
            $sPassword = \Aurora\System\Utils::DecryptValue($oUser->getExtendedProp($this->oModule->GetName() . '::Password'));

            if ($sEmail && $sPassword) {
                $token = $this->authenticate($sEmail, $sPassword);

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

    public function getAccountInfo($sEmail)
    {
        $mResult = null;

        if ($sEmail) {
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
                        $mResult = json_decode($response->getBody()->getContents());
                    }
                } catch (\Exception $oException) {
                    \Aurora\System\Api::Log('Get user account info Exception', \Aurora\System\Enums\LogLevel::Error);
                    \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                    \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
                }
            }
        }

        return $mResult;
    }

    public function updateAccountInfo($sEmail, $LoginId, $Name, $Quota, $Password)
    {
        $bResult = false;

        $sAdminAuthToken = $this->getAdminToken();
        if ($sAdminAuthToken) {
            $sSeafileUrl = $this->oModule->oModuleSettings->Url;
            $client = new \GuzzleHttp\Client();

            $oAccountInfo = $this->getAccountInfo($sEmail);
            $sCurrentLoginId = $oAccountInfo->login_id ?? '';
            $sName = $oAccountInfo->name ?? '';
            $iQuota = isset($oAccountInfo->quota_total) ? (int) $oAccountInfo->quota_total / 1000 / 1000 : 0;
            $iQuota = $iQuota > 0 ? $iQuota : 0;

            $aParams = [];

            if ($LoginId !== null && $sCurrentLoginId !== $LoginId) {
                $aParams['login_id'] = $LoginId;
            }

            if ($Name !== null && $sName !== $Name) {
                $aParams['name'] = $Name;
            }

            if ($Quota !== null && $iQuota !== $Quota) {
                $aParams['quota_total'] = $Quota;
            }

            if ($Password !== null) {
                $aParams['password'] = $Password;
            }

            if (!empty($aParams)) {
                try {
                    $response = $client->request('PUT', $sSeafileUrl . '/api/v2.1/admin/users/' . $sEmail . '/', [
                        'json' => $aParams,
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
                    \Aurora\System\Api::Log('Update user account Exception: ' . $sEmail, \Aurora\System\Enums\LogLevel::Error);
                    \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                    \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
                }
            } else {
                $bResult = true;
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
