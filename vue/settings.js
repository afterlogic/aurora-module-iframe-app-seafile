import _ from 'lodash'
import typesUtils from 'src/utils/types'

class IframeAppSeafileSettings {
  constructor(appData) {
    const moduleData = typesUtils.pObject(appData.IframeAppSeafile)

    if (!_.isEmpty(moduleData)) {
      this.tabName = typesUtils.pString(moduleData.TabName)
      // this.hasPassword = typesUtils.pBool(moduleData.HasPassword)
      // this.login = typesUtils.pString(moduleData.Login)
      this.url = typesUtils.pString(moduleData.Url)
      this.adminLogin = typesUtils.pString(moduleData.AdminLogin)
      this.adminPassword = typesUtils.pString(moduleData.AdminPassword)
      this.allowUserEditSettings = typesUtils.pBool(moduleData.AllowUserEditSettings)
    }
  }

  saveSettings({tabName, url, adminLogin, adminPassword, allowUserEditSettings }) {
    this.tabName = tabName
    this.url = url
    this.adminLogin = adminLogin
    this.adminPassword = adminPassword
    this.allowUserEditSettings = allowUserEditSettings
  }
}

let settings = null

export default {
  init(appData) {
    settings = new IframeAppSeafileSettings(appData)
  },

  saveSettings(data) {
    settings.saveSettings(data)
  },

  getSettings() {
    return {
      tabName: settings.tabName,
      url: settings.url,
      adminLogin: settings.adminLogin,
      adminPassword: settings.adminPassword,
      allowUserEditSettings: settings.allowUserEditSettings,
    }
  },
}
