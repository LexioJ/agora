<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { computed, onUnmounted, ref, watch, nextTick } from 'vue'
import { emit, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router'
import { showError, showSuccess } from '@nextcloud/dialogs'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'

import InquiryHeaderButtons from '../components/Inquiry/InquiryHeaderButtons.vue'
import InquiryGroupEditViewForm from '../components/InquiryGroup/InquiryGroupEditViewForm.vue'
import InquiryTransition from '../components/Inquiry/InquiryTransition.vue'
import InquiryGroup from './InquiryGroup.vue'
import InquiryGroupCreateDlg from '../components/Create/InquiryGroupCreateDlg.vue'
import { useInquiryGroupStore } from '../stores/inquiryGroup.ts'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { useSessionStore } from '../stores/session.ts'
import Collapsible from '../components/Base/modules/Collapsible.vue'
import type { CollapsibleProps } from '../components/Base/modules/Collapsible.vue'
import InquiryInfoCards from '../components/Cards/InquiryInfoCards.vue'
import { createPermissionContextForContent, ContentType, canEdit } from '../utils/permissions.ts'
import type { AccessLevel } from '../utils/permissions.ts'



const forceRenderKey = ref(0)
const selectedMode = ref('response')
const route = useRoute()
const router = useRouter()
const inquiryGroupStore = useInquiryGroupStore()
const inquiryGroupsStore = useInquiryGroupsStore()
const inquiriesStore = useInquiriesStore()
const sessionStore = useSessionStore()
const editMode = ref(false)
const isAppLoaded = ref(false)

const createGroupDlgToggle = ref(false)
const selectedInquiryGroupTypeForCreation = ref('')
const selectedGroups = ref([])
const isSaving = ref(false)


const availableGroups = computed(() => {
  const groups = sessionStore.currentUser.groups || {}
  if (typeof groups === 'object' && !Array.isArray(groups)) {
    return Object.keys(groups)
  }
  return groups
})

async function routeChild(childId: string) {
  router.push({ name: 'inquiryGroup', params: { id: childId } })
}

async function loadInquiry(id: string) {
  try {
    await inquiryGroupStore.load(id)
    const result = inquiryGroupsStore.inquiryGroups.filter(i => 
      i.parentId === Number(id)
    )
    inquiryGroupStore.childs = result

    if (inquiryGroupStore.childs.length === 0) {
      inquiryGroupStore.status.forceEditMode = true
      editMode.value = true
    } else {
      inquiryGroupStore.status.forceEditMode = false
      editMode.value = false
    }
    await nextTick()
    forceRenderKey.value += 1
  } catch  {
    showError(t('agora', 'Failed to load inquiry group'))
  } finally {
    isAppLoaded.value = true
  }
}

watch(
  () => route.params.id,
  async (newId) => {
    isAppLoaded.value = false
    await loadInquiry(newId as string)
  },
  { immediate: true }
)

onBeforeRouteUpdate(async (to, from, next) => {
  if (to.params.id) {
    inquiryGroupStore.reset()
  }
  next()
  emit('transitions-off', 500)
})

onUnmounted(() => {
  inquiryGroupStore.reset()
  unsubscribe('load-inquiry', () => {})
})

const collapsibleProps = computed<CollapsibleProps>(() => ({
  noCollapse: !inquiryGroupStore.configuration.collapseDescription || isShortDescription.value,
  initialState: inquiryGroupStore.currentUserStatus.countInquiries === 0 ? 'max' : 'min',
}))

const handleSave = async () => {
  if (isSaving.value) return

  if (!inquiryGroupStore.title || inquiryGroupStore.title.trim() === '') {
    showError(t('agora', 'Title is mandatory'), { timeout: 2000 })
    return
  }

  isSaving.value = true

  try {
    await inquiryGroupStore.update({
      id: inquiryGroupStore.id,
      type: inquiryGroupStore.type,
      title: inquiryGroupStore.title,
      description: inquiryGroupStore.description,
      categoryId: inquiryGroupStore.categoryId,
      locationId: inquiryGroupStore.locationId,
      parentId: inquiryGroupStore.parentId,
    })
    showSuccess(t('agora', 'The inquiry has been saved'), { timeout: 2000 })
  } catch {
    showError(t('agora', 'Error saving inquiry!'), { timeout: 2000 })
  } finally {
    isSaving.value = false
  }
}

const handleAllowedResponse = (responseType: string) => {
  selectedMode.value = 'response' 
  selectedInquiryGroupTypeForCreation.value = responseType
  createGroupDlgToggle.value = true
}


const handleCloseDialog = () => {
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = ''
}

const inquiryAdded = (inquiry) => {
  showSuccess(t('agora', 'Inquiry group {title} added', { title: inquiry.title }))
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = ''
  
  // Navigate to the new inquiry
  router.push({
    name: 'group',
    params: { id: inquiry.id },
  })
}

const handleGroupUpdate = (groups) => {
  selectedGroups.value = groups
}
</script>

<template>
  <NcAppContent v-if="isAppLoaded" :key="forceRenderKey" class="inquiry-list">
    <Collapsible 
      v-if="inquiryGroupStore.description" 
      class="sticky-left" 
      v-bind="collapsibleProps" 
    />
    
    <InquiryHeaderButtons />
    
    <!-- Action toolbar component -->
    <div class="area__main">
      <div class="view-content">
        <InquiryGroupEditViewForm 
          v-if="editMode" 
        />
      </div>

      <InquiryInfoCards class="sticky-left" />
    </div>

    <InquiryGroupCreateDlg
      v-if="createGroupDlgToggle"
      :response-type="selectedInquiryGroupTypeForCreation"
      :selected-groups="selectedGroups"
      :selected-mode="selectedMode"
      :available-groups="availableGroups"
      :parent-inquiry-id="inquiryGroupStore.id"
      @close="handleCloseDialog"
      @added="inquiryAdded"
      @update:selected-groups="handleGroupUpdate"
    />
  </NcAppContent>
</template>

<style lang="scss">
.type-display {
  display: flex;
  align-items: center;
  gap: 8px;

  .type-icon {
    flex-shrink: 0;
  }

  .type-label {
    font-weight: bold;
    text-transform: capitalize;
  }
}

.header-left-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 8px;
  width: 100%;
}
.dates-container {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: flex-end;

  @media (max-width: 1000px) {
    gap: 8px;

    .metadata-item {
      font-size: 0.8em;
    }
  }
}

.header-right-content {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.dates-container {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: flex-end;
  flex-shrink: 0;
  margin-right: 16px;
}

.metadata-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9em;
  color: var(--color-text-lighter);
  white-space: nowrap;
}

.date-label {
  white-space: nowrap;
}

.inquiry-list__list {
  width: 100%;
  display: flex;
  flex-direction: column;
  overflow: scroll;
  padding-bottom: 14px;
}

.observer_section {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 14px 0;
}

.clickable_load_more {
  cursor: pointer;
  font-weight: bold;
}

#expiring.closing {
  color: var(--color-warning);
  font-weight: bold;
}

#expiring.open {
  color: var(--color-text-lighter);
}
</style>
