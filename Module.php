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
 * @copyright Copyright (c) 2024, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractWebclientModule
{
    public $oManager = null;

    public function init()
    {
        $this->oManager = new Manager($this);

        $this->subscribeEvent('Core::CreateUser::after', [$this, 'onAfterCreateUser']);
        $this->subscribeEvent('Core::DeleteUser::after', [$this, 'onAfterDeleteUser']);
        $this->subscribeEvent('Core::Logout::after', [$this, 'onAfterLogout']);

        $this->subscribeEvent('Core::CreateGroup::after', array($this, 'onAfterCreateGroup'));
        $this->subscribeEvent('Core::DeleteGroup::before', array($this, 'onBeforeDeleteGroup'));
        $this->subscribeEvent('Core::AddUsersToGroup::after', array($this, 'onAfterAddUsersToGroup'));
        $this->subscribeEvent('Core::RemoveUsersFromGroup::after', array($this, 'onAfterRemoveUsersFromGroup'));

        // GetGroupUsers
        // AddUsersToGroup($GroupId, $UserIds)
        // RemoveUsersFromGroup($GroupId, $UserIds)
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
                true,
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
                    self::GetName() . '::Email' => $oAccount->email,
                    self::GetName() . '::Password' => \Aurora\System\Utils::EncryptValue($sPassword),
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

                if ($sEmail) {
                    $bResult = $this->oManager->deleteAccount($sEmail);

                    if ($bResult) {
                        $oUser->unsetExtendedProp(self::GetName() . '::Email');
                        $oUser->unsetExtendedProp(self::GetName() . '::Password');

                        \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
                    }
                }
            }
        }
    }

    public function onAfterCreateGroup($aArgs, &$mResult)
    {
        $iGroupId = isset($mResult) && (int) $mResult > 0 ? $mResult : 0;

        if ((int) $iGroupId > 0) {
            
            $oAuroraGroup = \Aurora\Modules\Core\Module::Decorator()->GetGroup($iGroupId);
            
            if ($oAuroraGroup) {
                $oSeafileGroup = $this->oManager->createGroup($oAuroraGroup->Name);

                if ($oSeafileGroup) {
                    $oAuroraGroup->setExtendedProp(self::GetName() . '::GroupId', $oSeafileGroup->id);
                    $oAuroraGroup->save();
                }
            }

        }
    }

    public function onBeforeDeleteGroup($aArgs, &$mResult)
    {
        $oAuroraGroup = \Aurora\Modules\Core\Module::Decorator()->GetGroup($aArgs['GroupId']);
            
        if ($oAuroraGroup) {
            $iGroupId = $oAuroraGroup->getExtendedProp(self::GetName() . '::GroupId');

            if  ($iGroupId) {
                $this->oManager->deleteGroup($iGroupId);
            }
        }
    }

    public function onAfterAddUsersToGroup($aArgs, &$mResult)
    {
        if ($mResult) {
            $iAuroraGroupId = (int) $aArgs['GroupId'] ?? 0;
            $aUserIds = $aArgs['UserIds'] ?? [];
    
            if ($iAuroraGroupId > 0 && is_array($aUserIds) && count($aUserIds) > 0) {
                
                $oAuroraGroup = \Aurora\Modules\Core\Module::Decorator()->GetGroup($iAuroraGroupId);
                
                if ($oAuroraGroup && $oAuroraGroup->getExtendedProp(self::GetName() . '::GroupId')) {
                    $iGroupId = $oAuroraGroup->getExtendedProp(self::GetName() . '::GroupId');

                    $aSeafileAccountEmails = $this->oManager->getAccountEmailsByUserIds($aUserIds); 
                    
                    $mResult = $this->oManager->addMembersToGroup($iGroupId, $aSeafileAccountEmails);
                }
            }
        }
    }

    public function onAfterRemoveUsersFromGroup($aArgs, &$mResult)
    {
        if ($mResult) {
            $iAuroraGroupId = (int) $aArgs['GroupId'] ?? 0;
            $aUserIds = $aArgs['UserIds'] ?? [];
    
            if ($iAuroraGroupId > 0 && is_array($aUserIds) && count($aUserIds) > 0) {
                
                $oAuroraGroup = \Aurora\Modules\Core\Module::Decorator()->GetGroup($iAuroraGroupId);
                
                if ($oAuroraGroup && $oAuroraGroup->getExtendedProp(self::GetName() . '::GroupId')) {
                    $iGroupId = $oAuroraGroup->getExtendedProp(self::GetName() . '::GroupId');

                    $aSeafileAccountEmails = $this->oManager->getAccountEmailsByUserIds($aUserIds); 
                    
                    $mResult = $this->oManager->removeMembersToGroup($iGroupId, $aSeafileAccountEmails);
                }
            }
        }
    }

    /**
     * Obtains module settings for authenticated user or superadmin.
     *
     * @return array|null
     */
    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
        $oSetting = null;
        $oUser = \Aurora\System\Api::getAuthenticatedUser();
        if ($oUser && ($oUser->isNormalOrTenant() && $this->isEnabledForEntity($oUser) || $oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)) {
            $oSetting = [
                'Url' => $this->oModuleSettings->Url,
                'TabName' => $this->oModuleSettings->TabName,
                'AllowUserEditSettings' => $this->oModuleSettings->AllowUserEditSettings,
            ];

            if ($oUser->isNormalOrTenant() && $this->isEnabledForEntity($oUser)) {
                $oSetting['Email'] = $oUser->getExtendedProp(self::GetName() . '::Email');

                $oAccountInfo = $this->oManager->getAccountInfo($oSetting['Email']);

                $sLoginId = $oAccountInfo->login_id ?? '';
                $sName = $oAccountInfo->name ?? '';

                $oSetting['Login'] = $sLoginId;
                $oSetting['HasPassword'] = (bool) $oUser->getExtendedProp(self::GetName() . '::Password');
                $oSetting['Name'] = $sName;
            }
        }

        if ($oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin) {
            $oSetting = is_array($oSetting) ? $oSetting : [];
            $oSetting['AdminLogin'] = $this->oModuleSettings->AdminLogin;
            $oSetting['HasAdminPassword'] = (bool) $this->oModuleSettings->AdminPassword;
        }

        return $oSetting;
    }

    /**
     * Updates module settings by a user.
     *
     * @param string|null $Email
     * @param string|null $Login
     * @param string $Password
     * @return bool
     */
    public function UpdateSettings($Email = null, $Password = null, $Login = null, $Name = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        $bResult = false;

        if ($this->oModuleSettings->AllowUserEditSettings) {
            \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
            $oUser = \Aurora\System\Api::getAuthenticatedUser();
            if ($oUser) {
                \Aurora\System\Api::skipCheckUserRole(true);
                $bModuleEnabled = $this->isEnabledForEntity($oUser);
                $bResult = $this->UpdatePerUserSettings($oUser->Id, $bModuleEnabled, $Email, $Password, $Login, $Name);
                \Aurora\System\Api::skipCheckUserRole(false);
            }
        }

        return $bResult;
    }

    /**
     * Updates module settings by a superadmin.
     *
     * @param string $TabName
     * @param string $Url
     * @param string $AdminLogin
     * @param string|null $AdminPassword
     * @param bool $AllowUserEditSettings
     * @return bool
     */
    public function UpdateAdminSettings($TabName = '', $Url = '', $AdminLogin = '', $AdminPassword = null, $AllowUserEditSettings = false)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
        $this->setConfig('TabName', (string) $TabName);
        $this->setConfig('Url', (string) $Url);
        $this->setConfig('AdminLogin', (string) $AdminLogin);
        $this->setConfig('AllowUserEditSettings', (bool) $AllowUserEditSettings);

        if ($AdminPassword !== null) {
            $this->setConfig('AdminPassword', \Aurora\System\Utils::EncryptValue((string) $AdminPassword));
        }

        return $this->saveModuleConfig();
    }

    /**
     * Obtains per user settings for superadmin.
     *
     * @param int $UserId
     * @return array
     */
    public function GetPerUserSettings($UserId)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);
        if ($oUser) {
            $sEmail = $oUser->getExtendedProp(self::GetName() . '::Email');

            $oAccountInfo = $this->oManager->getAccountInfo($sEmail);
            $sLoginId = $oAccountInfo->login_id ?? '';
            $sName = $oAccountInfo->name ?? '';
            $iQuota = isset($oAccountInfo->quota_total) ? (int) $oAccountInfo->quota_total / 1000 / 1000 : 0;
            $iQuota = $iQuota > 0 ? $iQuota : 0;

            return [
                'EnableModule' => $this->isEnabledForEntity($oUser),
                'EmailId' => $sEmail,
                'Login' => $sLoginId,
                'HasPassword' => (bool) $oUser->getExtendedProp(self::GetName() . '::Password'),
                'Quota' => $iQuota,
                'Name' => $sName,
            ];
        }

        return null;
    }

    /**
     * Updaters per user settings by superadmin.
     *
     * @param int $UserId
     * @param bool $EnableModule
     * @param string $Email
     * @param string $LoginId
     * @param string $Password
     * @param int|null $Quota
     * @return bool
     */
    public function UpdatePerUserSettings($UserId, $EnableModule, $Email = '', $Password = null, $LoginId = null, $Name = null, $Quota = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        $bResult = false;

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);
        if ($oUser) {
            $this->updateEnabledForEntity($oUser, $EnableModule);

            $bEmailChanged = false;
            $bPasswordChanged = false;
            $sCurrentEmail = $oUser->getExtendedProp(self::GetName() . '::Email');
            if ($sCurrentEmail !== $Email) {
                $oUser->setExtendedProp(self::GetName() . '::Email', $Email);
                $bEmailChanged = true;
            }

            $sCurrentPassword = \Aurora\System\Utils::DecryptValue($oUser->getExtendedProp(self::GetName() . '::Password'));
            if ($Password !== null && $sCurrentPassword !== $Password) {
                $oUser->setExtendedProp(self::GetName() . '::Password', \Aurora\System\Utils::EncryptValue($Password));
                $bPasswordChanged = true;
            } else {
                // reset password value to avoid unnecessary password updating
                $Password = null;
            }

            if ($bEmailChanged || $bPasswordChanged) {
                $bResult = \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            } else {
                $bResult = true;
            }

            $sEmail = $oUser->getExtendedProp(self::GetName() . '::Email');
            if (!empty($sEmail) && (is_numeric($Quota) || $LoginId !== null || $Name !== null || $bPasswordChanged)) {
                $bResult = $this->oManager->updateAccountInfo($sEmail, $LoginId, $Name, $Quota, $Password);
            }
        }

        return $bResult;
    }

    /**
     * Obtains login link.
     *
     * @return string
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
     *
     * @param int $UserId
     * @return string|null
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
     *
     * @param int $UserId
     * @return bool
     */
    public function CreateSeafileAccount($UserId)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        \Aurora\System\Api::CheckAccess($UserId);

        $mResult = false;

        $oUser = \Aurora\Modules\Core\Module::Decorator()->GetUserWithoutRoleCheck($UserId);

        if ($oUser && empty($oUser->getExtendedProp(self::GetName() . '::Email'))) {
            $sPassword = \Illuminate\Support\Str::random(10);
            $oAccount = $this->oManager->createAccount($oUser['PublicId'], $sPassword);

            if ($oAccount && isset($oAccount->email)) {
                $oUser->setExtendedProps([
                    self::GetName() . '::Email' => $oAccount->email,
                    self::GetName() . '::Password' => \Aurora\System\Utils::EncryptValue($sPassword),
                ]);
                $mResult = \Aurora\Modules\Core\Module::Decorator()->UpdateUserObject($oUser);
            }
        }

        return $mResult;
    }

    /**
     * The methods sets a cookie with token.
     *
     * @return bool
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
                [
                    'expires' => 0,  //\strtotime('+1 day'),
                    'path' => \Aurora\System\Api::getCookiePath(),
                    'domain' => '',
                    'secure' => \Aurora\System\Api::getCookieSecure(),
                    'httponly' => true,
                ]
            );
        }

        return !!$mResult;
    }

    /**
     * Performs a request to Seafile API.
     */
    public function GetSeafileResponse($Url, $Headers, $PostData = false)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        if (isset($_COOKIE['seafile_token'])) {
            $Headers['authorization'] = 'Bearer ' . $_COOKIE['seafile_token'];
        } else {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        $client = new \GuzzleHttp\Client();
        $res = null;
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
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }
        } else {
            try {
                $res = $client->get($Url, [
                    'headers' => $Headers,
                ]);
            } catch (\Exception $oException) {
                \Aurora\System\Api::Log($oException->getMessage(), \Aurora\System\Enums\LogLevel::Error);
                \Aurora\System\Api::LogException($oException, \Aurora\System\Enums\LogLevel::Error);
            }
        }
        if ($res && ($res->getStatusCode() === 200 || $res->getStatusCode() === 201)) {
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
                'headers' => $Headers,
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
                $newFileHash = \Aurora\System\Api::EncodeKeyValues([
                    'TempFile' => true,
                    'UserId' => $UserId,
                    'Name' => $fileName,
                    'TempName' => $tempName,
                ]);

                $actions = [
                    'view' => [
                        'url' => '?file-cache/' . $newFileHash . '/view',
                    ],
                    'download' => [
                        'url' => '?file-cache/' . $newFileHash,
                    ],
                ];

                $result[] = [
                    'Name' => $fileName,
                    'TempName' => $tempName,
                    'Size' => $size,
                    'Hash' => $fileHash,
                    'NewHash' => $newFileHash,
                    'MimeType' => \MailSo\Base\Utils::MimeContentType($fileName),
                    'Actions' => $actions,
                ];

                @fclose($fileResource);
            }
        }

        return $result;
    }
}
