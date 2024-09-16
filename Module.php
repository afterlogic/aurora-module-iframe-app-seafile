<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\IframeAppSeafile;

use Aurora\System\Api;
/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractWebclientModule
{
    public $oManager = null;

    public function init() {
        $this->oManager = new Manager($this);
        
        $this->subscribeEvent('Core::CreateUser::after', array($this, 'onAfterCreateUser'));
        $this->subscribeEvent('Core::DeleteUser::after', array($this, 'onAfterDeleteUser'));
        $this->subscribeEvent('Core::Logout::after', array($this, 'onAfterLogout'));
    }

    /**
     * @return Module
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     * @return Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    public function onAfterLogout($aArgs, &$mResult)
    {
        if ($mResult) {
            @\setcookie(
                'seafile_token',
                '',
                0, //\strtotime('+1 day'),
                \Aurora\System\Api::getCookiePath(),
                null,
                \Aurora\System\Api::getCookieSecure(),
                true
            );
        }
    }

    public function onAfterCreateUser($aArgs, &$mResult)
    {
        $iUserId = isset($mResult) && (int) $mResult > 0 ? $mResult : 0;

        if ((int) $iUserId > 0) {
            $sPassword = \Illuminate\Support\Str::random(10);
            
            $oAccount = $this->oManager->createAccount($aArgs['PublicId'], $sPassword);

            if ($oAccount && isset($oAccount->email)) {
                $oUser = \Aurora\System\Api::getUserById($iUserId);
                $oUser->setExtendedProps([
                    self::GetName() . '::Login' => $oAccount->login_id,
                    self::GetName() . '::Email' => $oAccount->email,
                    self::GetName() . '::Password' => \Aurora\System\Utils::EncryptValue($sPassword)
                ]);
                \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            }
        }
    }

    public function onAfterDeleteUser($aArgs, &$mResult)
    {
        if ($mResult) {
            $oUser = Api::getUserById($aArgs['UserId']);
            
            if ($oUser) {
                $sEmail = $oUser->getExtendedProp(self::GetName() . '::Email');

                if  ($sEmail) {
                    $this->oManager->deleteAccount($sEmail);
                }
            }
        }
    }

    /**
     * Obtains module settings for authenticated user.
     *
     * @return array
     */
    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
        $oSetting = null;
        $oUser = \Aurora\System\Api::getAuthenticatedUser();
        if ($oUser && ($oUser->isNormalOrTenant() && $this->isEnabledForEntity($oUser) || $oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)) {
            $oSetting = array(
                'EAuthMode' => (new Enums\AuthMode())->getMap(),
                'AuthMode' => $this->oModuleSettings->AuthMode,
                'Url' => $this->oModuleSettings->Url,
                'TabName' => $this->oModuleSettings->TabName,
                'AllowEditSettings' => $this->oModuleSettings->AllowEditSettings,
            );

            if ($oUser->isNormalOrTenant() && $this->isEnabledForEntity($oUser)) {
                $oSetting['Email'] = $oUser->getExtendedProp(self::GetName() . '::Email');
                $oSetting['Login'] = $oUser->getExtendedProp(self::GetName() . '::Login');
                $oSetting['HasPassword'] = (bool) $oUser->getExtendedProp(self::GetName() . '::Password');
            }
        }
        
        if ($oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin) {
            $oSetting = is_array($oSetting) ? $oSetting : array();
            $oSetting['AdminLogin'] = $this->oModuleSettings->AdminLogin;
            $oSetting['HasAdminPassword'] = (bool) $this->oModuleSettings->AdminPassword;
        }

        return $oSetting;
    }

    /**
     * Updates module settings by a user.
     *
     * @param string $Login
     * @param string $Password
     * @return bool
     */
    public function UpdateSettings($Email = null, $Login = null, $Password = null)
    {
        if ($this->oModuleSettings->AllowEditSettings) {
            \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
            $oUser = \Aurora\System\Api::getAuthenticatedUser();
            if ($oUser) {
                if ($Email !== null) {
                    $oUser->setExtendedProp(self::GetName() . '::Email', $Email);
                }
                if ($Login !== null) {
                    $oUser->setExtendedProp(self::GetName() . '::Login', $Login);
                }
                if ($Password !== null) {
                    $oUser->setExtendedProp(self::GetName() . '::Password', \Aurora\System\Utils::EncryptValue($Password));
                }
                return \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            }
        }

        return false;
    }

    /**
     * Updates module settings by a user.
     *
     * @param string $TabName
     * @param string $Url
     * @param string $AdminLogin
     * @param string $AdminPassword
     * @param int $AuthMode
     * @return bool
     */
    public function UpdateAdminSettings($TabName = null, $Url = null, $AdminLogin = null, $AdminPassword = null, $AuthMode = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
        $this->setConfig('TabName', $TabName);
        $this->setConfig('Url', $Url);
        $this->setConfig('AuthMode', $AuthMode);
        $this->setConfig('AdminLogin', $AdminLogin);

        if ($AdminPassword !== null) {
            $this->setConfig('AdminPassword', \Aurora\System\Utils::EncryptValue($AdminPassword));
        }

        return $this->saveModuleConfig();
    }

    /**
     * Obtains per user settings for superadmin.
     * @param int $UserId
     * @return array
     */
    public function GetPerUserSettings($UserId)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);
        if ($oUser) {
            $sLogin = $oUser->getExtendedProp(self::GetName() . '::Login');
            $sEmail = $oUser->getExtendedProp(self::GetName() . '::Email');
            return array(
                'EnableModule' => $this->isEnabledForEntity($oUser),
                'EmailId' => $sEmail,
                'Login' => $sLogin,
                'HasPassword' => (bool) $oUser->getExtendedProp(self::GetName() . '::Password'),
                'Quota' => (int) $this->oManager->getQuota($sEmail),
            );
        }

        return null;
    }

    /**
     * Updaters per user settings for superadmin.
     *
     * @param int $UserId
     * @param bool $EnableModule
     * @return bool
     */
    public function UpdatePerUserSettings($UserId, $EnableModule, $EmailId = '', $Login = '', $Password = '', $Quota = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        $bResult = false;

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);
        if ($oUser) {
            $this->updateEnabledForEntity($oUser, $EnableModule);

            $bNeedUpdateUser = false;
            $sCurrentEmail = $oUser->getExtendedProp(self::GetName() . '::Email');
            if ($sCurrentEmail !== $Login) {
                $oUser->setExtendedProp(self::GetName() . '::Email', $EmailId);
                $bNeedUpdateUser = true;
            }

            $sCurrentLogin = $oUser->getExtendedProp(self::GetName() . '::Login');
            if ($sCurrentLogin !== $Login) {
                // TODO save new login (email) in Seafile
                // https://seafile-api.readme.io/reference/put_api-v2-1-admin-update-user-ccnet-email
                $oUser->setExtendedProp(self::GetName() . '::Login', $Login);
                $bNeedUpdateUser = true;
            }

            $sCurrentPassword = \Aurora\System\Utils::DecryptValue($oUser->getExtendedProp(self::GetName() . '::Password'));
            if ($sCurrentPassword !== $Password) {
                $oUser->setExtendedProp(self::GetName() . '::Password', \Aurora\System\Utils::EncryptValue($Password));
                $bNeedUpdateUser = true;
            }

            if ($bNeedUpdateUser) {
                $bResult = \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            } else {
                $bResult = true;
            }

            $sEmail = $oUser->getExtendedProp(self::GetName() . '::Email');
            if (is_numeric($Quota) && !empty($sEmail)) {
                $bResult = $this->oManager->setQuota($sEmail, (int) $Quota);
            }
        }

        return $bResult;
    }

    /**
     * 
     */
    public function GetLoginLink()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        $sLink = '';
        
        $oUser = \Aurora\System\Api::getAuthenticatedUser();
        
        if ($oUser) {           
            $sToken = $this->oManager->getUserToken($oUser);

            if ($sToken) {
                $sLoginToken = $this->oManager->getLoginLink($sToken);

                if ($sLoginToken) {
                    $sLink = $sLoginToken;
                }
            }
        }

        return $sLink;
    }

    /**
     * Obtains user password.
     * @param int $UserId
     * @return array
     */
    public function GetUserPassword($UserId)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        \Aurora\System\Api::CheckAccess($UserId);

        $mResult = false;

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);
        if ($oUser) {
            $mResult = \Aurora\System\Utils::DecryptValue($oUser->getExtendedProp(self::GetName() . '::Password'));
        }

        return $mResult;
    }

    /**
     * Obtains user password.
     * @param int $UserId
     * @return array
     */
    public function CreateSeafileAccount($UserId)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        \Aurora\System\Api::CheckAccess($UserId);

        $mResult = false;

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);

        if ($oUser && empty($oUser->getExtendedProp(self::GetName() . '::Email')) && empty($oUser->getExtendedProp(self::GetName() . '::Login'))) {
            $sPassword = \Illuminate\Support\Str::random(10);
            $oAccount = $this->oManager->createAccount($oUser['PublicId'], $sPassword);

            if ($oAccount && isset($oAccount->email)) {
                $oUser->setExtendedProps([
                    self::GetName() . '::Login' => $oAccount->login_id,
                    self::GetName() . '::Email' => $oAccount->email,
                    self::GetName() . '::Password' => \Aurora\System\Utils::EncryptValue($sPassword)
                ]);
                $mResult = \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            }
        }

        return $mResult;
    }

    /**
     * Obtains user password for superadmin.
     * @return string|null
     */
    public function GetUserToken()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        $mResult = false;

        $oUser = \Aurora\System\Api::getAuthenticatedUser();
        if ($oUser) {
            $mResult = $this->oManager->getUserToken($oUser);

            @\setcookie(
                'seafile_token',
                (string) $mResult,
                0, //\strtotime('+1 day'),
                \Aurora\System\Api::getCookiePath(),
                null,
                \Aurora\System\Api::getCookieSecure(),
                true
            );
        }

        return !!$mResult;
    }

    public function GetSeafileResponse($Url, $Headers, $PostData = false)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        if (isset($_COOKIE['seafile_token'])) {
            $Headers['authorization'] = 'Bearer ' . $_COOKIE['seafile_token'];
        } else {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        $client = new \GuzzleHttp\Client();

        if (is_array($PostData)) {
            $multipart = [];
            foreach ($PostData as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }
            try {
                $res = $client->post($Url, [
                    'headers' => $Headers,
                    'multipart' => $multipart,
                ]);
            } catch (\Exception $e) {
                $response = $e->getResponse();
                return $response ? $response->getBody()->getContents() : '{"error_msg": "' . $e->getMessage() . '"}';
            }
        } else {
            try {
                $res = $client->get($Url, [
                    'headers' => $Headers,
                ]);
            } catch (\Exception $e) {
                $response = $e->getResponse();
                return $response ? $response->getBody()->getContents() : '{"error_msg": "' . $e->getMessage() . '"}';
            }
        }
        if ($res->getStatusCode() === 200 || $res->getStatusCode() === 201) {
            $resource = $res->getBody();
            return $resource->read($resource->getSize());
        }
        return '';
    }

    public function SaveAttachmentsToSeafile($UserId, $AccountID, $Attachments, $UploadLink, $Headers, $ParentDir = '/')
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        if (isset($_COOKIE['seafile_token'])) {
            $Headers['authorization'] = 'Bearer ' . $_COOKIE['seafile_token'];
        } else {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        $mailModuleDecorator = \Aurora\Modules\Mail\Module::Decorator();
        if (!$mailModuleDecorator) {
            return false;
        }

        $tempFiles = $mailModuleDecorator->SaveAttachmentsAsTempFiles($AccountID, $Attachments);
        if (!is_array($tempFiles)) {
            return false;
        }

        $userUUID = \Aurora\System\Api::getUserUUIDById($UserId);
        foreach ($tempFiles as $tempName => $encodedData) {
            $data = \Aurora\System\Api::DecodeKeyValues($encodedData);
            if (!is_array($data) || !isset($data['FileName'])) {
                continue;
            }

            $fileName = (string) $data['FileName'];
            $filecacheManager = new \Aurora\System\Managers\Filecache();
            $resource = $filecacheManager->getFile($userUUID, $tempName);
            if (!$resource) {
                continue;
            }

            $multipart[] = [
                'headers' => ['Content-Type' => 'application/octet-stream'],
                'name' => 'file',
                'contents' => $resource,
                'filename' => $fileName,
            ];
        }
        $multipart[] = [
            'name' => 'parent_dir',
            'contents' => $ParentDir,
        ];
        $client = new \GuzzleHttp\Client();
        $res = $client->post($UploadLink, [
            'headers' => $Headers,
            'multipart' => $multipart,
        ]);

        return $res->getStatusCode() === 200;
    }



    public function SaveSeafilesAsTempfiles($UserId, $Files, $Headers)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        if (!is_array($Files) || 0 === count($Files)) {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        if (isset($_COOKIE['seafile_token'])) {
            $Headers['authorization'] = 'Bearer ' . $_COOKIE['seafile_token'];
        } else {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        $client = new \GuzzleHttp\Client();

        $userUUID = \Aurora\System\Api::getUserUUIDById($UserId);
        $result = [];
        foreach ($Files as $file) {
            $fileName = $file['Name'];
            $fileHash = $file['Hash'];

            $downloadLink = $this->oManager->getDownloadLink($file['Link'], $Headers);
            if (empty($downloadLink)) {
                continue;
            }

            $res = $client->get($downloadLink, [
                'headers' => $Headers
            ]);
            $fileResource = null;
            $size = 0;
            if ($res->getStatusCode() === 200) {
                $resource = $res->getBody();
                $size = $resource->getSize();
                $fileResource = \GuzzleHttp\Psr7\StreamWrapper::getResource($resource);
            }

            $tempName = md5($downloadLink . microtime(true) . rand(1000, 9999));

            $filecacheManager = new \Aurora\System\Managers\Filecache();
            if (is_resource($fileResource) && $filecacheManager->putFile($userUUID, $tempName, $fileResource)) {
                $newFileHash = \Aurora\System\Api::EncodeKeyValues(array(
                    'TempFile' => true,
                    'UserId' => $UserId,
                    'Name' => $fileName,
                    'TempName' => $tempName
                ));

                $actions = [
                    'view' => [
                        'url' => '?file-cache/' . $newFileHash . '/view'
                    ],
                    'download' => [
                        'url' => '?file-cache/' . $newFileHash
                    ],
                ];

                $result[] = [
                    'Name' => $fileName,
                    'TempName' => $tempName,
                    'Size' => $size,
                    'Hash' => $fileHash,
                    'NewHash' => $newFileHash,
                    'MimeType' => \MailSo\Base\Utils::MimeContentType($fileName),
                    'Actions' => $actions
                ];

                @fclose($fileResource);
            }
        }

        return $result;
    }
}
