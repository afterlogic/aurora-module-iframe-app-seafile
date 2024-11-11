'use strict';

const
	_ = require('underscore'),
	ko = require('knockout'),
	Utils = require('%PathToCoreWebclientModule%/js/utils/Common.js'),
	CAbstractPopup = require('%PathToCoreWebclientModule%/js/popups/CAbstractPopup.js'),
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js')
;

/**
 * @constructor
 */
function CSearchFilesPopup()
{
	CAbstractPopup.call(this);

	this.isSearchInProgress = ko.observable(false);
	this.searchInput = ko.observable('');

	this.searchItems = ko.observable({
		storages: {},
		count: 0
	});

	this.searchSubmitCommand = Utils.createCommand(this, function () {
		this.search(this.searchInput());
	});

	this.storages = [
		{
			type: 'repo',
			name: TextUtils.i18n('%MODULENAME%/LABEL_MY_LIBRARIES')
		},
		{
			type: 'srepo',
			name: TextUtils.i18n('%MODULENAME%/LABEL_SHARED_WITH_ME_BY')
		},
		{
			type: 'grepo',
			name: TextUtils.i18n('%MODULENAME%/LABEL_SHARED_WITH_GROUPS_BY')
		}
	];
}

_.extendOwn(CSearchFilesPopup.prototype, CAbstractPopup.prototype);

CSearchFilesPopup.prototype.PopupTemplate = '%ModuleName%_SearchFilesPopup';

CSearchFilesPopup.prototype.onBind = function ()
{
};

CSearchFilesPopup.prototype.onOpen = function ()
{
	this.searchInput('');
	this.searchItems({
		storages: {},
		count: 0
	});
};

CSearchFilesPopup.prototype.search = function (query)
{
	this.isSearchInProgress(true);
	this.searchItems([]);
	Ajax.send('%ModuleName%',
		'Search',
		{
			'Query': query
		},
		this.onSearchResponse,
		this
	);
};

CSearchFilesPopup.prototype.onSearchResponse = function (oResponse, oRequest)
{
	this.isSearchInProgress(false);
	if (oResponse.Result)
	{
		for (var storage in oResponse.Result.storages) {
			if (storage !== 'repo') {
				var users = oResponse.Result.storages[storage];
				oResponse.Result.storages[storage] = [];
				for (var user in users) {
					oResponse.Result.storages[storage].push({
						user: user,
						items: users[user]
					});
				}
			}
		}

		this.searchItems(oResponse.Result);
	}
};

module.exports = new CSearchFilesPopup();
