<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg">
      <div class="row q-mb-md">
        <div class="col text-h5">{{ $t('IFRAMEAPPSEAFILE.HEADING_SETTINGS_TAB') }}</div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row">
            <q-checkbox dense v-model="enableIframeApp">
              <q-item-label v-t="'IFRAMEAPPSEAFILE.LABEL_ALLOW_IFRAMEAPP'" />
            </q-checkbox>
          </div>
          <div class="row q-mt-md">
            <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_EMAIL'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="emailId" ref="emailId" @keyup.enter="updateSettingsForEntity" />
            </div>
          </div>
          <div class="row q-mt-md">
            <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_LOGIN'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="login" ref="login" @keyup.enter="updateSettingsForEntity" />
            </div>
          </div>
          <div class="row items-center q-mt-md">
            <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_PASSWORD'"></div>
            <div class="col-5">
              <q-input
                outlined
                dense
                bg-color="white"
                type="password"
                autocomplete="new-password"
                v-model="password"
                ref="password"
                @keyup.enter="updateSettingsForEntity"
              />
            </div>
            <div class="col1 q-mx-sm">
              <q-btn
                unelevated
                no-caps
                dense
                class="q-px-sm"
                :ripple="false"
                color="primary"
                :label="$t('IFRAMEAPPSEAFILE.LABEL_SHOW_PASSWORD')"
                @click="showPassword"
              />
            </div>
          </div>
          <div class="row q-mt-md">
            <div class="col-2 q-my-sm" v-t="'IFRAMEAPPSEAFILE.LABEL_QUOTA'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="quota" ref="quota" @keyup.enter="updateSettingsForEntity" />
            </div>
          </div>
          <div class="row q-mt-md">
            <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_NAME'"></div>
            <div class="col-5">
              <q-input outlined dense bg-color="white" v-model="name" ref="emailId" @keyup.enter="updateSettingsForEntity" />
            </div>
          </div>
        </q-card-section>
      </q-card>
      <div class="q-pt-md text-right">
        <q-btn
          unelevated
          no-caps
          dense
          class="q-px-sm q-mx-md"
          :ripple="false"
          color="primary"
          v-if="isCreateAccountAllowed"
          :label="$t('IFRAMEAPPSEAFILE.ACTION_CREATE_SEAFILE_ACCOUNT')"
          @click="confirmCreateSeafileAccount"
        />
        <q-btn
          unelevated
          no-caps
          dense
          class="q-px-sm"
          :ripple="false"
          color="primary"
          :label="$t('COREWEBCLIENT.ACTION_SAVE')"
          @click="updateSettingsForEntity"
        />
      </div>
      <ConfirmDialog ref="confirmDialog" />
    </div>
    <q-inner-loading style="justify-content: flex-start" :showing="loading || saving">
      <q-linear-progress query />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import _ from 'lodash'

import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'
import ConfirmDialog from 'src/components/ConfirmDialog'

import cache from 'src/cache'

const FAKE_PASS = '******'

export default {
  name: 'AdminSettingsPerUser',

  components: {
    ConfirmDialog,
  },

  data() {
    return {
      saving: false,
      loading: false,
      enableIframeApp: false,
      enableIframeAppFromServer: false,
      emailId: '',
      login: '',
      password: '',
      quota: 100,
      name: '',
      isCreateAccountAllowed: false,
    }
  },

  watch: {
    $route(to, from) {
      this.parseRoute()
    },
  },

  mounted() {
    this.parseRoute()
    this.getPerUserSettings()
  },

  beforeRouteLeave(to, from, next) {
    this.$root.doBeforeRouteLeave(to, from, next)
  },

  methods: {
    /**
     * Method is used in doBeforeRouteLeave mixin
     */
    hasChanges() {
      return this.enableIframeApp !== this.enableIframeAppFromServer
    },

    /**
     * Method is used in doBeforeRouteLeave mixin,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges() {
      this.enableIframeApp = this.enableIframeAppFromServer
    },

    parseRoute() {
      const userId = typesUtils.pPositiveInt(this.$route?.params?.id)
      if (this.user?.id !== userId) {
        this.user = {
          id: userId,
        }
        this.populate()
      }
    },

    populate() {
      const currentTenantId = this.$store.getters['tenants/getCurrentTenantId']
      cache.getUser(currentTenantId, this.user.id).then(({ user, userId }) => {
        if (userId === this.user.id) {
          if (user && _.isFunction(user?.getData)) {
            this.user = user
          } else {
            this.$emit('no-user-found')
          }
        }
      })
    },

    updateSettingsForEntity() {
      if (!this.saving) {
        this.saving = true
        const parameters = {
          UserId: this.user?.id,
          TenantId: this.user.tenantId,
          EnableModule: typesUtils.pBool(this.enableIframeApp),
          Email: this.emailId.trim(),
          LoginId: this.login.trim(),
          Name: this.name.trim(),
          Quota: typesUtils.pInt(this.quota),
        }
        if (this.password !== FAKE_PASS) {
          parameters.Password = this.password.trim()
        }
        webApi
          .sendRequest({
            moduleName: 'IframeAppSeafile',
            methodName: 'UpdatePerUserSettings',
            parameters,
          })
          .then(
            (result) => {
              this.saving = false
              if (result) {
                this.getPerUserSettings()
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

    getPerUserSettings() {
      this.loading = true
      const parameters = {
        UserId: this.user?.id,
        TenantId: this.user.tenantId,
      }
      webApi
        .sendRequest({
          moduleName: 'IframeAppSeafile',
          methodName: 'GetPerUserSettings',
          parameters,
        })
        .then(
          (result) => {
            this.loading = false
            if (result) {
              this.enableIframeApp = result.EnableModule
              this.enableIframeAppFromServer = result.EnableModule
              this.emailId = result.EmailId || ''
              this.login = result.Login || ''
              this.password = result.HasPassword ? FAKE_PASS : ''
              this.quota = result.Quota || 0
              this.name = result.Name || ''

              this.isCreateAccountAllowed = this.emailId === '' && this.login === ''
            }
          },
          (response) => {
            notification.showError(errors.getTextFromResponse(response))
          }
        )
    },

    showPassword() {
      this.loading = true
      const parameters = {
        UserId: this.user?.id,
        TenantId: this.user.tenantId,
      }
      webApi
        .sendRequest({
          moduleName: 'IframeAppSeafile',
          methodName: 'GetUserPassword',
          parameters,
        })
        .then(
          (result) => {
            this.loading = false
            if (result) {
              alert('Password is: ' + result);
            }
          },
          (response) => {
            notification.showError(errors.getTextFromResponse(response))
          }
        )
    },

    confirmCreateSeafileAccount() {
      if (this.user && _.isFunction(this?.$refs?.confirmDialog?.openDialog)) {
        this.$refs.confirmDialog.openDialog({
          // title: name,
          message: this.$t('IFRAMEAPPSEAFILE.CONFIRM_CREATE_SEAFILE_ACCOUNT', { ACCOUNT_EMAIL: this.user?.publicId }),
          okHandler: this.createSeafileAccount.bind(this)
        })
      }
    },
    createSeafileAccount() {
      this.loading = true
      const parameters = {
        UserId: this.user?.id,
        TenantId: this.user.tenantId,
      }
      webApi
        .sendRequest({
          moduleName: 'IframeAppSeafile',
          methodName: 'CreateSeafileAccount',
          parameters,
        })
        .then(
          (result) => {
            this.loading = false
            if (result) {
                this.getPerUserSettings()
                notification.showReport(this.$t('IFRAMEAPPSEAFILE.REPORT_SEAFILE_ACCOUNT_CREATION_SUCCESS'))
              } else {
                notification.showError(this.$t('IFRAMEAPPSEAFILE.ERROR_SEAFILE_ACCOUNT_CREATION_FAILED'))
              }
          },
          (response) => {
            notification.showError(errors.getTextFromResponse(response))
          }
        )
    },
  },
}
</script>
