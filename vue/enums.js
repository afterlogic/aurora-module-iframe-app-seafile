import _ from 'lodash'

import typesUtils from 'src/utils/types'

class IframeAppSeafileEnums {
  constructor (appData) {
    const moduleData = typesUtils.pObject(appData.IframeAppSeafile)
    if (!_.isEmpty(moduleData)) {
      this.EAuthMode = typesUtils.pObject(moduleData.EAuthMode)
    }
  }
}

let enums = null

export default {
  init (appData) {
    enums = new IframeAppSeafileEnums(appData)
  },

  getAuthMode () {
    return enums.EAuthMode
  },
}
