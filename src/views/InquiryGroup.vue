<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <NcAppContent class="inquiry-group-page">
  <div class="inquiry-group-page">
    <!-- Enhanced Breadcrumb with better spacing -->
    <div class="breadcrumb-bar theme-dark">
      <div class="breadcrumb-container">
        <!-- Home Button with bigger icon -->
        <NcButton 
          type="tertiary"
          class="breadcrumb-home"
          @click="navigateToHome"
        >
          <template #icon>
              <component :is="InquiryGeneralIcons.Home" :size="24"/>
          </template>
          <span class="breadcrumb-label">{{ t('agora', 'Home') }}</span>
        </NcButton>
        
        <!-- Parent groups with better spacing -->
        <template v-for="(parent, index) in parentGroups" :key="parent.id">
          <div class="breadcrumb-separator">❯</div>
          <NcButton 
            type="tertiary"
            class="breadcrumb-item"
            @click="selectGroup(parent)"
          >
            <div class="breadcrumb-item-content">
                <component
                        :is="getInquiryGroupTypeData(parent.type, sessionStore.appSettings.inquiryGroupTypeTab).icon"
                        class="breadcrumb-icon"
                        />
                <span class="breadcrumb-label">{{ parent.title }}</span>
            </div>
          </NcButton>
        </template>

        <!-- Current group or type -->
        <div class="breadcrumb-separator">❯</div>
        <div class="breadcrumb-current">
            <div class="breadcrumb-current-content">
                <component
  :is="getInquiryGroupTypeData(currentGroupType, sessionStore.appSettings.inquiryGroupTypeTab).icon"
  class="breadcrumb-icon"
/>
                <span class="breadcrumb-label">{{ currentBreadcrumbTitle }}</span>
                <span v-if="totalInquiries > 0" class="inquiry-count">
                    ({{ totalInquiries }} {{ t('agora', 'inquiries') }})
                </span>
            </div>
        </div>
      </div>
    </div>

    <!-- White Separation Line -->
    <div class="separation-line"></div>

    <!-- Main Content -->
    <!-- Header Section -->
    <div class="group-header">
        <div class="header-left">
            <div class="group-icon-badge">
                <component
                        :is="getInquiryGroupTypeData(currentGroupType, sessionStore.appSettings.inquiryGroupTypeTab).icon"
                        class="group-icon"
                        />
            </div>
            <div class="group-title-section">
                <!-- TITLE: When no slug, use type label. When slug exists, use group title -->
                <h1 class="group-title">{{ currentTitle }}</h1>
                <!-- SUBTITLE: When no slug, use type description. When slug exists, use group subtitle -->
                <div class="group-subtitle">
                    <p>{{ currentDescription }}</p>
                    <span v-if="totalInquiries > 0 && hasSlug" class="inquiry-count-badge">
                        {{ totalInquiries }} {{ t('agora', 'inquiries') }}
                    </span>
                    <span v-if="groupsCount > 0 && !hasSlug" class="groups-count-badge">
                        {{ groupsCount }} {{ t('agora', 'groups') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>{{ t('agora', 'Loading...') }}</p>
    </div>

    <!-- SCENARIO 1: No slug selected - Show group type overview -->
    <div v-else-if="!hasSlug" class="type-overview">
        <!-- Type Information Card -->
        <div class="type-info-card">
            <div class="type-icon-large">
                <component
                        :is="getInquiryGroupTypeData(currentGroupType, sessionStore.appSettings.inquiryGroupTypeTab).icon"
                        class="type-icon-large"
                        />
            </div>
            <div class="type-content">
                <h2>{{ selectedTypeLabel }}</h2>
                <p class="type-description">{{ selectedTypeDescription }}</p>
                <div class="type-meta">
                    <span class="type-groups-count">
                        <span class="count">{{ filteredGroups.length }}</span>
                        {{ t('agora', 'groups available') }}
                    </span>
                    <span class="type-total-inquiries">
                        <span class="count">{{ totalInquiriesInType }}</span>
                        {{ t('agora', 'total inquiries') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Groups Grid for this type -->
        <div class="groups-section">
            <div class="section-header">
                <h3>{{ t('agora', 'Available Groups') }}</h3>
                <div class="section-actions">
                    <NcButton 
                     v-if="filteredGroups.length === 0"
                     @click="createNewGroup"
                     type="primary"
                     >
                     ➕ {{ t('agora', 'Create First Group') }}
                    </NcButton>
                </div>
            </div>

            <div v-if="filteredGroups.length > 0" class="groups-grid">
                <div 
                                                  v-for="group in filteredGroups" 
                                                  :key="group.id"
                                                  class="group-vignette"
                                                  @click="selectGroup(group)"
                                                  >
                                                  <div v-if="group.coverId" class="vignette-cover">
                                                      <img :src="getCoverUrl(group.coverId)" :alt="group.title" />
                                                      <div class="vignette-cover-overlay"></div>
                                                  </div>
                                                  <div class="vignette-content">
                                                      <div class="vignette-icon-badge">
                                                          <component
  :is="getInquiryGroupTypeData(group.type, sessionStore.appSettings.inquiryGroupTypeTab).icon"
  class="vignette-icon-badge"
/>
                                                      </div>
                                                      <h4>{{ group.title }}</h4>
                                                      <p v-if="group.description" class="vignette-description">
                                                      {{ truncateText(group.description, 120) }}
                                                      </p>
                                                      <div class="vignette-stats">
                                                          <div class="stat-item">
                                                              <span class="stat-icon">📝</span>
                                                              <span class="stat-value">{{ group.inquiryIds?.length || 0 }}</span>
                                                              <span class="stat-label">{{ t('agora', 'inquiries') }}</span>
                                                          </div>
                                                          <div v-if="group.children?.length > 0" class="stat-item">
                                                              <span class="stat-icon">👥</span>
                                                              <span class="stat-value">{{ group.children.length }}</span>
                                                              <span class="stat-label">{{ t('agora', 'subgroups') }}</span>
                                                          </div>
                                                          <div v-if="group.members?.length > 0" class="stat-item">
                                                              <span class="stat-icon">👤</span>
                                                              <span class="stat-value">{{ group.members.length }}</span>
                                                              <span class="stat-label">{{ t('agora', 'members') }}</span>
                                                          </div>
                                                      </div>
                                                      <div class="vignette-footer">
                                                          <NcButton class="view-group-button" @click.stop="selectGroup(group)">
                                                          {{ t('agora', 'View Group') }}
                                                          <template #icon>
                                                              <svg width="16" height="16" viewBox="0 0 24 24">
                                                                  <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                                                              </svg>
                                                          </template>
                                                          </NcButton>
                                                      </div>
                                                  </div>
                </div>
            </div>

            <div v-else class="empty-groups-state">
                <div class="empty-state-icon">🏛️</div>
                <h3>{{ t('agora', 'No groups yet') }}</h3>
                <p>{{ t('agora', 'Be the first to create a group for this type') }}</p>
                <NcButton @click="createNewGroup" type="primary">
                ➕ {{ t('agora', 'Create New Group') }}
                </NcButton>
            </div>
        </div>
    </div>

    <!-- SCENARIO 2: Specific group selected - Show group details -->
    <div v-else-if="currentInquiryGroup" class="group-detail-view">
        <!-- Banner Section -->
        <div v-if="currentInquiryGroup.coverId" class="group-banner">
            <img 
                                                :src="getCoverUrl(currentInquiryGroup.coverId)" 
                                                :alt="currentInquiryGroup.title"
                                                class="banner-image"
                                                />
            <div class="banner-overlay">
                <div class="banner-description">
                    {{ currentInquiryGroup.description || t('agora', 'No description available') }}
                </div>
                <div v-if="isOwnerOrAdmin" class="banner-actions">
                    <NcActions class="actions-hover">
                    <NcActionButton @click="handleModify">
                    ✏️ {{ t('agora', 'Modify') }}
                    </NcActionButton>
                    <NcActionButton class="danger" @click="handleDelete">
                    🗑️ {{ t('agora', 'Delete') }}
                    </NcActionButton>
                    <NcActionButton @click="handleAddResponse">
                    ➕ {{ t('agora', 'Add Allowed Response') }}
                    </NcActionButton>
                    </NcActions>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="layout-container">
            <!-- Main Content Column -->
            <div class="main-content">
                <!-- Child Groups Section -->
                <div v-if="childGroups.length > 0" class="section-child-groups">
                    <h3 class="section-title">
                        {{ t('agora', 'Subgroups') }} ({{ childGroups.length }})
                    </h3>
                    <div class="children-grid">
                        <div 
                         v-for="child in childGroups" 
                         :key="child.id"
                         class="child-card"
                         @click="selectGroup(child)"
                         >
                         <div class="child-icon">
                             <component
  :is="getInquiryGroupTypeData(child.type, sessionStore.appSettings.inquiryGroupTypeTab).icon"
  class="child-icon"
/>
                         </div>
                            <div class="child-info">
                                <h4>{{ child.title }}</h4>
                                <p v-if="child.description">
                                {{ truncateText(child.description, 80) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Allowed Response Types -->
                <div v-if="allowedResponseTypes.length > 0" class="section-response-types">
                    <h3 class="section-title">
                        {{ t('agora', 'Allowed Response Types') }}
                    </h3>
                    <div class="response-grid">
                        <div 
                         v-for="type in allowedResponseTypes" 
                         :key="type.group_type"
                         class="response-card"
                         @click="createInquiry(type.group_type)"
                         >
                             <component
                                     :is="getInquiryGroupTypeData(type.group_type, sessionStore.appSettings.inquiryGroupTypeTab).icon"
                                     class="response-icon"
                                     />
                            <span>{{ getTypeLabel(type.group_type) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Inquiries Display -->
                <div class="inquiries-display">
                    <template v-for="(inquiries, typeKey) in groupedInquiries" :key="typeKey">
                        <div class="inquiry-section">
                            <h3 class="section-title">
                                {{ getInquiryTypeLabel(typeKey) }} ({{ inquiries.length }})
                            </h3>

                            <!-- Main Display (full page) -->
                            <div v-if="getDisplayModeForType(inquiries) === 'main'" class="inquiries-main">
                                <div 
                                                                                    v-for="inquiry in inquiries" 
                                                                                    :key="inquiry.id"
                                                                                    class="inquiry-main-item"
                                                                                    @click="navigateToInquiry(inquiry.id)"
                                                                                    >
                                                                                    <h4>{{ inquiry.title }}</h4>
                                                                                    <div v-html="inquiry.description"></div>
                                </div>
                            </div>

                            <!-- Card Display -->
                            <div v-else-if="getDisplayModeForType(inquiries) === 'cards'" class="inquiries-cards">
                                <div class="cards-grid">
                                    <div 
                                     v-for="inquiry in inquiries" 
                                     :key="inquiry.id"
                                     class="inquiry-card"
                                     @click="navigateToInquiry(inquiry.id)"
                                     >
                                     <div class="card-header">
                                         <component
  :is="getInquiryTypeData(inquiry.inquiry_type, sessionStore.appSettings.inquiryTypeTab).icon"
  class="card-icon"
/>
                                        <span class="card-status" :class="`status-${inquiry.status}`">
                                            {{ getStatusText(inquiry.status) }}
                                        </span>
                                     </div>
                                     <h4>{{ inquiry.title }}</h4>
                                     <p v-if="inquiry.description" class="card-description">
                                     {{ truncateText(stripHtml(inquiry.description), 120) }}
                                     </p>
                                     <div class="card-footer">
                                         <NcButton 
                                          v-if="inquiry.status === 'open'" 
                                          class="action-button open"
                                          >
                                          {{ t('agora', 'Open') }}
                                         </NcButton>
                                         <NcButton 
                                          v-else-if="inquiry.status === 'closed'" 
                                          class="action-button closed" 
                                          disabled
                                          >
                                          {{ t('agora', 'Closed') }}
                                         </NcButton>
                                         <NcButton v-else class="action-button">
                                         {{ t('agora', 'Read') }}
                                         </NcButton>
                                     </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Block Display -->
                            <div v-else class="inquiries-block">
                                <div class="block-list">
                                    <div 
                                     v-for="inquiry in inquiries" 
                                     :key="inquiry.id"
                                     class="block-item"
                                     @click="navigateToInquiry(inquiry.id)"
                                     >
<component
  :is="getInquiryTypeData(inquiry.inquiry_type, sessionStore.appSettings.inquiryTypeTab).icon"
  class="block-icon"
/>
                                        <div class="block-content">
                                            <h4>{{ inquiry.title }}</h4>
                                            <div class="block-meta">
                                                <span class="status" :class="`status-${inquiry.status}`">
                                                    {{ getStatusText(inquiry.status) }}
                                                </span>
                                                <span v-if="inquiry.date" class="date">
                                                    {{ formatDate(inquiry.date) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="block-action">
                                            <NcButton v-if="inquiry.status === 'open'" size="small">
                                            {{ t('agora', 'Open') }}
                                            </NcButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state for inquiries -->
                    <div v-if="groupInquiries.length === 0" class="empty-inquiries-state">
                        <div class="empty-icon">📝</div>
                        <h3>{{ t('agora', 'No inquiries yet') }}</h3>
                        <p>{{ t('agora', 'Start by creating the first inquiry in this group') }}</p>
                        <NcButton @click="navigateToCreateInquiry" type="primary">
                        ➕ {{ t('agora', 'Create First Inquiry') }}
                        </NcButton>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="sidebar">
                <!-- Group Description -->
                <div v-if="currentInquiryGroup.description" class="sidebar-section">
                    <h3>{{ t('agora', 'Description') }}</h3>
                    <p class="description-text">{{ currentInquiryGroup.description }}</p>
                </div>

                <!-- Inquiry Types Summary -->
                <div class="sidebar-section types-summary">
                    <h3>{{ t('agora', 'Inquiry types in this group') }}</h3>
                    <div class="types-list">
                        <div 
                         v-for="(count, typeKey) in inquiryTypeCounts" 
                         :key="typeKey"
                         class="type-item"
                         >
                         <component
  :is="getInquiryTypeData(typeKey, sessionStore.appSettings.inquiryTypeTab).icon"
  class="type-icon"
/>
                            <span class="type-label">{{ getInquiryTypeLabel(typeKey) }}</span>
                            <span class="type-count">({{ count }})</span>
                        </div>
                        <div class="type-item" v-if="groupInquiries.length === 0">
                            <div class="type-icon">📝</div>
                            <span class="type-label">{{ t('agora', 'No inquiries yet') }}</span>
                        </div>
                    </div>
                    <NcButton 
                                  v-if="isOwnerOrAdmin" 
                                  class="add-inquiry-btn"
                                  @click="navigateToCreateInquiry"
                                  >
                                  ➕ {{ t('agora', 'Add Inquiry to this Group') }}
                    </NcButton>
                </div>
            </div>
        </div>
    </div>

    <!-- Group not found -->
    <div v-else class="not-found-state">
        <div class="not-found-icon">🔍</div>
        <h2>{{ t('agora', 'Group not found') }}</h2>
        <p>{{ t('agora', 'The group you are looking for does not exist or you do not have permission to access it.') }}</p>
        <NcButton @click="navigateToHome" type="primary">
        {{ t('agora', 'Back to home') }}
        </NcButton>
    </div>
  </div>
  </NcAppContent>
</template>

<script setup lang="ts">
    import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import { InquiryGeneralIcons } from '../utils/icons.ts'

import { 
  getInquiryGroupTypeData,
  getInquiryTypeData,
  getInquiryGroupItemData
} from '../helpers/modules/InquiryHelper.ts'

const props = defineProps<{
  slug?: string
}>()

// Use it in your component
const currentSlug = computed(() => props.slug || route.params.slug )


const route = useRoute()
const router = useRouter()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()
const inquiryGroupsStore = useInquiryGroupsStore()

const isLoading = ref(true)

// Check if we have a slug in the route
const hasSlug = computed(() => {
  const slug = route.params.slug as string
  return slug && slug !== 'none' && slug !== 'undefined' && slug !== ''
})

// Get current group type from route or default to 'assembly'
const currentGroupType = computed(() => {
  if (hasSlug.value && currentInquiryGroup.value) {
    return currentInquiryGroup.value.type
  }

  // Check if type is specified in query params
  if (route.query.type) {
    return route.query.type as string
  }

  // Default to assembly
  return 'assembly'
})

// Get type data from appSettings
const selectedTypeData = computed(() => {
  const typeTab = sessionStore.appSettings?.inquiryGroupTypeTab || []
  const type = typeTab.find(t => t.group_type === currentGroupType.value)

  // If not found, try to find assembly or first available
  if (!type) {
    const assemblyType = typeTab.find(t => t.group_type === 'assembly')
    return assemblyType || typeTab[0] || {
      group_type: 'assembly',
      label: t('agora', 'Assembly'),
      description: t('agora', 'General assembly groups'),
      icon: '🏛️'
    }
  }

  return type
})

const selectedTypeIcon = computed(() => selectedTypeData.value?.icon || '🏛️')
const selectedTypeLabel = computed(() => 
  selectedTypeData.value?.label ? t('agora', selectedTypeData.value.label) : t('agora', 'Assembly')
)
const selectedTypeDescription = computed(() => 
  selectedTypeData.value?.description ? t('agora', selectedTypeData.value.description) : t('agora', 'Browse available groups')
)

// Get current group if slug exists
const currentInquiryGroup = computed(() => {
  if (!hasSlug.value) return null
  const slug = route.params.slug as string
  return inquiryGroupsStore.bySlug(slug)
})

// Current title logic
const currentTitle = computed(() => {
  if (hasSlug.value && currentInquiryGroup.value) {
    return currentInquiryGroup.value.title || currentInquiryGroup.value.name
  }
  return selectedTypeLabel.value
})

// Current description logic
const currentDescription = computed(() => {
  if (hasSlug.value && currentInquiryGroup.value) {
    return currentInquiryGroup.value.description || selectedTypeDescription.value
  }
  return selectedTypeDescription.value
})

// Breadcrumb current title
const currentBreadcrumbTitle = computed(() => {
  return currentTitle.value
})

const currentIcon = computed(() => {
  if (hasSlug.value && currentInquiryGroup.value) {
    return getGroupTypeIcon(currentInquiryGroup.value.type)
  }
  return selectedTypeIcon.value
})

// Filter groups by current type when no slug
const filteredGroups = computed(() => {
  if (hasSlug.value) return []

  const allGroups = inquiryGroupsStore.inquiryGroups || []
  return allGroups.filter(group => group.type === currentGroupType.value)
})

const groupsCount = computed(() => filteredGroups.value.length)

// Calculate total inquiries in type (for type overview)
const totalInquiriesInType = computed(() => {
  return filteredGroups.value.reduce((total, group) => {
    return total + (group.inquiryIds?.length || 0)
  }, 0)
})

// For specific group view
const groupInquiries = computed(() => {
  if (!currentInquiryGroup.value?.inquiryIds) return []
  const inquiries = inquiriesStore.inquiries || []
  return inquiries.filter(i => 
    currentInquiryGroup.value.inquiryIds.includes(i.id)
  )
})

const totalInquiries = computed(() => groupInquiries.value.length)

// Group inquiries by type
const groupedInquiries = computed(() => {
  const grouped = {}
  groupInquiries.value.forEach(inquiry => {
    const type = inquiry.inquiry_type || 'default'
    if (!grouped[type]) grouped[type] = []
    grouped[type].push(inquiry)
  })
  return grouped
})

const inquiryTypeCounts = computed(() => {
  const counts = {}
  Object.entries(groupedInquiries.value).forEach(([type, inquiries]) => {
    counts[type] = inquiries.length
  })
  return counts
})

// Child groups for current group
const childGroups = computed(() => {
  if (!currentInquiryGroup.value) return []
  const allGroups = inquiryGroupsStore.inquiryGroups || []
  return allGroups.filter(g => g.parentId === currentInquiryGroup.value.id)
})

// Parent groups for breadcrumb
const parentGroups = computed(() => {
  if (!currentInquiryGroup.value) return []

  const parents = []
  let group = currentInquiryGroup.value
  const allGroups = inquiryGroupsStore.inquiryGroups || []

  while (group.parentId) {
    const parent = allGroups.find(g => g.id === group.parentId)
    if (parent) {
      parents.unshift(parent)
      group = parent
    } else {
      break
    }
  }

  return parents
})

// Allowed response types
const allowedResponseTypes = computed(() => {
  if (!selectedTypeData.value?.family) return []

  const typeTab = sessionStore.appSettings?.inquiryGroupTypeTab || []
  return typeTab.filter(type => 
    type.group_type !== currentGroupType.value && 
    type.family === selectedTypeData.value.family
  )
})

// Helper functions
function getNextcloudPreviewUrl(fileId: number, x = 1920, y = 1080, autoScale = true) {
  const baseUrl = window.location.origin
  return `${baseUrl}/index.php/core/preview?fileId=${fileId}&x=${x}&y=${y}&a=${autoScale ? 1 : 0}`
}

function getCoverUrl(coverId: string | number) {
  if (!coverId) return null
  const fileId = typeof coverId === 'string' ? parseInt(coverId) : coverId
  return getNextcloudPreviewUrl(fileId, 1200, 400)
}

function getGroupTypeIcon(type: string) {
  const typeTab = sessionStore.appSettings?.inquiryGroupTypeTab || []
  const typeData = typeTab.find(t => t.group_type === type)
  return typeData?.icon || '🏛️'
}

function getInquiryTypeIcon(typeKey: string) {
  const typeTab = sessionStore.appSettings?.inquiryTypeTab || []
  const typeData = typeTab.find(t => t.inquiry_type === typeKey)
  return typeData?.icon || '📝'
}

function getInquiryTypeLabel(typeKey: string) {
  const typeTab = sessionStore.appSettings?.inquiryTypeTab || []
  const typeData = typeTab.find(t => t.inquiry_type === typeKey)
  return typeData?.label ? t('agora', typeData.label) : typeKey
}

function getTypeLabel(type: string) {
  const typeTab = sessionStore.appSettings?.inquiryGroupTypeTab || []
  const typeData = typeTab.find(t => t.group_type === type)
  return typeData?.label ? t('agora', typeData.label) : type
}

function getDisplayModeForType(inquiries: any[]) {
  const firstInquiry = inquiries[0]
  return firstInquiry?.miscFields?.display || 'block'
}

function truncateText(text: string, length: number) {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

function stripHtml(html: string) {
  return html?.replace(/<[^>]*>/g, '') || ''
}

function getStatusText(status: string) {
  switch (status) {
    case 'open': return t('agora', 'Open')
    case 'closed': return t('agora', 'Closed')
    default: return status
  }
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString()
  } catch {
    return dateString
  }
}

const isOwnerOrAdmin = computed(() => false)

const groupTypeIcon = computed(() => {
  return getGroupTypeIcon(currentGroupType.value)
})

// Navigation
function navigateToHome() {
  router.push({ name: 'group-list', params: { slug: 'none' }, query: {} })
}

function selectGroup(group: any) {
  if (group.slug) {
    router.push({ name: 'group-list', params: { slug: group.slug } })
  }
}

function navigateToInquiry(inquiryId: number) {
  router.push({ name: 'inquiry', params: { id: inquiryId } })
}

function navigateToCreateInquiry() {
  router.push({
    name: 'menu',
    query: {
      viewMode: 'create',
      familyType: selectedTypeData.value?.family,
      groupType: currentGroupType.value
    }
  })
}

function createInquiry(type: string) {
  router.push({
    name: 'menu',
    query: {
      viewMode: 'create',
      groupType: type
    }
  })
}

function createNewGroup() {
  router.push({
    name: 'menu',
    query: {
      viewMode: 'create-group',
      groupType: currentGroupType.value
    }
  })
}

// Action handlers
function handleModify() {
  console.log('Modify group')
}

function handleDelete() {
  console.log('Delete group')
}

function handleAddResponse() {
  console.log('Add allowed response')
}

// Lifecycle
onMounted(async () => {
  try {
    // Ensure we have groups loaded
    if (inquiryGroupsStore.inquiryGroups.length === 0) {
      // You might want to load groups here if not already loaded
      // await inquiryGroupsStore.fetchAllGroups()
    }

    // Ensure we have inquiries loaded
    if (inquiriesStore.inquiries.length === 0) {
      // await inquiriesStore.fetchAll()
    }
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    isLoading.value = false
  }
})

watch(() => route.params.slug, async () => {
  isLoading.value = false
})
</script>

<style lang="scss" scoped>
.inquiry-group-page {
    width: 100%;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

                                          /* Enhanced Breadcrumb */
                                          .breadcrumb-bar {
                                              background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                                              padding: 20px 0;
                                              box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
                                              border-bottom: 3px solid var(--color-primary, #2196f3);

                                              .breadcrumb-container {
                                                  max-width: 1600px;
                                                  margin: 0 auto;
                                                  padding: 0 30px;
                                                  display: flex;
                                                  align-items: center;
                                                  gap: 15px;

                                                  .breadcrumb-home {
                                                      color: white;
                                                      font-weight: 600;
                                                      font-size: 16px;
                                                      padding: 10px 15px;
                                                      background: rgba(255, 255, 255, 0.1);
                                                      border-radius: 10px;
                                                      transition: all 0.3s ease;

                                                      &:hover {
                                                          background: rgba(255, 255, 255, 0.2);
                                                          transform: translateY(-2px);
                                                      }

                                                      .breadcrumb-label {
                                                          margin-left: 8px;
                                                      }
                                                  }

                                                  .breadcrumb-separator {
                                                      color: rgba(255, 255, 255, 0.6);
                                                      margin: 0 5px;
                                                      font-weight: 300;
                                                      font-size: 20px;
                                                  }

                                                  .breadcrumb-item {
                                                      color: rgba(255, 255, 255, 0.9);
                                                      font-weight: 500;
                                                      font-size: 16px;
                                                      padding: 10px 15px;
                                                      background: rgba(255, 255, 255, 0.05);
                                                      border-radius: 10px;
                                                      transition: all 0.3s ease;

                                                      &:hover {
                                                          background: rgba(255, 255, 255, 0.15);
                                                          transform: translateY(-2px);
                                                      }

                                                      .breadcrumb-item-content {
                                                          display: flex;
                                                          align-items: center;
                                                          gap: 8px;
                                                      }
                                                  }

                                                  .breadcrumb-current {
                                                      color: white;
                                                      font-weight: 700;
                                                      font-size: 18px;
                                                      margin-left: 5px;

                                                      .breadcrumb-current-content {
                                                          display: flex;
                                                          align-items: center;
                                                          gap: 12px;
                                                          padding: 12px 20px;
                                                          background: rgba(255, 255, 255, 0.15);
                                                          border-radius: 12px;
                                                          border-left: 4px solid var(--color-primary, #2196f3);
                                                      }

                                                      .inquiry-count {
                                                          font-weight: 400;
                                                          font-size: 14px;
                                                          opacity: 0.9;
                                                          margin-left: 10px;
                                                      }
                                                  }

                                                  .breadcrumb-icon {
                                                      width: 20px;
                                                      height: 20px;
                                                      display: flex;
                                                      align-items: center;
                                                      justify-content: center;
                                                  }
                                              }
                                          }

                                          /* Enhanced White Separation Line */
                                          .separation-line {
                                              height: 2px;
                                              background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
                                              margin: 0;
                                              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                                          }

                                          /* Enhanced Group Header */
                                          .group-header {
                                              padding: 40px 30px 25px;
                                              background: white;
                                              border-radius: 0 0 30px 30px;
                                              box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
                                              margin-bottom: 30px;

                                              .header-left {
                                                  display: flex;
                                                  align-items: center;
                                                  gap: 25px;
                                                  max-width: 1600px;
                                                  margin: 0 auto;
                                              }

                                              .group-icon-badge {
                                                  width: 80px;
                                                  height: 80px;
                                                  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                  border-radius: 20px;
                                                  display: flex;
                                                  align-items: center;
                                                  justify-content: center;
                                                  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);

                                                  .group-icon {
                                                      width: 40px;
                                                      height: 40px;
                                                      color: white;
                                                  }
                                              }

                                              .group-title-section {
                                                  flex: 1;

                                                  .group-title {
                                                      font-size: 42px;
                                                      font-weight: 800;
                                                      margin: 0;
                                                      color: #2c3e50;
                                                      line-height: 1.1;
                                                      letter-spacing: -0.5px;
                                                  }

                                                  .group-subtitle {
                                                      margin-top: 15px;

                                                      p {
                                                          color: #5d6d7e;
                                                          font-size: 18px;
                                                          line-height: 1.5;
                                                          max-width: 800px;
                                                          margin: 0 0 15px 0;
                                                      }

                                                      .inquiry-count-badge {
                                                          background: linear-gradient(135deg, #00b09b, #96c93d);
                                                          color: white;
                                                          padding: 8px 18px;
                                                          border-radius: 20px;
                                                          font-weight: 600;
                                                          font-size: 15px;
                                                          box-shadow: 0 4px 15px rgba(0, 176, 155, 0.3);
                                                      }

                                                      .groups-count-badge {
                                                          background: linear-gradient(135deg, #667eea, #764ba2);
                                                          color: white;
                                                          padding: 8px 18px;
                                                          border-radius: 20px;
                                                          font-weight: 600;
                                                          font-size: 15px;
                                                          box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                                                      }
                                                  }
                                              }
                                          }

                                          /* Enhanced Type Overview */
                                          .type-overview {
                                              max-width: 1600px;
                                              margin: 0 auto;
                                              padding: 0 30px 40px;

                                              .type-info-card {
                                                  background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                                                  border-radius: 30px;
                                                  padding: 50px;
                                                  color: white;
                                                  display: flex;
                                                  align-items: center;
                                                  gap: 40px;
                                                  margin-bottom: 50px;
                                                  box-shadow: 0 15px 50px rgba(106, 17, 203, 0.4);
                                                  position: relative;
                                                  overflow: hidden;

                                                  &::before {
                                                      content: '';
                                                      position: absolute;
                                                      top: -50%;
                                                      right: -50%;
                                                      width: 200%;
                                                      height: 200%;
                                                      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
                                                      background-size: 30px 30px;
                                                      opacity: 0.2;
                                                  }

                                                  .type-icon-large {
                                                      width: 100px;
                                                      height: 100px;
                                                      background: rgba(255, 255, 255, 0.2);
                                                      border-radius: 25px;
                                                      display: flex;
                                                      align-items: center;
                                                      justify-content: center;
                                                      font-size: 50px;
                                                      backdrop-filter: blur(10px);
                                                      border: 2px solid rgba(255, 255, 255, 0.3);
                                                      flex-shrink: 0;
                                                  }

                                                  .type-content {
                                                      flex: 1;

                                                      h2 {
                                                          font-size: 48px;
                                                          margin: 0 0 15px 0;
                                                          font-weight: 800;
                                                      }

                                                      .type-description {
                                                          font-size: 20px;
                                                          opacity: 0.95;
                                                          line-height: 1.6;
                                                          margin: 0 0 25px 0;
                                                      }

                                                      .type-meta {
                                                          display: flex;
                                                          gap: 30px;

                                                          .type-groups-count,
                                                          .type-total-inquiries {
                                                              display: flex;
                                                              align-items: center;
                                                              gap: 10px;
                                                              font-size: 16px;
                                                              background: rgba(255, 255, 255, 0.15);
                                                              padding: 10px 20px;
                                                              border-radius: 12px;
                                                              backdrop-filter: blur(5px);

                                                              .count {
                                                                  font-weight: 700;
                                                                  font-size: 22px;
                                                              }
                                                          }
                                                      }
                                                  }
                                              }
                                          }

                                          /* Enhanced Groups Grid */
                                          .groups-section {
                                              .section-header {
                                                  display: flex;
                                                  justify-content: space-between;
                                                  align-items: center;
                                                  margin-bottom: 30px;

                                                  h3 {
                                                      font-size: 28px;
                                                      font-weight: 700;
                                                      color: #2c3e50;
                                                      margin: 0;
                                                  }

                                                  .section-actions {
                                                      .create-button {
                                                          background: linear-gradient(135deg, #00b09b, #96c93d);
                                                          color: white;
                                                          font-weight: 600;
                                                          padding: 12px 24px;
                                                          border: none;
                                                          border-radius: 12px;
                                                          box-shadow: 0 4px 15px rgba(0, 176, 155, 0.3);

                                                          &:hover {
                                                              transform: translateY(-2px);
                                                              box-shadow: 0 6px 20px rgba(0, 176, 155, 0.4);
                                                          }
                                                      }
                                                  }
                                              }
                                          }

                                          .groups-grid {
                                              display: grid;
                                              grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                                              gap: 30px;
                                          }

                                          .group-vignette {
                                              background: white;
                                              border-radius: 20px;
                                              overflow: hidden;
                                              cursor: pointer;
                                              transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                                              box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                                              border: 1px solid rgba(0, 0, 0, 0.05);

                                              &:hover {
                                                  transform: translateY(-10px);
                                                  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
                                                  border-color: var(--color-primary, #2196f3);

                                                  .vignette-cover img {
                                                      transform: scale(1.05);
                                                  }
                                              }

                                              .vignette-cover {
                                                  height: 180px;
                                                  overflow: hidden;
                                                  position: relative;

                                                  img {
                                                      width: 100%;
                                                      height: 100%;
                                                      object-fit: cover;
                                                      transition: transform 0.5s ease;
                                                  }

                                                  .vignette-cover-overlay {
                                                      position: absolute;
                                                      bottom: 0;
                                                      left: 0;
                                                      right: 0;
                                                      height: 60px;
                                                      background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
                                                  }
                                              }

                                              .vignette-content {
                                                  padding: 25px;
                                                  position: relative;

                                                  .vignette-icon-badge {
                                                      position: absolute;
                                                      top: -25px;
                                                      left: 25px;
                                                      width: 50px;
                                                      height: 50px;
                                                      background: linear-gradient(135deg, #667eea, #764ba2);
                                                      border-radius: 15px;
                                                      display: flex;
                                                      align-items: center;
                                                      justify-content: center;
                                                      color: white;
                                                      font-size: 24px;
                                                      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
                                                      border: 3px solid white;
                                                  }

                                                  h4 {
                                                      font-size: 20px;
                                                      font-weight: 700;
                                                      margin: 15px 0 15px 0;
                                                      color: #2c3e50;
                                                      line-height: 1.3;
                                                  }

                                                  .vignette-description {
                                                      color: #7f8c8d;
                                                      font-size: 15px;
                                                      line-height: 1.5;
                                                      margin-bottom: 20px;
                                                      display: -webkit-box;
                                                      -webkit-line-clamp: 3;
                                                      -webkit-box-orient: vertical;
                                                      overflow: hidden;
                                                  }

                                                  .vignette-stats {
                                                      display: flex;
                                                      gap: 20px;
                                                      margin-bottom: 20px;

                                                      .stat-item {
                                                          display: flex;
                                                          align-items: center;
                                                          gap: 6px;
                                                          font-size: 14px;

                                                          .stat-icon {
                                                              opacity: 0.8;
                                                          }

                                                          .stat-value {
                                                              font-weight: 700;
                                                              color: #2c3e50;
                                                          }

                                                          .stat-label {
                                                              color: #95a5a6;
                                                          }
                                                      }
                                                  }

                                                  .vignette-footer {
                                                      .view-group-button {
                                                          width: 100%;
                                                          justify-content: center;
                                                          background: linear-gradient(135deg, #667eea, #764ba2);
                                                          color: white;
                                                          border: none;
                                                          padding: 12px;
                                                          border-radius: 10px;
                                                          font-weight: 600;
                                                          transition: all 0.3s ease;

                                                          &:hover {
                                                              background: linear-gradient(135deg, #764ba2, #667eea);
                                                              transform: translateY(-2px);
                                                          }
                                                      }
                                                  }
                                              }
                                          }

                                          .empty-groups-state {
                                              text-align: center;
                                              padding: 60px 30px;
                                              background: white;
                                              border-radius: 20px;
                                              border: 2px dashed #e0e6ed;

                                              .empty-state-icon {
                                                  font-size: 60px;
                                                  margin-bottom: 20px;
                                                  opacity: 0.3;
                                              }

                                              h3 {
                                                  font-size: 24px;
                                                  color: #2c3e50;
                                                  margin: 0 0 10px 0;
                                              }

                                              p {
                                                  color: #7f8c8d;
                                                  margin-bottom: 25px;
                                              }
                                          }

                                          /* Rest of the styles remain similar but with enhanced spacing */
                                          /* ... (other styles remain similar but with increased padding/margins) ... */

                                          /* Responsive Design */
                                          @media (max-width: 768px) {
                                              .breadcrumb-bar {
                                                  padding: 15px 0;

                                                  .breadcrumb-container {
                                                      padding: 0 15px;
                                                      gap: 8px;
                                                      flex-wrap: wrap;

                                                      .breadcrumb-home,
                                                      .breadcrumb-item {
                                                          font-size: 14px;
                                                          padding: 8px 12px;
                                                      }

                                                      .breadcrumb-current {
                                                          font-size: 16px;

                                                          .breadcrumb-current-content {
                                                              padding: 10px 15px;
                                                          }
                                                      }
                                                  }
                                              }

                                              .group-header {
                                                  padding: 30px 15px 20px;

                                                  .group-icon-badge {
                                                      width: 60px;
                                                      height: 60px;
                                                      border-radius: 15px;

                                                      .group-icon {
                                                          width: 30px;
                                                          height: 30px;
                                                      }
                                                  }

                                                  .group-title {
                                                      font-size: 28px;
                                                  }
                                              }

                                              .type-overview {
                                                  padding: 0 15px 30px;

                                                  .type-info-card {
                                                      flex-direction: column;
                                                      text-align: center;
                                                      padding: 30px 20px;
                                                      gap: 25px;

                                                      .type-icon-large {
                                                          width: 80px;
                                                          height: 80px;
                                                          font-size: 40px;
                                                      }

                                                      h2 {
                                                          font-size: 32px;
                                                      }

                                                      .type-meta {
                                                          flex-direction: column;
                                                          gap: 15px;
                                                      }
                                                  }
                                              }

                                              .groups-grid {
                                                  grid-template-columns: 1fr;
                                                  gap: 20px;
                                              }
                                          }
</style>
