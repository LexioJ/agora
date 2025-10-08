<!--
	- SPDX-FileCopyrightText: 2018 Nextcloud Contributors
	- SPDX-FileCopyrightText: 2018 Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { t } from '@nextcloud/l10n'

import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'

import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { InquiryGeneralIcons, StatusIcons } from '../utils/icons.ts'


const route = useRoute()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()

// Function to get icon component for family
const getFamilyIcon = (family: any) => {
  const iconName = family?.icon?.toLowerCase() || 'accountgroup'
  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
}

// Function to get icon component for inquiry type
const getInquiryTypeIcon = (inquiryType: any) => {
  const iconName = inquiryType?.icon?.toLowerCase() || 'filedocumentedit'
  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
}

// State for expanded/collapsed families
const expandedFamilies = ref<Set<string>>(new Set())

// Computed for available families
const inquiryFamilies = computed(() => {
  return sessionStore.appSettings.inquiryFamilyTab || []
})

// Computed for recent inquiries
const recentInquiries = computed(() => {
  return inquiriesStore.inquiries.slice(0, 5)
})

// Computed for inquiry types grouped by family
const inquiryTypesByFamily = computed(() => {
  const types = sessionStore.appSettings.inquiryTypeTab || []
  const grouped: Record<string, any[]> = {}
  
  types.forEach(type => {
    const familyKey = type.family
    if (!grouped[familyKey]) {
      grouped[familyKey] = []
    }
    grouped[familyKey].push(type)
  })
  
  return grouped
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

// Get inquiry types for a specific family
function getInquiryTypesForFamily(familyInquiryType: string) {
  return inquiryTypesByFamily.value[familyInquiryType] || []
}

// Check if a navigation item is active
function isItemActive(itemRoute: string) {
  return route.path === itemRoute || route.name === itemRoute
}

// Check if inquiry type is active
function isInquiryTypeActive(typeId: string) {
  return route.params.typeId === typeId.toString()
}
</script>

<template>
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
        :name="family.label"
        :allow-collapse="true"
        :open="isFamilyExpanded(family.inquiry_type)"
        @update:open="toggleFamily(family.inquiry_type)"
      >
        <template #counter>
          <span class="family-counter">
            {{ getInquiryTypesForFamily(family.inquiry_type).length }}
          </span>
        </template>

        <!-- Inquiry Types for this family -->
        <NcAppNavigationItem
          v-for="inquiryType in getInquiryTypesForFamily(family.inquiry_type)"
          :key="inquiryType.id"
          :name="inquiryType.label"
          :to="{ 
            name: 'menu-family-type', 
            params: { 
              familyId: family.id, 
              typeId: inquiryType.id 
            } 
          }"
          :exact="true"
        >
	  <template #icon>
            <component :is="getInquiryTypeIcon(inquiryType)" />
          </template>

          <template v-if="inquiryType.description" #description>
            {{ inquiryType.description }}
          </template>
        </NcAppNavigationItem>

        <NcAppNavigationItem
          v-if="getInquiryTypesForFamily(family.inquiry_type).length === 0"
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
  </nav>
</template>

<style lang="scss" scoped>
.navigation-menu {
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
