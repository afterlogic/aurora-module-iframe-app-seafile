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
	CAbstractPopup.call(this)

	this.isSearchInProgress = ko.observable(false)
	this.searchInput = ko.observable('')

	this.searchResultCount = ko.observable(0)
	this.searchItems = ko.observable({
		'Storages': {},
		'Count': 0,
	})

	this.fCallback = null

	this.storageLabels = {
		'repo': TextUtils.i18n('%MODULENAME%/LABEL_MY_LIBRARIES'),
		'srepo': TextUtils.i18n('%MODULENAME%/LABEL_SHARED_WITH_ME_BY'),
		'grepo': TextUtils.i18n('%MODULENAME%/LABEL_SHARED_WITH_GROUPS_BY'),
	}
	
	this.searchResultRepoItems = ko.observable([])
	this.searchResultSRepoItems = ko.observable({})
	this.searchResultGRepoItems = ko.observable({})

	this.searchSubmitCommand = Utils.createCommand(this, function () {
		this.search(this.searchInput())
	})

	this.bindOpenLink = _.bind(this.openLink, this)
}

_.extendOwn(CSearchFilesPopup.prototype, CAbstractPopup.prototype)

CSearchFilesPopup.prototype.PopupTemplate = '%ModuleName%_SearchFilesPopup'

CSearchFilesPopup.prototype.onBind = function ()
{
}

CSearchFilesPopup.prototype.onOpen = function (fCallback)
{
	console.log(arguments)
	// this.searchInput('')
	// this.resetSearchResults()
	if (_.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
}

CSearchFilesPopup.prototype.openLink = function (item)
{
	console.log('openLink', this, item)
	this.fCallback(item.url)
	this.closePopup()
}

CSearchFilesPopup.prototype.resetSearchResults = function ()
{
	this.searchItems({
		'Storages': {},
		'Count': 0,
	})
	this.searchResultCount(0)
	this.searchResultRepoItems([])
	this.searchResultSRepoItems({})
	this.searchResultGRepoItems({})
}

CSearchFilesPopup.prototype.search = function (query)
{
	this.isSearchInProgress(true)
	this.resetSearchResults()
	Ajax.send(
		'%ModuleName%',
		'Search',
		{
			'Query': query
		},
		this.onSearchResponse,
		this
	)
}

CSearchFilesPopup.prototype.onSearchResponse = function (oResponse, oRequest)
{
	this.isSearchInProgress(false)
	if (oResponse.Result)
	{
		this.searchResultCount(oResponse.Result.Count)

		const storages = oResponse.Result.Storages
		oResponse.Result.Storages = []
		for (const storageName in storages) {
			if (storageName === 'repo') {
				oResponse.Result.Storages.push({
					'name': storageName,
					'label': this.storageLabels[storageName] ?? '',
					'items':  storages[storageName]
				});
			} else {
				const users = storages[storageName]
				const userList = []
				for (const userName in users) {
					userList.push({
						email: userName,
						items: users[userName]
					});
				}

				oResponse.Result.Storages.push({
					'name': storageName,
					'label': this.storageLabels[storageName] ?? '',
					'items': userList
				});
				
			}
		}

		this.searchItems(oResponse.Result)
	}
}

module.exports = new CSearchFilesPopup()
