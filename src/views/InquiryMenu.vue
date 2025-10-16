<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import InquiryCreateDlg from '../components/Create/InquiryCreateDlg.vue'
import { HeaderBar } from '../components/Base/index.ts'
import { AgoraAppIcon } from '../components/AppIcons/index.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { 
  getInquiryItemData,
  getInquiryTypeData,
  type InquiryFamily,
  type InquiryType
} from '../helpers/modules/InquiryHelper.ts'

const route = useRoute()
const router = useRouter()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()
const createDlgToggle = ref(false)
const selectedInquiryTypeForCreation = ref<InquiryType | null>(null)
const selectedGroups = ref<string[]>([])

// ViewMode state - default to 'create' or get from route
const viewMode = ref<string>(route.query.viewMode as string || 'create')

const availableGroups = computed(() => {
  const groups = sessionStore.currentUser.groups || {}
  if (typeof groups === 'object' && !Array.isArray(groups)) {
    return Object.keys(groups)
  }
  return groups
})

// State for selected family
const selectedFamily = ref<string | null>(route.params.familyId as string || null)

// Computed for available families
const inquiryFamilies = computed((): InquiryFamily[] => {
  return sessionStore.appSettings.inquiryFamilyTab || []
})

// Computed for all inquiry types (templates)
const allInquiryTypes = computed((): InquiryType[] => {
  return sessionStore.appSettings.inquiryTypeTab || []
})

// DEBUG: Check data
onMounted(() => {
  console.log('🔍 DEBUG InquiryMenu - Families:', inquiryFamilies.value)
  console.log('🔍 DEBUG InquiryMenu - Inquiry Types:', allInquiryTypes.value)
  console.log('🔍 DEBUG InquiryMenu - Selected family:', selectedFamily.value)
  console.log('🔍 DEBUG InquiryMenu - View mode :', viewMode.value)
})

// Computed for inquiry types filtered by selected family (only isOption === 0)
const filteredInquiryTypes = computed(() => {
  if (!selectedFamily.value) return []
  
  const family = inquiryFamilies.value.find(f => f.id.toString() === selectedFamily.value)
  if (!family) return []
  
  console.log('🔍 Filtering for family:', family)
  
  const filtered = allInquiryTypes.value.filter(type => 
    type.family === family.inquiry_type && type.isOption === 0
  )
  return filtered
})

// Get family by ID
const currentFamily = computed(() => {
  if (!selectedFamily.value) return null
  return inquiryFamilies.value.find(f => f.id.toString() === selectedFamily.value)
})

// Get current family data (icon, label, description)
const currentFamilyData = computed(() => {
  return getInquiryItemData(currentFamily.value, t('agora', 'Inquiry Types'))
})

// Watch for route changes
watch(
  () => route.params.familyId,
  (newFamilyId) => {
    selectedFamily.value = newFamilyId as string || null
  }
)

// Watch for viewMode changes in route
watch(
  () => route.query.viewMode,
  (newViewMode) => {
    if (newViewMode) {
      viewMode.value = newViewMode as string
    }
  }
)

// Function to select a family
function selectFamily(familyId: string) {
  selectedFamily.value = familyId
  router.push({
    name: 'family-inquiries',
    params: { familyId },
    query: { viewMode: viewMode.value }
  })
}

// Function to clear family selection
function clearFamilySelection() {
  selectedFamily.value = null
  router.push({ 
    name: 'menu',
    query: { viewMode: viewMode.value }
  })
}

// Function to handle view mode change
function handleViewModeChange(mode: string) {
  viewMode.value = mode
  
  // Update current route with new view mode
  if (viewMode.value === 'create') {
    router.push({
      name: 'family-inquiries',
      params: { familyId: selectedFamily.value },
      query: { viewMode: mode }
    })
  } else {
    router.push({
      name: 'list',
      params: { type: 'relevant', familyId: selectedFamily.value },
      query: { viewMode: mode }
    })
  }
  
  // You can add additional logic here based on the view mode
  console.log(`View mode changed to: ${mode}`)
  // Example: Refresh data based on view mode, change display logic, etc.
}

// Function to create new inquiry from type
function createInquiry(inquiryType: InquiryType) {
  console.log('Creating inquiry from type:', inquiryType)
  selectedInquiryTypeForCreation.value = inquiryType
  createDlgToggle.value = true
}

// Function to add inquiry 
function inquiryAdded(payLoad: { id: number; title: string }) {
  createDlgToggle.value = false
  selectedInquiryTypeForCreation.value = null
  router.push({
    name: 'inquiry',
    params: { id: payLoad.id },
    query: { viewMode: viewMode.value }
  })
}

// Empty content props
const emptyContentProps = computed(() => ({
  name: selectedFamily.value 
    ? t('agora', 'No inquiry types found for this family')
    : t('agora', 'No inquiry families configured'),
  description: selectedFamily.value
    ? t('agora', 'There are no inquiry types available in this family')
    : t('agora', 'Please configure inquiry families in the settings'),
}))

function handleCloseDialog() {
  createDlgToggle.value = false
  selectedInquiryTypeForCreation.value = null
  selectedGroups.value = []
}
</script>

<template>
  <NcAppContent class="inquiry-menu">
    <HeaderBar>
      <template #title>
        {{ selectedFamily 
          ? currentFamilyData.label
          : t('agora', 'Select Inquiry Family') 
        }}
      </template>
      
      <!-- Header Actions Container -->
      <div class="header-actions">
        <!-- Back Button (conditionally shown) -->
        <NcButton 
          v-if="selectedFamily" 
          class="back-button" 
          @click="clearFamilySelection"
        >
          <span class="back-button__icon">←</span>
          {{ t('agora', 'Back to families') }}
        </NcButton>

        <!-- View Mode Switcher (always shown, aligned to right) -->
        <div v-if="selectedFamily" class="view-mode-switcher">
          <NcCheckboxRadioSwitch
            :button-variant="true"
            :model-value="viewMode"
            value="create"
            name="sharing_permission_radio"
            type="radio"
            button-variant-grouped="horizontal"
            @update:model-value="handleViewModeChange"
          >
            {{ t('agora', 'Create Mode') }}
          </NcCheckboxRadioSwitch>
          <NcCheckboxRadioSwitch
            :button-variant="true"
            :model-value="viewMode"
            value="view"
            name="sharing_permission_radio"
            type="radio"
            button-variant-grouped="horizontal"
            @update:model-value="handleViewModeChange"
          >
            {{ t('agora', 'View Mode') }}
          </NcCheckboxRadioSwitch>
        </div>
      </div>
    </HeaderBar>

    <!-- Family Selection Grid -->
    <div v-if="!selectedFamily" class="families-grid-container">
      <div class="families-grid">
        <div
          v-for="family in inquiryFamilies"
          :key="family.id"
          class="family-card-large"
          @click="selectFamily(family.id.toString())"
        >
          <div class="family-card-large__icon">
            <component :is="getInquiryItemData(family).icon" />
          </div>
          <div class="family-card-large__content">
            <h3 class="family-card-large__title">{{ getInquiryItemData(family).label }}</h3>
            <p class="family-card-large__description" v-if="getInquiryItemData(family).description">
              {{ getInquiryItemData(family).description }}
            </p>
          </div>
        </div>
      </div>

      <NcEmptyContent 
        v-if="inquiryFamilies.length === 0" 
        v-bind="emptyContentProps"
      >
        <template #icon>
          <AgoraAppIcon />
        </template>
      </NcEmptyContent>
    </div>

    <!-- Inquiry Types List for Selected Family -->
    <div v-else class="inquiry-types-container">
      <div class="inquiry-types-header">
        <div class="selected-family-info">
          <h2>{{ currentFamilyData.label }}</h2>
          <p v-if="currentFamilyData.description">{{ currentFamilyData.description }}</p>
        </div>
      </div>

      <div class="inquiry-types-grid">
        <div
          v-for="inquiryType in filteredInquiryTypes"
          :key="inquiryType.id"
          class="inquiry-type-card"
          @click="createInquiry(inquiryType)"
        >
          <div class="inquiry-type-card__icon">
            <component :is="getInquiryTypeData(inquiryType.inquiry_type, allInquiryTypes).icon" />
          </div>
          <div class="inquiry-type-card__content">
            <h4 class="inquiry-type-card__title">
              {{ getInquiryTypeData(inquiryType.inquiry_type, allInquiryTypes).label }}
            </h4>
            <p class="inquiry-type-card__description" v-if="getInquiryTypeData(inquiryType.inquiry_type, allInquiryTypes).description">
              {{ getInquiryTypeData(inquiryType.inquiry_type, allInquiryTypes).description }}
            </p>
          </div>
        </div>
      </div>

      <NcEmptyContent 
        v-if="filteredInquiryTypes.length === 0" 
        v-bind="emptyContentProps"
      >
        <template #icon>
          <AgoraAppIcon />
        </template>
      </NcEmptyContent>
    </div>

    <NcDialog
      v-if="createDlgToggle"
      :name="t('agora', 'Create New Inquiry')"
      :enable-slide-up="false"
      @close="handleCloseDialog"
    >
      <InquiryCreateDlg
        :inquiry-type="selectedInquiryTypeForCreation"
        :selected-groups="selectedGroups"
        :available-groups="availableGroups"
        @added="inquiryAdded"
        @close="handleCloseDialog"
        @update:selected-groups="selectedGroups = $event"
      />
    </NcDialog>
  </NcAppContent>
</template>

<style lang="scss" scoped>
.inquiry-menu {
  .area__main {
    width: 100%;
  }
}

// Header Actions Container
.header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end; // Align everything to the right
  gap: 16px;
  width: 100%;
  margin-left: auto; // Push container to the right

  // Back button styles
  .back-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 18px;
    cursor: pointer;
    color: var(--color-text-lighter);
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-right: auto; // Push back button to left, view modes to right

    &:hover {
      background: var(--color-background-dark);
      color: var(--color-main-text);
      border-color: var(--color-primary-element);
    }

    &__icon {
      font-size: 18px;
      font-weight: 600;
    }
  }

  // View Mode Switcher styles
  .view-mode-switcher {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--color-background-dark);
    border-radius: 10px;
    padding: 4px;
    border: 1px solid var(--color-border);

    // Style for the radio buttons
    :deep(.checkbox-radio-switch) {
      margin: 0;

      &.checkbox-radio-switch--button-variant {
        .checkbox-radio-switch__label {
          padding: 8px 16px;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s ease;
          
          &:hover {
            background: var(--color-background-hover);
          }
        }

        input:checked + .checkbox-radio-switch__label {
          background: var(--color-primary-element);
          color: var(--color-primary-text);
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
      }
    }
  }
}

.families-grid-container {
  padding: 20px;
}

.families-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.family-card-large {
  background: var(--color-main-background);
  border: 2px solid var(--color-border);
  border-radius: 20px;
  padding: 32px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-height: 240px;
  justify-content: center;
  position: relative;

  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
  }

  &__icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--color-primary-element), var(--color-primary-element-hover));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
  }

  &__title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--color-main-text);
    line-height: 1.3;
  }

  &__description {
    color: var(--color-text-lighter);
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 16px;
  }

  &__meta {
    margin-top: auto;
    
    .inquiry-count {
      background: var(--color-background-dark);
      color: var(--color-text-lighter);
      border-radius: 12px;
      padding: 6px 12px;
      font-size: 13px;
      font-weight: 600;
    }
  }
}

/* Inquiry Types List Styles */
.inquiry-types-container {
  padding: 20px;
}

.inquiry-types-header {
  display: flex;
  align-items: flex-start;
  gap: 24px;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);

  .selected-family-info {
    flex: 1;
    
    h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--color-main-text);
    }

    p {
      color: var(--color-text-lighter);
      font-size: 16px;
      line-height: 1.5;
      margin-bottom: 12px;
    }

    .inquiry-types-count {
      background: var(--color-background-dark);
      color: var(--color-text-lighter);
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 14px;
      font-weight: 600;
      display: inline-block;
    }
  }
}

.inquiry-types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.inquiry-type-card {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  min-height: 140px;

  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
  }

  &__icon {
    width: 48px;
    height: 48px;
    background: var(--color-background-dark);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__content {
    flex: 1;
  }

  &__title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-main-text);
    line-height: 1.3;
  }

  &__description {
    color: var(--color-text-lighter);
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 12px;
  }

  &__meta {
    .inquiry-type-id {
      background: var(--color-background-darker);
      color: var(--color-text-lighter);
      border-radius: 8px;
      padding: 4px 8px;
      font-size: 11px;
      font-weight: 500;
    }
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;

    .back-button {
      margin-right: 0;
      order: 1;
    }

    .view-mode-switcher {
      order: 2;
      justify-content: center;
    }
  }

  .families-grid {
    grid-template-columns: 1fr;
  }
  
  .inquiry-types-header {
    flex-direction: column;
    gap: 16px;
    
    .selected-family-info h2 {
      font-size: 24px;
    }
  }
  
  .inquiry-types-grid {
    grid-template-columns: 1fr;
  }
}
</style>
