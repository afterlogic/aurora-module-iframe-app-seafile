'use strict';

module.exports = function (oAppData) {
	const
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Api = require('%PathToCoreWebclientModule%/js/Api.js'),
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;
	
	let HeaderItemView = null
	
	Settings.init(oAppData)
	
	require('modules/%ModuleName%/js/enums.js')
	
	const sAppHash = Settings.TabName ? TextUtils.getUrlFriendlyName(Settings.TabName) : Settings.HashModuleName
	
	if (App.isUserNormalOrTenant() && Settings.Url && Settings.Login) {
		const onGetUserTokenResponse = function (oResponse, oRequest) {
			if (!oResponse.Result) {
				Api.showErrorByCode(oResponse, TextUtils.i18n('COREWEBCLIENT/ERROR_UNKNOWN'))
			}
		}

		// this requiest sets a seafile_token cookie
		if (App.isUserNormalOrTenant()) {
			Ajax.send(
				Settings.ServerModuleName,
				'GetUserToken',
				null,
				onGetUserTokenResponse,
				this
			)
		}

		return {
			/**
			 * Registers settings tab before application start.
			 * 
			 * @param {Object} ModulesManager
			 */
			start: function (ModulesManager) {

				if (Settings.AuthMode === Enums.IframeAppSeafileAuthMode.CustomCredentialsSetByUser) {
					ModulesManager.run('SettingsWebclient', 'registerSettingsTab', [
						function () { return require('modules/%ModuleName%/js/views/UserSettingsFormView.js'); },
						sAppHash,
						Settings.TabName || TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')
					])
				}

				ModulesManager.run('MailWebclient', 'registerComposeUploadAttachmentsController',
					[require('modules/%ModuleName%/js/views/UploadButtonOnComposeView.js')]
				)

				App.subscribeEvent('MailWebclient::AddAllAttachmentsDownloadMethod', function (fAddAllAttachmentsDownloadMethod) {
					fAddAllAttachmentsDownloadMethod({
						'Text': TextUtils.i18n('%MODULENAME%/ACTION_SAVE_ATTACHMENTS_TO_SEAFILE'),
						'Handler': function (accountId, hashes) {
							const
								Popups = require('%PathToCoreWebclientModule%/js/Popups.js'),
								SeafileApi = require('modules/%ModuleName%/js/utils/SeafileApi.js'),
								SelectFilesPopup = require('modules/%ModuleName%/js/popups/SelectFilesPopup.js'),
								popupParams = {
									selectFilesMode: false,
									callback: (repoId, dirName) => {
										SeafileApi.saveToSeafile({ accountId, hashes, repoId, dirName })
									}
								}
							;
							Popups.showPopup(SelectFilesPopup, [popupParams])
						}
					})
				})
			},

			/**
			 * Returns list of functions that are return module screens.
			 * 
			 * @returns {Object}
			 */
			getScreens: function () {
				const oScreens = {}
				
				if (Settings.AuthMode !== Enums.IframeAppSeafileAuthMode.CustomCredentialsSetByAdmin || (Settings.Login !== '' && Settings.HasPassword)) {
					oScreens[sAppHash] = function () {
						return require('modules/%ModuleName%/js/views/MainView.js');
					}
				}
				
				return oScreens
			},

			/**
			 * Returns object of header item view of the module.
			 * 
			 * @returns {Object}
			 */
			getHeaderItem: function () {
				const 
					CHeaderItemView = require('%PathToCoreWebclientModule%/js/views/CHeaderItemView.js'),
					oHeaderEntry = {}
				;

				if (Settings.AuthMode !== Enums.IframeAppSeafileAuthMode.CustomCredentialsSetByAdmin || (Settings.Login !== '' && Settings.HasPassword)) {
					if (HeaderItemView === null) {
						HeaderItemView = new CHeaderItemView(Settings.TabName || TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB'))
					}
					oHeaderEntry['item'] = HeaderItemView
					oHeaderEntry['name'] = sAppHash
				}
				
				return oHeaderEntry
			}
		}
	}
	
	return null
}
