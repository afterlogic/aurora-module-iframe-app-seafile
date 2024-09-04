import _ from 'lodash'

import typesUtils from 'src/utils/types'

import enums from './enums'
// enums is not initialized yet, so we cannot get IframeAppAuthMode here

class IframeAppSeafileSettings {
  constructor(appData) {
    const IframeAppAuthMode = enums.getAuthMode()

    const moduleData = typesUtils.pObject(appData.IframeAppSeafile)
    if (!_.isEmpty(moduleData)) {
      this.tabName = typesUtils.pString(moduleData.TabName)
      this.authMode = typesUtils.pEnum(moduleData.AuthMode, IframeAppAuthMode)
      this.hasPassword = typesUtils.pBool(moduleData.HasPassword)
      this.login = typesUtils.pString(moduleData.Login)
      this.url = typesUtils.pString(moduleData.Url)
      this.adminLogin = typesUtils.pString(moduleData.AdminLogin)
      this.adminPassword = typesUtils.pString(moduleData.AdminPassword)
    }
  }

  saveIframeAppSettings({ authMode, tabName, url, adminLogin, adminPassword }) {
    this.authMode = authMode
    this.tabName = tabName
    this.url = url
    this.adminLogin = adminLogin
    this.adminPassword = adminPassword
  }
}

let settings = null

export default {
  init(appData) {
    enums.init(appData) // should be done before settings initialization
    settings = new IframeAppSeafileSettings(appData)
  },

  saveIframeAppSettings(data) {
    settings.saveIframeAppSettings(data)
  },

  getIframeAppSettings() {
    return {
      tabName: settings.tabName,
      authMode: settings.authMode,
      eIframeAppAuthMode: settings.eIframeAppAuthMode,
      url: settings.url,
      adminLogin: settings.adminLogin,
      adminPassword: settings.adminPassword,
    }
  },

  isAuthModeCredentialsSetByAdmin() {
    const AuthMode = enums.getAuthMode()
    return settings.authMode === AuthMode.CustomCredentialsSetByAdmin
  },
}
