<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg">
      <div class="row q-mb-md">
        <div class="col text-h5" v-t="'IFRAMEAPPSEAFILE.HEADING_BROWSER_TAB'"></div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row q-mb-md">
            <div class="col-2 q-my-sm" v-t="'IFRAMEAPPSEAFILE.LABEL_TAB_NAME'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="tabName" @keyup.enter="save" />
            </div>
          </div>
          <div class="row q-mb-sm">
            <div class="col-2 q-my-sm" v-t="'IFRAMEAPPSEAFILE.LABEL_IFRAME_URL'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="url" ref="url" @keyup.enter="save" />
            </div>
          </div>
          <div class="row q-mb-sm">
            <div class="col-2 q-my-sm" v-t="'IFRAMEAPPSEAFILE.LABEL_ADMIN_LOGIN'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="adminLogin" ref="admin-login" @keyup.enter="save" />
            </div>
          </div>
          <div class="row q-mb-sm">
            <div class="col-2 q-my-sm" v-t="'IFRAMEAPPSEAFILE.LABEL_ADMIN_PASSWORD'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="adminPassword" ref="admin-password" @keyup.enter="save" />
            </div>
          </div>
          <div class="row">
            <div class="col-2"></div>
            <div class="col-5">
              <q-checkbox dense v-model="allowUserEditSettings">
                <q-item-label v-t="'IFRAMEAPPSEAFILE.LABEL_ALLOW_USER_EDIT_SETTIMGS'" />
              </q-checkbox>
            </div>
          </div>
        </q-card-section>
      </q-card>
      <div class="q-pt-md text-right">
        <q-btn
          unelevated
          no-caps
          dense
          class="q-px-sm"
          :ripple="false"
          color="primary"
          @click="save"
          :label="$t('COREWEBCLIENT.ACTION_SAVE')"
        >
        </q-btn>
      </div>
    </div>
    <q-inner-loading style="justify-content: flex-start" :showing="saving">
      <q-linear-progress query />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import webApi from 'src/utils/web-api'

import settings from '../settings'
import { isValidHttpURL } from '../utils/validation'

export default {
  name: 'AdminSettings',

  data() {
    return {
      saving: false,
      tabName: '',
      url: '',
      adminLogin: '',
      adminPassword: '',
      allowUserEditSettings: false,
    }
  },

  mounted() {
    this.populate()
  },

  beforeRouteLeave(to, from, next) {
    this.$root.doBeforeRouteLeave(to, from, next)
  },

  methods: {
    /**
     * Method is used in doBeforeRouteLeave mixin
     */
    hasChanges() {
      const data = settings.getIframeAppSettings()
      return (
        this.url !== data.url
        || this.tabName !== data.tabName
        || this.adminLogin.value !== data.adminLogin
        || this.adminPassword.value !== data.adminPassword
        || this.allowUserEditSettings.value !== data.allowUserEditSettings
      )
    },

    /**
     * Method is used in doBeforeRouteLeave mixin,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges() {
      this.populate()
    },

    isValidData() {
      if (!isValidHttpURL(this.url)) {
        notification.showError(this.$t('IFRAMEAPPSEAFILE.ERROR_URL_NOT_VALID'))
        this.$refs.url.$el.focus()
        return false
      }
      return true
    },

    save() {
      if (!this.saving && this.isValidData()) {
        this.saving = true
        const parameters = {
          TabName: this.tabName,
          Url: this.url,
          AdminLogin: this.adminLogin,          
        }

        const data = settings.getIframeAppSettings()
        if (this.adminPassword.value !== data.adminPassword) {
          parameters['AdminPassword'] = this.adminPassword
        }

        webApi
          .sendRequest({
            moduleName: 'IframeAppSeafile',
            methodName: 'UpdateAdminSettings',
            parameters,
          })
          .then(
            (result) => {
              this.saving = false
              if (result === true) {
                settings.saveIframeAppSettings({
                  tabName: this.tabName,
                  url: this.url,
                  adminLogin: this.adminLogin,
                  adminPassword: this.adminPassword,
                })
                this.populate()
                notification.showReport(this.$t('COREWEBCLIENT.REPORT_SETTINGS_UPDATE_SUCCESS'))
              } else {
                notification.showError(this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
              }
            },
            (response) => {
              this.saving = false
              notification.showError(
                errors.getTextFromResponse(response, this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
              )
            }
          )
      }
    },

    populate() {
      const data = settings.getIframeAppSettings()
      this.tabName = data.tabName
      this.url = data.url
      this.adminLogin = data.adminLogin
      this.adminPassword = data.adminPassword
    },
  },
}
</script>
