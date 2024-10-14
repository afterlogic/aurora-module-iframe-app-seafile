import settings from './settings'
import store from 'src/store'

import AdminSettingsPerTenant from './components/AdminSettingsPerTenant'
import AdminSettingsPerUser from './components/AdminSettingsPerUser'

const moduleHash = 'iframe-app-seafile'
export default {
  moduleName: 'IframeAppSeafile',

  requiredModules: [],

  init (appData) {
    settings.init(appData)
  },

  getAdminSystemTabs () {
    return [
      {
        tabName: moduleHash,
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
          tabName: moduleHash,
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

  getAdminTenantTabs () {
    return [
      {
        tabName: moduleHash,
        tabTitle: 'IFRAMEAPPSEAFILE.HEADING_BROWSER_TAB',
        tabRouteChildren: [
          { path: 'id/:id/' + moduleHash, component: AdminSettingsPerTenant },
          { path: 'search/:search/id/:id/' + moduleHash, component: AdminSettingsPerTenant },
          { path: 'page/:page/id/:id/' + moduleHash, component: AdminSettingsPerTenant },
          { path: 'search/:search/page/:page/id/:id/' + moduleHash, component: AdminSettingsPerTenant },
        ],
      },
    ]
  },
}
