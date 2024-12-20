'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),

	App = require('%PathToCoreWebclientModule%/js/App.js'),
	Api = require('%PathToCoreWebclientModule%/js/Api.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),

	CAbstractScreenView = require('%PathToCoreWebclientModule%/js/views/CAbstractScreenView.js'),

	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * View that is used as screen of the module. Inherits from CAbstractScreenView that has showing and hiding methods.
 * 
 * @constructor
 */
function CMainView()
{
	CAbstractScreenView.call(this, '%ModuleName%');

	/**
	 * Text for displaying in browser title.
	 */
	this.browserTitle = ko.observable(TextUtils.i18n('%MODULENAME%/HEADING_BROWSER_TAB'));
	this.sFrameUrl =  ko.observable();
	this.iframeDom = ko.observable(null);
	this.isIframeLoaded = ko.observable(false);

	App.broadcastEvent('%ModuleName%::ConstructView::after', {'Name': this.ViewConstructorName, 'View': this});
}

_.extendOwn(CMainView.prototype, CAbstractScreenView.prototype);

CMainView.prototype.ViewTemplate = '%ModuleName%_MainView';
CMainView.prototype.ViewConstructorName = 'CMainView';

CMainView.prototype.onGetLoginLinkResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result) {
		Api.showErrorByCode(oResponse, TextUtils.i18n('COREWEBCLIENT/ERROR_UNKNOWN'));
	} else {
		this.sFrameUrl(oResponse.Result);
	}
};
CMainView.prototype.onShow = function ()
{
	var
		Routing = require('%PathToCoreWebclientModule%/js/Routing.js'),
		sAppHash = Settings.TabName !== '' ?  TextUtils.getUrlFriendlyName(Settings.TabName) : Settings.HashModuleName
	;

	if (Settings.AllowUserEditSettings && !(Settings.Login !== '' && Settings.HasPassword))
	{
		Routing.setHash(['settings', sAppHash]);
		Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_EMPTY_LOGIN_RASSWORD', {'TABNAME': Settings.TabName}));
	}

	Ajax.send(
		Settings.ServerModuleName,
		'GetLoginLink',
		null,
		this.onGetLoginLinkResponse,
		this
	);
};

CMainView.prototype.checkIframeLoaded = function () {
    var 
		iframe = document.getElementById('seafileIframe'),
		self = this;

	iframe.addEventListener("load", function() {
		self.isIframeLoaded(true);
	});
}

CMainView.prototype.openSearchDialog = function ()
{
	const
		Popups = require('%PathToCoreWebclientModule%/js/Popups.js'),
		SearchFilesPopup = require('modules/%ModuleName%/js/popups/SearchFilesPopup.js')
	;
	Popups.showPopup(SearchFilesPopup, [_.bind(this.searchLinkCallback, this)]);
};

CMainView.prototype.searchLinkCallback = function (link)
{
	this.sFrameUrl(link);
};

module.exports = new CMainView();
