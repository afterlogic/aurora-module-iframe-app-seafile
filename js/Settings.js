'use strict';
var
	_ = require('underscore'),
	
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: '%ModuleName%',
	HashModuleName: TextUtils.getUrlFriendlyName('%ModuleName%'), /*'iframe-app',*/
	
	TabName: TextUtils.i18n('%MODULENAME%/LABEL_TAB_NAME'),
	AllowUserEditSettings: false,
	Url: '',
	Host: '',
	Email: '',
	Login: '',
	HasPassword: false,
	Name: '',
	
	/**
	 * Initializes settings from AppData object sections.
	 * 
	 * @param {Object} oAppData Object contained modules settings.
	 */
	init: function (oAppData)
	{
		var oAppDataSection = oAppData[this.ServerModuleName];
		
		if (!_.isEmpty(oAppDataSection))
		{
			this.AllowUserEditSettings = Types.pBool(oAppDataSection.AllowUserEditSettings, this.AllowUserEditSettings);
			
			this.TabName = Types.pString(oAppDataSection.TabName, this.TabName);
			this.Url = Types.pString(oAppDataSection.Url, this.Url);
			this.Host = Types.pString(oAppDataSection.Url, this.Url);
			this.Email = Types.pString(oAppDataSection.Email, this.Email);
			this.Login = Types.pString(oAppDataSection.Login, this.Login);
			this.HasPassword = Types.pBool(oAppDataSection.HasPassword, this.HasPassword);
			this.Name = Types.pString(oAppDataSection.Name, this.Name);
		}
	},
	
	/**
	 * Updates module settings after editing.
	 * 
	 * @param {string} sEmail New value of setting 'Email'
	 * @param {string} sLogin New value of setting 'Login'
	 * @param {string} sName New value of setting 'Name'
	 * @param {boolean} bHasPassword Indicates if user has custom password
	 */
	update: function (sEmail, sLogin, sName, bHasPassword)
	{
		this.Email = sEmail;
		this.Login = sLogin;
		this.HasPassword = bHasPassword;
		this.Name = sName;
	},
};
