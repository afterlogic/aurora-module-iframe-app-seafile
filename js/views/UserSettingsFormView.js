'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	Api = require('%PathToCoreWebclientModule%/js/Api.js'),
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	CAbstractSettingsFormView = ModulesManager.run('SettingsWebclient', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

const FAKE_PASS = '******';

/**
 * Inherits from CAbstractSettingsFormView that has methods for showing and hiding settings tab,
 * updating settings values on the server, checking if there was changins on the settings page.
 * 
 * @constructor
 */
function CUserSettingsFormView()
{
	CAbstractSettingsFormView.call(this, Settings.ServerModuleName);

	this.sTabName = Settings.TabName || TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB');
	this.email = ko.observable(Settings.Email);
	this.login = ko.observable(Settings.Login);
	this.password = ko.observable(Settings.HasPassword ? FAKE_PASS : '');
	this.name = ko.observable(Settings.Name);
	this.bAllowUserEditSettings = Settings.AllowUserEditSettings;

	this.visiblePassword = ko.observable('');
	this.focusVisiblePassword = ko.observable(false);
}

_.extendOwn(CUserSettingsFormView.prototype, CAbstractSettingsFormView.prototype);

/**
 * Name of template that will be bound to this JS-object.
 */
CUserSettingsFormView.prototype.ViewTemplate = '%ModuleName%_UserSettingsFormView';

/**
 * Returns array with all settings values wich is used for indicating if there were changes on the page.
 * 
 * @returns {Array} Array with all settings values;
 */
CUserSettingsFormView.prototype.getCurrentValues = function ()
{
	return [
		this.email(),
		this.login(),
		this.password(),
		this.name()
	];
};

/**
 * Reverts all settings values to global ones.
 */
CUserSettingsFormView.prototype.revertGlobalValues = function ()
{
	this.email(Settings.Email);
	this.login(Settings.Login);
	this.password(Settings.HasPassword ? FAKE_PASS : '');
	this.name(Settings.Name);
};

/**
 * Returns Object with parameters for passing to the server while settings updating.
 * 
 * @returns Object
 */
CUserSettingsFormView.prototype.getParametersForSave = function ()
{
	const parameters = {
		Email: this.email().trim(),
		Login: this.login().trim(),
		Name: this.name().trim(),
	};
	if (this.password() !== FAKE_PASS) {
		parameters.Password = this.password().trim();
	}
	return parameters;
};

CUserSettingsFormView.prototype.validateBeforeSave = function ()
{
	return this.bAllowUserEditSettings;
};

/**
 * Applies new settings values to global settings object.
 * 
 * @param {Object} oParameters Parameters with new values which were passed to the server.
 */
CUserSettingsFormView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.Email, oParameters.Login, oParameters.Name, true);
};

CUserSettingsFormView.prototype.hidePassword = function ()
{
	this.visiblePassword('');
	this.focusVisiblePassword(false);
};
/**
 * Applies new settings values to global settings object.
 * 
 * @param {Object} oParameters Parameters with new values which were passed to the server.
 */
CUserSettingsFormView.prototype.showPassword = function ()
{
	Ajax.send(
		Settings.ServerModuleName,
		'GetUserPassword',
		null,
		this.onGetPasswordResponse,
		this
	);
};

/**
 * Applies new settings values to global settings object.
 * 
 * @param {Object} oParameters Parameters with new values which were passed to the server.
 */
CUserSettingsFormView.prototype.onGetPasswordResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result) {
		Api.showErrorByCode(oResponse, TextUtils.i18n('COREWEBCLIENT/ERROR_UNKNOWN'));
	} else {
		this.visiblePassword(oResponse.Result);
		this.focusVisiblePassword(true);
	}
};

module.exports = new CUserSettingsFormView();
