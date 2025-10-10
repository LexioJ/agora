<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'

import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import InquiryCreateDlg from '../components/Create/InquiryCreateDlg.vue'
import { NavigationIcons } from '../utils/icons.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { 
  getInquiryIcon,
  getInquiryLabel,
  getInquiryTypesByFamily,
  getInquiryTypesForFamily,
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

const availableGroups = computed(() => {
  const groups = sessionStore.currentUser.groups || {}
  if (typeof groups === 'object' && !Array.isArray(groups)) {
    return Object.keys(groups)
  }
  return groups
})

// State for selected family
const selectedFamily = ref<string | null>(route.params.familyId as string || null)

// State for expanded/collapsed families
const expandedFamilies = ref<Set<string>>(new Set())

// Computed for available families
const inquiryFamilies = computed((): InquiryFamily[] => {
  return sessionStore.appSettings.inquiryFamilyTab || []
})

// Computed for recent inquiries
const recentInquiries = computed(() => {
  return inquiriesStore.inquiries.slice(0, 5)
})

// Computed for all inquiry types
const allInquiryTypes = computed((): InquiryType[] => {
  return sessionStore.appSettings.inquiryTypeTab || []
})

// Computed for inquiry types grouped by family (with filter isOption === 0)
const inquiryTypesByFamily = computed(() => {
  const types = allInquiryTypes.value.filter(type => type.isOption === 0)
  return getInquiryTypesByFamily(types)
})

// DEBUG: Check data
onMounted(() => {
  console.log('🔍 DEBUG InquiryMenu - Families:', inquiryFamilies.value)
  console.log('🔍 DEBUG InquiryMenu - Inquiry Types:', allInquiryTypes.value)
  console.log('🔍 DEBUG InquiryMenu - Selected family:', selectedFamily.value)
})

// Toggle family expansion
function toggleFamily(familyId: string) {
  if (expandedFamilies.value.has(familyId)) {
    expandedFamilies.value.delete(familyId)
  } else {
    expandedFamilies.value.add(familyId)
  }
}

// Check if a family is expanded
function isFamilyExpanded(familyId: string) {
  return expandedFamilies.value.has(familyId)
}

// Get inquiry types for a specific family (with filter isOption === 0)
function getInquiryTypesForCurrentFamily(familyInquiryType: string) {
  const types = getInquiryTypesForFamily(familyInquiryType, inquiryTypesByFamily.value, 0)
  return types
}

// Function to show settings
function showSettings() {
  console.log('Opening settings...')
}

// Function to navigate to family inquiries
function navigateToFamilyInquiries(familyId: string) {
  router.push({
    name: 'family-inquiries',
    params: { familyId }
  })
}

// Function to create new inquiry from type
function createInquiry(inquiryType: InquiryType) {
  console.log('Creating inquiry from type:', inquiryType)
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

function handleCloseDialog() {
  createDlgToggle.value = false
  selectedInquiryTypeForCreation.value = null
  selectedGroups.value = []
}

// Function to handle group selection update
function handleGroupUpdate(groups: string[]) {
  selectedGroups.value = groups
}
</script>

<template>
  <div class="navigation-container">
    <!-- Menu de navigation -->
    <nav class="navigation-menu" aria-label="Inquiry navigation">
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
        >
          <template v-if="inquiry.unreadCount" #counter>
            <span class="counter-bubble">{{ inquiry.unreadCount }}</span>
          </template>
        </NcAppNavigationItem>
        
        <NcAppNavigationItem
          v-if="recentInquiries.length === 0"
          :name="t('agora', 'No recent inquiries')"
          :disabled="true"
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
          :name="getInquiryLabel(family)"
          :allow-collapse="true"
          :open="isFamilyExpanded(family.inquiry_type)"
          @update:open="toggleFamily(family.inquiry_type)"
	  @click="navigateToFamilyInquiries(family.id.toString())"
        >
          <template #icon>
            <component :is="getInquiryIcon(family)" />
          </template>
          
          <template #counter>
            <span class="family-counter">
              {{ getInquiryTypesForCurrentFamily(family.inquiry_type).length }}
            </span>
          </template>

          <!-- Inquiry Types for this family (only isOption === 0) -->
          <NcAppNavigationItem
            v-for="inquiryType in getInquiryTypesForCurrentFamily(family.inquiry_type)"
            :key="inquiryType.id"
            :name="getInquiryLabel(inquiryType)"
            @click="createInquiry(inquiryType)"
          >
            <template #icon>
              <component :is="getInquiryIcon(inquiryType)" />
            </template>

            <template v-if="inquiryType.description" #description>
              {{ inquiryType.description }}
            </template>
          </NcAppNavigationItem>

          <NcAppNavigationItem
            v-if="getInquiryTypesForCurrentFamily(family.inquiry_type).length === 0"
            :name="t('agora', 'No inquiry types')"
            :disabled="true"
          />
        </NcAppNavigationItem>

        <NcAppNavigationItem
          v-if="inquiryFamilies.length === 0"
          :name="t('agora', 'No families configured')"
          :disabled="true"
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
        />
      </NcAppNavigationList>


      <!-- Footer Section -->
      <NcAppNavigationList class="navigation-footer">
        <NcAppNavigationItem
          :name="t('agora', 'Settings')"
          class="footer-item"
          @click="showSettings()"
        >
          <template #icon>
            <Component :is="NavigationIcons.settings" />
          </template>
        </NcAppNavigationItem>
      </NcAppNavigationList>
    </nav>

    <!-- Inquiry Creation Dialog - EN DEHORS du nav -->
    <InquiryCreateDlg
      v-if="createDlgToggle"
      :inquiry-type="selectedInquiryTypeForCreation"
      :selected-groups="selectedGroups"
      :available-groups="availableGroups"
      @close="handleCloseDialog"
      @added="inquiryAdded"
      @update:selected-groups="handleGroupUpdate"
    />
  </div>
</template>

<style lang="scss" scoped>
.navigation-container {
  position: relative;
  height: 100%;
}

.navigation-menu {
  padding: 12px 0;
  display: flex;
  flex-direction: column;
  height: 100%;
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

.navigation-footer {
  margin-top: auto;
  border-top: 1px solid var(--color-border);
  padding-top: 8px;

  .footer-item {
    margin: 0 8px;
    border-radius: 8px;

    &:hover {
      background-color: var(--color-background-hover);
    }
  }
}

.family-counter {
  background: var(--color-background-darker);
  color: var(--color-text-lighter);
  border-radius: 10px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  min-width: 20px;
  text-align: center;
}

.counter-bubble {
  background: var(--color-primary-element);
  color: var(--color-primary-text);
  border-radius: 10px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  min-width: 20px;
  text-align: center;
}

// Custom styling for nested items
:deep(.app-navigation-entry) {
  .app-navigation-entry__children {
    .app-navigation-entry {
      padding-left: 20px;
      
      .app-navigation-entry__description {
        font-size: 12px;
        color: var(--color-text-lighter);
        margin-top: 2px;
      }
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .navigation-menu {
    padding: 8px 0;
  }
}

// Dark theme adjustments
.theme--dark {
  .family-counter {
    background: var(--color-background-hover);
  }
}
</style>
