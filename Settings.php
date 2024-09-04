<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\IframeAppSeafile;

use Aurora\System\SettingsProperty;
use Aurora\Modules\IframeAppSeafile\Enums;

/**
 * @property bool $Disabled
 * @property string $TabName
 * @property int $AuthMode
 * @property string $Url
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "Setting to true disables the module",
            ),
            "TabName" => new SettingsProperty(
                "",
                "string",
                null,
                "Denotes app name used in the interface for the integrated app",
            ),
            "AuthMode" => new SettingsProperty(
                Enums\AuthMode::NoAuthentication,
                "spec",
                Enums\AuthMode::class,
                "Defines the mode of sending authentication data into the integrated app",
            ),
            "Url" => new SettingsProperty(
                "",
                "string",
                null,
                "URL of the integrated app",
            ),
            "AdminLogin" => new SettingsProperty(
                "",
                "string",
                null,
                "",
            ),
            "AdminPassword" => new SettingsProperty(
                "",
                "string",
                null,
                "",
            ),
        ];
    }
}
