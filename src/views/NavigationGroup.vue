<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud Contributors
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { watch, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationSpacer from '@nextcloud/vue/components/NcAppNavigationSpacer'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import InquiryGroupCreateDlg from '../components/Create/InquiryGroupCreateDlg.vue'
import { NavigationIcons } from '../utils/icons.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import type { InquiryGroupType } from '../stores/inquiryGroups.types.ts'
import { 
  getInquiryGroupTypeData,
} from '../helpers/modules/InquiryHelper.ts'

const preferencesStore = usePreferencesStore()
const router = useRouter()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()
const inquiryGroupsStore = useInquiryGroupsStore()
const createGroupDlgToggle = ref(false)
const selectedInquiryGroupTypeForCreation = ref<InquiryGroupType | null>(null)
const selectedGroups = ref<string[]>([])

const availableGroups = computed(() => {
  const groups = sessionStore.currentUser.groups || {}
  if (typeof groups === 'object' && !Array.isArray(groups)) {
    return Object.keys(groups)
  }
  return groups
})

// State for selected family
const selectedFamily = ref<string | null>(inquiriesStore.familyType || null)

// Computed for all inquiry group types (root types only)
const allInquiryGroupTypes = computed((): InquiryGroupType[] => {
  const groupTypes = sessionStore.appSettings.inquiryGroupTypeTab || []
  return groupTypes.filter(groupType => groupType.is_root === true)
})

// Filter group types by selected family
const filteredInquiryGroupTypes = computed(() => {
  if (!selectedFamily.value) return allInquiryGroupTypes.value
  
  return allInquiryGroupTypes.value.filter(groupType => 
    groupType.family === selectedFamily.value
  )
})

// Get inquiry groups from store
const inquiryGroups = computed(() => inquiryGroupsStore.inquiryGroups)

// Group inquiry groups by their type
const inquiryGroupsByType = computed(() => {
  const groupsByType: Record<string, any[]> = {}
  
  filteredInquiryGroupTypes.value.forEach(groupType => {
    const groupsOfType = inquiryGroups.value.filter(group => 
      group.type === groupType.group_type || group.group_type === groupType.group_type
    )
    groupsByType[groupType.group_type] = groupsOfType
  })
  
  return groupsByType
})

// Get inquiry group type data (icon, label, description)
function getInquiryGroupTypeDisplayData(inquiryGroupType: InquiryGroupType) {
  return getInquiryGroupTypeData(inquiryGroupType.group_type, allInquiryGroupTypes.value)
}

// Get groups count for specific type
function getGroupTypeCount(groupType: string) {
  return inquiryGroupsByType.value[groupType]?.length || 0
}

// Function to create new inquiry group from type
function createInquiryGroup(inquiryGroupType: InquiryGroupType) {
  selectedInquiryGroupTypeForCreation.value = inquiryGroupType
  createGroupDlgToggle.value = true
}

// Function to navigate to existing inquiry group
function navigateToInquiryGroup(group: any) {
  router.push({
    name: 'group',
    params: { id: group.id },
  })
}

// Function to handle inquiry group added
function inquiryGroupAdded(payload: { id: number; slug: string }) {
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = null
  selectedGroups.value = []
  // Refresh groups to include the new one
  router.push({
    name: 'group',
    params: { id: payload.id },
  })
}

function handleCloseGroupDialog() {
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = null
  selectedGroups.value = []
}

// Function to handle group selection update
function handleGroupUpdate(groups: string[]) {
  selectedGroups.value = groups
}

// Load data on mount
onMounted(() => {
})

// Watch for familyType changes in store
watch(
  () => inquiriesStore.familyType,
  (newFamilyId) => {
    selectedFamily.value = newFamilyId
  }
)

// Function to show settings (placeholder)
function showSettings() {
  showError(t('agora', 'Settings functionality not implemented yet'))
}
</script>

<template>
  <NcAppNavigation class="agora-navigation" aria-label="Agora Navigation">
    <!-- Navigation List -->
    <template #list>
      <!-- Groups Section -->
      <NcAppNavigationList v-if="filteredInquiryGroupTypes.length > 0">
        <h3 class="navigation-caption">
          {{ t('agora', 'Group Types') }}
        </h3>
        
        <NcAppNavigationItem
          v-for="inquiryGroupType in filteredInquiryGroupTypes"
          :key="inquiryGroupType.id"
          :name="t('agora', getInquiryGroupTypeDisplayData(inquiryGroupType).label)"
          :title="t('agora', getInquiryGroupTypeDisplayData(inquiryGroupType).description)"
          allow-collapse
          class="navigation-item"
          :open="false"
          @click="createInquiryGroup(inquiryGroupType)"
        >
          <template #icon>
            <component :is="getInquiryGroupTypeDisplayData(inquiryGroupType).icon" />
          </template>
          
          <template #counter>
            <NcCounterBubble
              :count="getGroupTypeCount(inquiryGroupType.group_type)"
              class="navigation-counter"
            />
          </template>

          <!-- List of existing groups of this type -->
          <ul class="navigation-sublist">
            <NcAppNavigationItem
              v-for="group in inquiryGroupsByType[inquiryGroupType.group_type]"
              :key="group.id"
              :name="group.title"
              :title="group.description"
              :to="{
                name: 'group',
                params: { id: group.id },
              }"
              class="navigation-subitem"
            >
              <template #icon>
                <component :is="getInquiryGroupTypeDisplayData(inquiryGroupType).icon" />
              </template>
            </NcAppNavigationItem>

            <NcAppNavigationItem
              v-if="getGroupTypeCount(inquiryGroupType.group_type) === 0"
              :name="t('agora', 'No groups created yet')"
              class="navigation-empty"
            />
          </ul>
        </NcAppNavigationItem>
      </NcAppNavigationList>

      <NcAppNavigationSpacer v-if="filteredInquiryGroupTypes.length > 0" />

      <!-- Quick Actions Section -->
      <NcAppNavigationList>
        <h3 class="navigation-caption">
          {{ t('agora', 'Quick Actions') }}
        </h3>

        <NcAppNavigationItem
          :name="t('agora', 'All Groups')"
          :to="{ name: 'menu' }"
          :exact="true"
          class="navigation-item"
        >
          <template #icon>
            <component :is="NavigationIcons.Home" />
          </template>
        </NcAppNavigationItem>

        <NcAppNavigationItem
          :name="t('agora', 'Settings')"
          class="navigation-item"
          @click="showSettings()"
        >
          <template #icon>
            <Component :is="NavigationIcons.Settings" />
          </template>
        </NcAppNavigationItem>
      </NcAppNavigationList>
    </template>
  </NcAppNavigation>

  <InquiryGroupCreateDlg
    v-if="createGroupDlgToggle"
    :inquiry-group-type="selectedInquiryGroupTypeForCreation"
    :selected-groups="selectedGroups"
    :available-groups="availableGroups"
    @close="handleCloseGroupDialog"
    @added="inquiryGroupAdded"
    @update:selected-groups="handleGroupUpdate"
  />
</template>

<style lang="scss">
.agora-navigation {
  padding: 12px 0;
}

.navigation-caption {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-lighter);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 12px 8px 12px;
  padding: 0;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 8px;
}

.navigation-item {
  margin: 2px 8px;
  border-radius: 8px;

  &:hover {
    background-color: var(--color-background-hover);
  }

  &.active {
    background-color: var(--color-primary-light);

    :deep(.app-navigation-entry__title) {
      font-weight: 600;
    }
  }
}

.navigation-sublist {
  margin-left: 8px;
}

.navigation-subitem {
  margin: 1px 4px;
  border-radius: 6px;

  &:hover {
    background-color: var(--color-background-hover);
  }
}

.navigation-counter {
  background-color: var(--color-background-darker);
  color: var(--color-text-lighter);
}

.navigation-empty {
  opacity: 0.6;
  font-style: italic;
}

// Override default navigation styles
:deep(.app-navigation__body) {
  overflow: revert;
}

:deep(.app-navigation-entry-icon),
:deep(.app-navigation-entry__title) {
  transition: opacity 0.2s ease;
}

:deep(.app-navigation-entry.active .app-navigation-entry-icon),
:deep(.app-navigation-entry.active .app-navigation-entry__title) {
  opacity: 1;
}

.closed {
  :deep(.app-navigation-entry-icon),
  :deep(.app-navigation-entry__title) {
    opacity: 0.6;
  }
}

.force-not-active {
  :deep(.app-navigation-entry.active) {
    background-color: transparent !important;

    * {
      color: unset !important;
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .agora-navigation {
    padding: 8px 0;
  }
}

// Dark theme adjustments
.theme--dark {
  .navigation-caption {
    color: var(--color-text-light);
  }
}
</style>
