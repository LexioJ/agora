<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'

// Import de tous les composants
import AdminInquiryFamilies from './AdminInquiryFamilies.vue'
import AdminInquiryTypes from './AdminInquiryTypes.vue'
import AdminInquiryStatus from './AdminInquiryStatus.vue'
import AdminOfficialRights from './AdminOfficialRights.vue'
import AdminModeratorRights from './AdminModeratorRights.vue'
import AdminInquiryRights from './AdminInquiryRights.vue'

const activeTab = ref('families')

const tabs = [
  { id: 'families', label: t('agora', 'Inquiry Families'), component: AdminInquiryFamilies },
  { id: 'types', label: t('agora', 'Inquiry Types'), component: AdminInquiryTypes },
  { id: 'status', label: t('agora', 'Status Settings'), component: AdminInquiryStatus },
  { id: 'official-rights', label: t('agora', 'Official Rights'), component: AdminOfficialRights },
  { id: 'moderator-rights', label: t('agora', 'Moderator Rights'), component: AdminModeratorRights },
  { id: 'inquiry-rights', label: t('agora', 'Inquiry Rights'), component: AdminInquiryRights },
]
</script>

<template>
  <div class="admin-settings-container">
    <NcAppNavigation>
      <NcAppNavigationItem
        v-for="tab in tabs"
        :key="tab.id"
        :name="tab.label"
        :active="activeTab === tab.id"
        @click="activeTab = tab.id"
      />
    </NcAppNavigation>

    <div class="settings-content">
      <component :is="tabs.find(t => t.id === activeTab)?.component" />
    </div>
  </div>
</template>

<style scoped>
.admin-settings-container {
  display: flex;
  min-height: 600px;
  background: var(--color-main-background);
}

.settings-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}
</style>
