<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import { Event } from '../Types/index.ts'

import { InquiryGeneralIcons } from '../utils/icons.ts'
import {
  canComment,
  canUseResource,
  canShare,
  createPermissionContextForContent,
  ContentType,
} from '../utils/permissions.ts'
import {
  SideBarTabComments,
  SideBarTabShare,
  SideBarTabResources,
  SideBarTabMisc,
} from '../components/SideBar/index.js'
import { useInquiryStore } from '../stores/inquiry.ts'

const inquiryStore = useInquiryStore()

// Context for permissions
const context = computed(() => {
  const ctx = createPermissionContextForContent(
    ContentType.Inquiry,
    inquiryStore.owner.id,
    inquiryStore.configuration.access === 'public',
    inquiryStore.status.isLocked,
    inquiryStore.status.isExpired,
    inquiryStore.status.deletionDate > 0,
    inquiryStore.status.isArchived,
    inquiryStore.inquiryGroups.length > 0,
    inquiryStore.inquiryGroups,
    inquiryStore.type,
    inquiryStore.family, 
    inquiryStore.configuration.access as AccessLevel,
    inquiryStore.status.isFinalStatus,
    inquiryStore.status.moderationStatus 
  )
  return ctx
}) 


const showSidebar = ref(window.innerWidth > 920)
const activeTab = ref(t('agora', 'Comments').toLowerCase())

const shouldDisplay = computed(() => inquiryStore.status.forceEditMode)

onMounted(() => {
  subscribe(Event.SidebarToggle, (payload) => {
    showSidebar.value = payload?.open ?? !showSidebar.value
    activeTab.value = payload?.activeTab ?? activeTab.value
  })
  subscribe(Event.SidebarChangeTab, (payload) => {
    activeTab.value = payload?.activeTab ?? activeTab.value
  })
})

onUnmounted(() => {
  unsubscribe(Event.SidebarToggle, () => {
    activeTab.value = 'comments'
  })
  unsubscribe(Event.SidebarChangeTab, () => {
    showSidebar.value = false
  })
})

function closeSideBar() {
  emit(Event.SidebarToggle, { open: false })
}
</script>

<template>
    <aside v-if="shouldDisplay">
        <NcAppSidebar
                v-show="showSidebar"
                v-model="activeTab"
                :name="t('agora', 'Details')"
                @close="closeSideBar()"
                >
         <NcAppSidebarTab
                        v-if="canComment(context)"
                        id="comments"
                        :order="1"
                        :name="t('agora', 'Comments')"
                        >
                        <template #icon>
                            <component :is="InquiryGeneralIcons.Comment" />
                        </template>
                <SideBarTabComments />
        </NcAppSidebarTab>
        
        <NcAppSidebarTab
                        id="misc"
                        :order="2"
                        :name="t('agora', 'Settings')"
                        >
                        <template #icon>
                            <component :is="InquiryGeneralIcons.Map" />
                        </template>
                <SideBarTabMisc />
        </NcAppSidebarTab>

        <NcAppSidebarTab
                v-if="canUseResource(context)"
                id="links"
                :order="4"
                :name="t('agora', 'Resources')"
                >
                <template #icon>
                    <component :is="InquiryGeneralIcons.LinkIcon" />
                </template>
        <SideBarTabResources />
        </NcAppSidebarTab>
        
        <NcAppSidebarTab
                v-if="canShare(context)"
                id="sharing"
                :order="5"
                :name="t('agora', 'Sharing')"
                >
                <template #icon>
                    <component :is="InquiryGeneralIcons.Share" />
                </template>
        <SideBarTabShare />
        </NcAppSidebarTab>

        </NcAppSidebar>
    </aside>
</template>
