<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { watch,ref, computed, onMounted } from 'vue'
import {  useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import InquiryCreateDlg from '../components/Create/InquiryCreateDlg.vue'
import { InquiryGeneralIcons,NavigationIcons } from '../utils/icons.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Event } from '../Types/index.ts'
import { 
  getInquiryItemData,
  getInquiryTypeData,
  getInquiryTypesByFamily,
  getInquiryTypesForFamily,
  type InquiryFamily,
  type InquiryType
} from '../helpers/modules/InquiryHelper.ts'

const preferencesStore = usePreferencesStore()

const router = useRouter()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()
const createDlgToggle = ref(false)
const selectedInquiryTypeForCreation = ref<InquiryType | null>(null)
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
// State for expanded/collapsed families
const expandedFamilies = ref<Set<string>>(new Set())

// Computed for available families
const inquiryFamilies = computed((): InquiryFamily[] => sessionStore.appSettings.inquiryFamilyTab || [])

// Computed for recent inquiries
const recentInquiries = computed(() => inquiriesStore.inquiries.slice(0, 5))

// Computed for all inquiry types
const allInquiryTypes = computed((): InquiryType[] => sessionStore.appSettings.inquiryTypeTab || [])

// Computed for inquiry types grouped by family (with filter isOption === 0)
const inquiryTypesByFamily = computed(() => {
  const types = allInquiryTypes.value.filter(type => type.isOption === 0)
  return getInquiryTypesByFamily(types)
})

// Computed for default view mode from app settings
const defaultViewMode = computed(() => preferencesStore.user.defaultDisplayMode === 'view' ? 'view' : 'create')

// DEBUG: Check data
onMounted(() => {
  inquiriesStore.load(false)
})

// Toggle family expansion
function toggleFamily(familyType: string) {
  if (expandedFamilies.value.has(familyType)) {
    expandedFamilies.value.delete(familyType)
  } else {
    expandedFamilies.value.add(familyType)
  }
}

// Check if a family is expanded
function isFamilyExpanded(familyType: string) {
  return expandedFamilies.value.has(familyType)
}

// Get inquiry types for a specific family (with filter isOption === 0)
function getInquiryTypesForCurrentFamily(familyInquiryType: string) {
  const types = getInquiryTypesForFamily(familyInquiryType, inquiryTypesByFamily.value, 0)
  return types
}

// Get family data (icon, label, description)
function getFamilyData(family: InquiryFamily) {
  return getInquiryItemData(family)
}

// Get inquiry type data (icon, label, description)
function getInquiryTypeDisplayData(inquiryType: InquiryType) {
  return getInquiryTypeData(inquiryType.inquiry_type, allInquiryTypes.value)
}

/**
 * Show the settings dialog
 */
function showSettings() {
  emit(Event.ShowSettings, null)
}

// Function to navigate to family inquiries
function navigateToFamilyInquiries(familyType: string) {
  inquiriesStore.setFamilyType(familyType)
  selectedFamily.value = familyType
  router.push({
  	name: defaultViewMode.value === 'create' ? 'menu' : 'list',
    	params: defaultViewMode.value === 'view' ? { type: 'relevant' } : {},
    	query: { viewMode: defaultViewMode.value }
  })
}

// Function to create new inquiry from type
function createInquiry(inquiryType: InquiryType) {
  selectedInquiryTypeForCreation.value = inquiryType
  createDlgToggle.value = true
}

// Function to handle inquiry added
function inquiryAdded(payload: { id: number; title: string }) {
  createDlgToggle.value = false
  selectedInquiryTypeForCreation.value = null
  selectedGroups.value = []
  router.push({
    name: 'inquiry',
    params: { id: payload.id },
  })
}


// Function to get icon for an inquiry based on its type
function getInquiryIcon(inquiry) {
  if (inquiry.type) {
    const typeData = getInquiryTypeData(inquiry.type, allInquiryTypes.value)
    return typeData?.icon || InquiryGeneralIcons.Flash
  }

  return InquiryGeneralIcons.Flash
}


function handleCloseDialog() {
  createDlgToggle.value = false
  selectedInquiryTypeForCreation.value = null
  selectedGroups.value = []
}

// Function to handle group selection update
function handleGroupUpdate(groups: string[]) {
  selectedGroups.value = groups
}

// Watch for familyType changes in store
watch(
  () => inquiriesStore.familyType,
  (newFamilyId) => {
    selectedFamily.value = newFamilyId
  }
)

</script>
<template>
  <NcAppNavigation class="agora-navigation" aria-label="Inquiry Navigation">
    <template #list>
        <!-- Recent Inquiries Section -->
        <NcAppNavigationList>
          <h3 class="navigation-caption">
            {{ t('agora', 'Recent Inquiries') }}
          </h3>
          <NcAppNavigationItem
            v-for="inquiry in recentInquiries"
            :key="inquiry.id"
            :name="inquiry.title"
            :to="{ name: 'inquiry', params: { id: inquiry.id } }"
            :exact="true"
            class="navigation-item"
          >
            <template #icon>
              <component :is="getInquiryIcon(inquiry)" class="nav-icon" />
            </template>
          </NcAppNavigationItem>

          <NcAppNavigationItem
            v-if="recentInquiries.length === 0"
            :name="t('agora', 'No recent inquiries')"
            :disabled="true"
            class="navigation-empty"
          />
        </NcAppNavigationList>

        <!-- Inquiry Families Section -->
        <NcAppNavigationList>
          <h3 class="navigation-caption">
            {{ t('agora', 'Inquiry Families') }}
          </h3>

          <NcAppNavigationItem
            v-for="family in inquiryFamilies"
            :key="family.id"
            :name="getFamilyData(family).label"
            :allow-collapse="true"
            :open="isFamilyExpanded(family.family_type)"
            class="navigation-item"
            @update:open="toggleFamily(family.family_type)"
            @click="navigateToFamilyInquiries(family.family_type)"
          >
            <template #icon>
              <component :is="getFamilyData(family).icon" />
            </template>

            <template #counter>
              <span class="family-counter">
                {{ getInquiryTypesForCurrentFamily(family.family_type).length }}
              </span>
            </template>

            <!-- Inquiry Types for this family (only isOption === 0) -->
            <NcAppNavigationItem
              v-for="inquiryType in getInquiryTypesForCurrentFamily(family.family_type)"
              :key="inquiryType.id"
              :name="getInquiryTypeDisplayData(inquiryType).label"
              class="navigation-item"
              @click="createInquiry(inquiryType)"
            >
              <template #icon>
                <component :is="getInquiryTypeDisplayData(inquiryType).icon" />
              </template>

              <template v-if="getInquiryTypeDisplayData(inquiryType).description" #description>
                {{ getInquiryTypeDisplayData(inquiryType).description }}
              </template>
            </NcAppNavigationItem>

            <NcAppNavigationItem
              v-if="getInquiryTypesForCurrentFamily(family.family_type).length === 0"
              :name="t('agora', 'No inquiry types')"
              :disabled="true"
              class="navigation-empty"
            />
          </NcAppNavigationItem>

          <NcAppNavigationItem
            v-if="inquiryFamilies.length === 0"
            :name="t('agora', 'No families configured')"
            :disabled="true"
            class="navigation-empty"
          />
        </NcAppNavigationList>

        <!-- Quick Actions Section -->
        <NcAppNavigationList>
          <h3 class="navigation-caption">
            {{ t('agora', 'Quick Actions') }}
          </h3>

          <NcAppNavigationItem
            :name="t('agora', 'All Inquiries')"
            :to="{ name: 'list', params: { type: 'relevant' } }"
            :exact="true"
            class="navigation-item"
          />
        </NcAppNavigationList>
    </template>

    <!-- Footer Section -->
    <template #footer>
      <NcAppNavigationList class="navigation-footer">
        <NcAppNavigationItem
          :name="t('agora', 'Settings')"
          class="footer-item"
          @click="showSettings()"
        >
          <template #icon>
            <Component :is="NavigationIcons.Settings" />
          </template>
        </NcAppNavigationItem>
      </NcAppNavigationList>
    </template>
  </NcAppNavigation>

  <InquiryCreateDlg
    v-if="createDlgToggle"
    :inquiry-type="selectedInquiryTypeForCreation"
    :selected-groups="selectedGroups"
    :available-groups="availableGroups"
    @close="handleCloseDialog"
    @added="inquiryAdded"
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

.navigation-counter {
  font-weight: 600;
}

.navigation-sublist {
  margin-left: 12px;
  border-left: 1px solid var(--color-border);
  padding: 0;

  :deep(.app-navigation-entry) {
    padding-left: 20px;
    
    .app-navigation-entry__description {
      font-size: 12px;
      color: var(--color-text-lighter);
      margin-top: 2px;
    }
  }
}

.navigation-empty {
  opacity: 0.7;
  font-style: italic;
}

// Override default navigation styles without :deep() nesting
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
  .navigation-sublist {
    background: var(--color-background-darker);
  }
}
</style>
