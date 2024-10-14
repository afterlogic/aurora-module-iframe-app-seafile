<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg">
      <div class="row q-mb-md">
        <div class="col text-h5" v-t="'IFRAMEAPPSEAFILE.HEADING_SETTINGS_TAB'"/>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row">
            <div class="col-5">
              <q-btn unelevated no-caps dense class="q-px-sm" :ripple="false" color="primary"
                    :label="$t('IFRAMEAPPSEAFILE.ACTION_SYNC_GROUPS') + (syncGroupsInProgress ? ' ...' : '')" @click="syncGroups">
              </q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
    <ConfirmDialog ref="confirmDialog" />
    <q-inner-loading style="justify-content: flex-start;" :showing="loading || saving">
      <q-linear-progress query />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import webApi from 'src/utils/web-api'
import ConfirmDialog from 'components/ConfirmDialog'

export default {
  name: 'AdminSettingsPerTenant',

  components: {
    ConfirmDialog,
  },

  data () {
    return {
      saving: false,
      loading: false,
      syncGroupsInProgress: false,
    }
  },

  computed: {
    currentTenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    },
  },

  methods: {
    syncGroups(event, force) {
      if (!this.syncGroupsInProgress) {
        this.syncGroupsInProgress = true

        const parameters = {
          TenantId: this.currentTenantId,
          ForceRemove: !!force,
        }

        webApi.sendRequest({
          moduleName: 'IframeAppSeafile',
          methodName: 'SyncGroups',
          parameters,
        }).then(result => {
          this.syncGroupsInProgress = false
          if (result['GroupsToIds']?.length > 0) {
            this.askRemoveGroups(result['GroupsToIds'])
          } else if (result['Failed'] > 0) {
            notification.showError(this.$t('IFRAMEAPPSEAFILE.REPORT_GROUP_SYNC_SUCCESS'))
          } else {
            notification.showReport(this.$t('IFRAMEAPPSEAFILE.REPORT_GROUP_SYNC_SUCCESS'))
          }
        }, response => {
          this.syncGroupsInProgress = false
          notification.showError(errors.getTextFromResponse(response, this.$t('IFRAMEAPPSEAFILE.ERROR_GROUP_SYNC_FAILED')))
        })
      }
    },

    askRemoveGroups(seafileGroupIds) {
      if (_.isFunction(this?.$refs?.confirmDialog?.openDialog)) {
        this.$refs.confirmDialog.openDialog({
          title: this.$t('IFRAMEAPPSEAFILE.CONFIRM_REMOVE_SEAFILE_GROUPS'),
          message: this.$t('IFRAMEAPPSEAFILE.CONFIRM_REMOVE_SEAFILE_GROUPS_DESCRIPTION', { GROUP_IDS: seafileGroupIds?.join(',') }),
          okHandler: () => {
            this.syncGroups('event', true)
          }
        })
      }
    },
  }
}
</script>
