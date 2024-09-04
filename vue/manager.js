import settings from './settings'
import store from 'src/store'

import AdminSettingsPerUser from './components/AdminSettingsPerUser'

export default {
  moduleName: 'IframeAppSeafile',

  requiredModules: [],

  init (appData) {
    settings.init(appData)
  },

  getAdminSystemTabs () {
    return [
      {
        tabName: 'iframe-app-seafile',
        tabTitle: 'IFRAMEAPPSEAFILE.LABEL_SETTINGS_TAB',
        tabRouteChildren: [
          { path: 'iframe-app-seafile', component: () => import('./components/AdminSettings') },
        ],
      },
    ]
  },

  getAdminUserTabs () {
    const isUserSuperAdmin = store.getters['user/isUserSuperAdmin']
    if (isUserSuperAdmin) {
      return [
        {
          tabName: 'iframe-app-seafile',
          tabTitle: 'IFRAMEAPPSEAFILE.LABEL_SETTINGS_TAB',
          tabRouteChildren: [
            { path: 'id/:id/iframe-app-seafile', component: AdminSettingsPerUser },
            { path: 'search/:search/id/:id/iframe-app-seafile', component: AdminSettingsPerUser },
            { path: 'page/:page/id/:id/iframe-app-seafile', component: AdminSettingsPerUser },
            { path: 'search/:search/page/:page/id/:id/iframe-app-seafile', component: AdminSettingsPerUser },
          ],
        },
      ]
    }
    return []
  },
}
