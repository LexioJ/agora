<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud Contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { t } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import InquiryCreateDlg from '../components/Create/InquiryCreateDlg.vue'
import InquiryGroupCreateDlg from '../components/Create/InquiryGroupCreateDlg.vue'
import InquiryGroupEditViewForm from '../components/InquiryGroup/InquiryGroupEditViewForm.vue'
import { AgoraAppIcon } from '../components/AppIcons/index.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import { useInquiryGroupStore } from '../stores/inquiryGroup.ts'
import { getInquiryItemData } from '../helpers/modules/InquiryHelper.ts'
import type { InquiryGroup, InquiryGroupType } from '../types/inquiryGroup.ts'
import type { Inquiry } from '../types/index.ts'

const route = useRoute()
const router = useRouter()
const sessionStore = useSessionStore()
const inquiriesStore = useInquiriesStore()
const inquiryGroupsStore = useInquiryGroupsStore()
const inquiryGroupStore = useInquiryGroupStore()

// States
const selectedInquiryGroup = ref<InquiryGroup | null>(null)
const createInquiryDlgToggle = ref(false)
const createGroupDlgToggle = ref(false)
const editGroupDlgToggle = ref(false)
const selectedInquiryType = ref<string | null>(null)
const selectedInquiryGroupTypeForCreation = ref<string | null>(null)
const selectedGroups = ref<number[]>([])
const availableGroups = ref<InquiryGroup[]>([])

// Get data from stores
const inquiryGroups = computed(() => inquiryGroupsStore.inquiryGroups)
const currentInquiryGroup = computed(() => inquiryGroupStore.inquiryGroup)
const inquiryGroupTypes = computed(() => sessionStore.appSettings.inquiryGroupTypeTab || [])
const inquiryTypes = computed(() => sessionStore.appSettings.inquiryTypeTab || [])

// Get the default inquiry group type (sort_order = 1)
const defaultInquiryGroupType = computed(() => {
  return inquiryGroupTypes.value.find(type => type.sort_order === 1) || inquiryGroupTypes.value[0]
})

// Get inquiry group types by family
const inquiryGroupTypesByFamily = computed(() => {
  const families: Record<string, InquiryGroupType[]> = {}
  inquiryGroupTypes.value.forEach(type => {
    if (!families[type.family]) {
      families[type.family] = []
    }
    families[type.family].push(type)
  })
  return families
})

// Get root groups of default type (parent_id = null)
const rootInquiryGroups = computed(() => {
  if (!defaultInquiryGroupType.value) return []
  return inquiryGroups.value.filter(group => 
    group.parent_id === null && group.type === defaultInquiryGroupType.value.type
  )
})

// Get child groups by type for display
const childGroupsByType = computed(() => {
  const groupsByType: Record<string, InquiryGroup[]> = {}
  
  // Get all groups that have parent_id = null and are not the default type
  const childGroups = inquiryGroups.value.filter(group => 
    group.parent_id === null && group.type !== defaultInquiryGroupType.value?.type
  )
  
  childGroups.forEach(group => {
    const typeData = getGroupTypeData(group.type)
    if (!groupsByType[group.type]) {
      groupsByType[group.type] = []
    }
    groupsByType[group.type].push(group)
  })
  
  return groupsByType
})

// Check if we're in inquiry group detail view based on route
const isInquiryGroupView = computed(() => {
  return route.name === 'group' && route.params.id
})

// Get current inquiry group from route
const currentInquiryGroupId = computed(() => {
  if (!isInquiryGroupView.value) return null
  return parseInt(route.params.id as string)
})

// Get child inquiry groups for current group
const childGroups = computed(() => {
  if (!currentInquiryGroup.value.id) return []
  return inquiryGroups.value.filter(group => 
    group.parent_id === currentInquiryGroup.value.id
  )
})

// Get child groups by type for current group
const childGroupsByTypeForCurrent = computed(() => {
  const groupsByType: Record<string, InquiryGroup[]> = {}
  
  childGroups.value.forEach(group => {
    if (!groupsByType[group.type]) {
      groupsByType[group.type] = []
    }
    groupsByType[group.type].push(group)
  })
  
  return groupsByType
})

// Get inquiries in current group
const inquiriesInGroup = computed(() => {
  if (!currentInquiryGroup.value.id) return []
  const inquiryIds = currentInquiryGroup.value.inquiryIds || []
  return inquiriesStore.inquiries.filter(inquiry => 
    inquiryIds.includes(inquiry.id)
  )
})

// Get inquiries by type
const inquiriesByType = computed(() => {
  const grouped: Record<string, Inquiry[]> = {}
  inquiriesInGroup.value.forEach(inquiry => {
    const type = inquiry.type
    if (!grouped[type]) grouped[type] = []
    grouped[type].push(inquiry)
  })
  return grouped
})

// Get inquiry group type data
const currentInquiryGroupTypeData = computed(() => {
  if (!currentInquiryGroupId.value) return defaultInquiryGroupType.value
  return inquiryGroupTypes.value.find(
    type => type.type === currentInquiryGroup.value.type
  )
})

// Get allowed response types for current group
const allowedResponseTypes = computed(() => {
  if (!currentInquiryGroupTypeData.value?.allowed_response) return []
  const allowed = currentInquiryGroupTypeData.value.allowed_response
  const responseTypes = Array.isArray(allowed) ? allowed : [allowed]
  return inquiryGroupTypes.value.filter(type => 
    responseTypes.includes(type.type)
  )
})

// Get inquiry types allowed for current group
const allowedInquiryTypes = computed(() => {
  if (!currentInquiryGroupTypeData.value?.allowed_inquiry_types) return []
  const allowed = currentInquiryGroupTypeData.value.allowed_inquiry_types
  const inquiryTypeList = Array.isArray(allowed) ? allowed : [allowed]
  return inquiryTypes.value.filter(type => 
    inquiryTypeList.includes(type.inquiry_type))
  })

// Navigation sections for detail view
const sections = ref([
  { id: 'overview', title: 'Overview', icon: '📌' },
  { id: 'child-groups', title: 'Child Groups', icon: '🧩' },
  { id: 'inquiries', title: 'Inquiries', icon: '📄' },
  { id: 'settings', title: 'Settings', icon: '⚙️' }
])

const activeSection = ref('overview')

// Load data
onMounted(() => {
  loadData()
})

watch(() => route.params.id, () => {
  loadData()
})

async function loadData() {
  if (currentInquiryGroupId.value) {
    // Load specific inquiry group
    await inquiryGroupStore.load(currentInquiryGroupId.value)
    selectedInquiryGroup.value = currentInquiryGroup.value
  } else {
    // Reset store for new view
    inquiryGroupStore.reset()
    selectedInquiryGroup.value = null
  }
  
  // Load all inquiry groups for selection
  availableGroups.value = inquiryGroups.value
}

// View inquiry group details
function viewInquiryGroup(groupId: number) {
  router.push({
    name: 'group',
    params: { id: groupId }
  })
}

// View inquiry details
function viewInquiry(inquiryId: number) {
  router.push({
    name: 'inquiry',
    params: { id: inquiryId }
  })
}

// Back to inquiry groups list
function backToGroups() {
  router.push({
    name: 'menu',
    query: { view: 'inquiry-groups' }
  })
}

// Create new inquiry
function createInquiry(inquiryType: string) {
  selectedInquiryType.value = inquiryType
  createInquiryDlgToggle.value = true
}

// Create new child group
function createChildGroup(groupType?: string) {
  selectedInquiryGroupTypeForCreation.value = groupType || null
  createGroupDlgToggle.value = true
}

// Edit current inquiry group
function editInquiryGroup() {
  editGroupDlgToggle.value = true
}

// Delete current inquiry group
async function deleteInquiryGroup() {
  if (confirm(t('inquiries', 'Are you sure you want to delete this inquiry group?'))) {
    await inquiryGroupStore.deleteGroup()
    backToGroups()
  }
}

// Add allowed response type
function addAllowedResponse() {
  selectedInquiryGroupTypeForCreation.value = 'allowed_response'
  createGroupDlgToggle.value = true
}

// Handle inquiry creation
function inquiryAdded(payload: { id: number; title: string }) {
  createInquiryDlgToggle.value = false
  selectedInquiryType.value = null
  inquiriesStore.load(false)
  
  // Navigate to the new inquiry
  router.push({
    name: 'inquiry',
    params: { id: payload.id }
  })
}

// Handle group creation
async function inquiryGroupAdded(payload: { id: number; title: string }) {
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = null
  
  // If creating a child group, reload current group
  if (currentInquiryGroup.value.id) {
    await inquiryGroupStore.load(currentInquiryGroup.value.id)
  }
  
  // Navigate to the new group if not child
  if (!currentInquiryGroup.value.id) {
    router.push({
      name: 'group',
      params: { id: payload.id }
    })
  }
}

// Handle group update selection
function handleGroupUpdate(groups: number[]) {
  selectedGroups.value = groups
}

// Close group dialog
function handleCloseGroupDialog() {
  createGroupDlgToggle.value = false
  selectedInquiryGroupTypeForCreation.value = null
}

// Get group type data
function getGroupTypeData(groupType: string) {
  return inquiryGroupTypes.value.find(type => type.type === groupType) || {}
}

// Get inquiry type data
function getInquiryTypeData(inquiryType: string) {
  return inquiryTypes.value.find(type => type.inquiry_type === inquiryType) || {}
}

// Get color for group card
function getGroupColor(index: number) {
  const colors = ['blue', 'green', 'purple', 'orange', 'red']
  return colors[index % colors.length]
}

// Get icon for group type
function getGroupIcon(groupType: string) {
  const typeData = getGroupTypeData(groupType)
  return typeData.icon || '🏛️'
}

// Get label for group type
function getGroupLabel(groupType: string) {
  const typeData = getGroupTypeData(groupType)
  return typeData.label || groupType
}

// Get description for group type
function getGroupDescription(groupType: string) {
  const typeData = getGroupTypeData(groupType)
  return typeData.description || ''
}

// Get family label
function getFamilyLabel(family: string) {
  // Simple mapping, you might want to add proper translations
  const familyLabels: Record<string, string> = {
    'assembly': 'Assemblies',
    'working_group': 'Working Groups',
    'commission': 'Commissions',
    'chapter': 'Chapters',
    'deliberation': 'Deliberations',
    'default': 'Inquiry Groups'
  }
  return familyLabels[family] || family
}
</script>

<template>
  <NcAppContent class="inquiry-groups">
    <!-- Inquiry Groups List View (when no ID) -->
    <template v-if="!currentInquiryGroupId" #title>
      <div class="page-title">
        <span class="title-icon">{{ defaultInquiryGroupType?.icon || '🏛️' }}</span>
        {{ defaultInquiryGroupType?.label || t('inquiries', 'Inquiry Groups') }}
      </div>
    </template>
    
    <!-- Inquiry Group Detail View -->
    <template v-else #title>
      <div class="page-title">
        <span class="title-icon">
          {{ getGroupIcon(currentInquiryGroup.type) }}
        </span>
        {{ getGroupLabel(currentInquiryGroup.type) }}: {{ currentInquiryGroup.name || currentInquiryGroup.title }}
      </div>
    </template>

    <!-- Header Actions -->
    <div class="header-actions">
      <NcButton 
        v-if="currentInquiryGroupId" 
        class="back-button" 
        @click="backToGroups"
      >
        <span class="back-button__icon">←</span>
        {{ t('inquiries', 'Back to') }} {{ getGroupLabel(defaultInquiryGroupType?.type || 'default') }}
      </NcButton>

      <div class="action-buttons">
        <NcButton 
          v-if="currentInquiryGroupId && inquiryGroupStore.permissions.edit"
          type="primary"
          @click="editInquiryGroup"
        >
          {{ t('inquiries', 'Modify') }}
        </NcButton>
        <NcButton 
          v-if="currentInquiryGroupId && inquiryGroupStore.permissions.delete"
          type="error"
          @click="deleteInquiryGroup"
        >
          {{ t('inquiries', 'Delete') }}
        </NcButton>
        <NcButton 
          v-if="currentInquiryGroupId && inquiryGroupStore.permissions.addInquiries && allowedInquiryTypes.length > 0"
          type="primary"
          @click="createInquiry(allowedInquiryTypes[0].inquiry_type)"
        >
          {{ t('inquiries', 'Create Inquiry') }}
        </NcButton>
        <NcButton 
          v-if="currentInquiryGroupId && inquiryGroupStore.permissions.addInquiries && allowedResponseTypes.length > 0"
          type="secondary"
          @click="addAllowedResponse"
        >
          {{ t('inquiries', 'Add Allowed Response') }}
        </NcButton>
      </div>
    </div>

    <!-- Inquiry Groups List View (no ID) -->
    <div v-if="!currentInquiryGroupId" class="inquiry-groups-list-view">
      <!-- Main Inquiry Group Type Section -->
      <div class="main-inquiry-group-section">
        <div class="type-hero-section">
          <div class="type-hero-content">
            <div class="type-icon-large">{{ defaultInquiryGroupType?.icon || '🏛️' }}</div>
            <h1 class="type-hero-title">{{ defaultInquiryGroupType?.label || t('inquiries', 'Inquiry Groups') }}</h1>
            <p class="type-hero-description">
              {{ defaultInquiryGroupType?.description || t('inquiries', 'Organize and manage your inquiries in groups') }}
            </p>
          </div>
        </div>

        <!-- Root Groups of Default Type -->
        <div v-if="rootInquiryGroups.length > 0" class="root-groups-section">
          <h2 class="section-title">{{ getGroupLabel(defaultInquiryGroupType?.type || 'default') }}</h2>
          <p class="section-description">
            {{ t('inquiries', 'Main groups for organizing inquiries and processes') }}
          </p>
          
          <div class="root-groups-grid">
            <div
              v-for="(group, index) in rootInquiryGroups"
              :key="group.id"
              class="root-group-card"
              :class="`root-group-card--${getGroupColor(index)}`"
              @click="viewInquiryGroup(group.id)"
            >
              <div class="card-header">
                <div class="card-icon">
                  {{ getGroupIcon(group.type) }}
                </div>
                <h3 class="card-title">{{ group.name || group.title }}</h3>
              </div>
              
              <p class="card-description">{{ group.description }}</p>
              
              <div class="card-stats">
                <div class="stat">
                  <span class="stat-icon">🧩</span>
                  <span class="stat-label">{{ t('inquiries', 'Child Groups:') }}</span>
                  <span class="stat-value">
                    {{ inquiryGroups.filter(g => g.parent_id === group.id).length }}
                  </span>
                </div>
                <div class="stat">
                  <span class="stat-icon">📄</span>
                  <span class="stat-label">{{ t('inquiries', 'Inquiries:') }}</span>
                  <span class="stat-value">{{ group.inquiryIds?.length || 0 }}</span>
                </div>
              </div>
              
              <div class="card-actions">
                <NcButton type="primary" class="view-button">
                  {{ t('inquiries', 'View >') }}
                </NcButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Child Groups by Type -->
        <div 
          v-for="(typeGroups, typeKey) in childGroupsByType" 
          :key="typeKey"
          class="child-groups-by-type-section"
        >
          <div class="type-section-header">
            <h2 class="type-section-title">
              <span class="type-section-icon">{{ getGroupIcon(typeKey) }}</span>
              {{ getGroupLabel(typeKey) }}
            </h2>
            <p class="type-section-description">{{ getGroupDescription(typeKey) }}</p>
          </div>
          
          <div class="type-groups-grid">
            <div
              v-for="group in typeGroups"
              :key="group.id"
              class="type-group-card"
              @click="viewInquiryGroup(group.id)"
            >
              <div class="type-group-icon">{{ getGroupIcon(group.type) }}</div>
              <h3 class="type-group-title">{{ group.name || group.title }}</h3>
              <p class="type-group-description">{{ group.description }}</p>
              <div class="type-group-count">
                {{ group.inquiryIds?.length || 0 }} {{ t('inquiries', 'inquiries') }}
              </div>
            </div>
            
            <!-- Add new group card -->
            <div 
              class="type-group-card add-new-card"
              @click="createChildGroup(typeKey)"
            >
              <div class="add-new-icon">+</div>
              <h3 class="add-new-title">{{ t('inquiries', 'Create New') }}</h3>
              <p class="add-new-description">
                {{ t('inquiries', 'Add a new') }} {{ getGroupLabel(typeKey).toLowerCase() }}
              </p>
            </div>
          </div>
        </div>

        <!-- Empty state for root groups -->
        <div v-if="rootInquiryGroups.length === 0 && Object.keys(childGroupsByType).length === 0" class="empty-state">
          <NcEmptyContent 
            :name="t('inquiries', 'No inquiry groups created')"
            :description="t('inquiries', 'Create your first inquiry group to start organizing inquiries')"
          >
            <template #icon>
              <AgoraAppIcon />
            </template>
          </NcEmptyContent>
          
          <div class="create-first-actions">
            <NcButton type="primary" @click="createChildGroup(defaultInquiryGroupType?.type)">
              {{ t('inquiries', 'Create First') }} {{ getGroupLabel(defaultInquiryGroupType?.type || 'default') }}
            </NcButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Inquiry Group Detail View -->
    <div v-else class="inquiry-group-detail-view">
      <!-- Group Header -->
      <div class="inquiry-group-hero">
        <div class="inquiry-group-hero-content">
          <h1 class="inquiry-group-title">
            <span class="inquiry-group-icon">
              {{ getGroupIcon(currentInquiryGroup.type) }}
            </span>
            {{ currentInquiryGroup.name || currentInquiryGroup.title }}
          </h1>
          <p class="inquiry-group-description">{{ currentInquiryGroup.description }}</p>
          
          <div class="inquiry-group-meta">
            <span class="meta-item">
              <strong>{{ getGroupLabel(currentInquiryGroup.type) }}</strong>
              ({{ inquiriesInGroup.length }} {{ t('inquiries', 'inquiries') }})
            </span>
          </div>

          <!-- Owner/Admin actions (visible on hover) -->
          <div v-if="inquiryGroupStore.permissions.edit" class="inquiry-group-admin-actions">
            <NcButton type="primary" @click="editInquiryGroup">
              {{ t('inquiries', 'Modify') }}
            </NcButton>
            <NcButton type="error" @click="deleteInquiryGroup">
              {{ t('inquiries', 'Delete') }}
            </NcButton>
            <NcButton 
              v-if="allowedResponseTypes.length > 0"
              type="secondary"
              @click="addAllowedResponse"
            >
              {{ t('inquiries', 'Add Allowed Response') }}
            </NcButton>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="navigation-tabs">
        <div class="tabs-container">
          <div
            v-for="section in sections"
            :key="section.id"
            class="tab-item"
            :class="{ 'tab-item--active': activeSection === section.id }"
            @click="activeSection = section.id"
          >
            <span class="tab-icon">{{ section.icon }}</span>
            <span class="tab-label">{{ section.title }}</span>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="content-area">
        <!-- Overview Section -->
        <div v-if="activeSection === 'overview'" class="content-section">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-value">{{ childGroups.length }}</div>
              <div class="stat-label">{{ t('inquiries', 'Child Groups') }}</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ inquiriesInGroup.length }}</div>
              <div class="stat-label">{{ t('inquiries', 'Inquiries') }}</div>
            </div>
            <div class="stat-card">
              <div class="stat-value">{{ getGroupLabel(currentInquiryGroup.type) }}</div>
              <div class="stat-label">{{ t('inquiries', 'Group Type') }}</div>
            </div>
          </div>

          <!-- Child Groups by Type Preview -->
          <div v-if="Object.keys(childGroupsByTypeForCurrent).length > 0" class="child-groups-by-type-preview">
            <h3 class="preview-title">{{ t('inquiries', 'Child Groups by Type') }}</h3>
            
            <div 
              v-for="(groups, typeKey) in childGroupsByTypeForCurrent" 
              :key="typeKey"
              class="child-type-section"
            >
              <h4 class="child-type-title">
                <span class="child-type-icon">{{ getGroupIcon(typeKey) }}</span>
                {{ getGroupLabel(typeKey) }} ({{ groups.length }})
              </h4>
              
              <div class="child-type-grid">
                <div
                  v-for="child in groups"
                  :key="child.id"
                  class="child-group-preview-card"
                  @click="viewInquiryGroup(child.id)"
                >
                  <div class="child-group-preview-icon">
                    {{ getGroupIcon(child.type) }}
                  </div>
                  <h5 class="child-group-preview-title">{{ child.name || child.title }}</h5>
                  <p class="child-group-preview-description">{{ child.description }}</p>
                  <div class="child-group-preview-count">
                    {{ child.inquiryIds?.length || 0 }} {{ t('inquiries', 'inquiries') }}
                  </div>
                </div>
                
                <!-- Add new child group -->
                <div 
                  class="child-group-preview-card add-new-preview-card"
                  @click="createChildGroup(typeKey)"
                >
                  <div class="add-new-preview-icon">+</div>
                  <h5 class="add-new-preview-title">{{ t('inquiries', 'Add New') }}</h5>
                  <p class="add-new-preview-description">
                    {{ t('inquiries', 'Create new') }} {{ getGroupLabel(typeKey).toLowerCase() }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Child Groups Section -->
        <div v-if="activeSection === 'child-groups'" class="content-section">
          <div class="section-header-with-action">
            <h3 class="section-title">{{ t('inquiries', 'Child Groups') }}</h3>
            <div class="child-group-actions">
              <NcButton 
                v-if="allowedResponseTypes.length > 0"
                type="secondary"
                @click="createChildGroup()"
              >
                {{ t('inquiries', 'Create Child Group') }}
              </NcButton>
              
              <!-- Quick create buttons for allowed response types -->
              <div v-if="allowedResponseTypes.length > 0" class="quick-create-buttons">
                <span class="quick-create-label">{{ t('inquiries', 'Quick create:') }}</span>
                <NcButton 
                  v-for="responseType in allowedResponseTypes"
                  :key="responseType.type"
                  type="tertiary"
                  size="small"
                  @click="createChildGroup(responseType.type)"
                >
                  {{ responseType.icon || '🧩' }} {{ responseType.label }}
                </NcButton>
              </div>
            </div>
          </div>
          
          <!-- Child Groups by Type -->
          <div 
            v-for="(groups, typeKey) in childGroupsByTypeForCurrent" 
            :key="typeKey"
            class="child-groups-type-section"
          >
            <h4 class="child-type-header">
              <span class="child-type-header-icon">{{ getGroupIcon(typeKey) }}</span>
              {{ getGroupLabel(typeKey) }} ({{ groups.length }})
            </h4>
            
            <div v-if="groups.length > 0" class="child-groups-list">
              <div
                v-for="child in groups"
                :key="child.id"
                class="child-group-item"
                @click="viewInquiryGroup(child.id)"
              >
                <div class="child-group-item-icon">
                  {{ getGroupIcon(child.type) }}
                </div>
                <div class="child-group-item-content">
                  <h4 class="child-group-item-title">{{ child.name || child.title }}</h4>
                  <p class="child-group-item-description">{{ child.description }}</p>
                  <div class="child-group-item-meta">
                    <span class="meta-item">{{ getGroupLabel(child.type) }}</span>
                    <span class="meta-item">•</span>
                    <span class="meta-item">{{ child.inquiryIds?.length || 0 }} inquiries</span>
                  </div>
                </div>
                <div class="child-group-item-arrow">→</div>
              </div>
            </div>
            
            <div v-else class="no-child-groups">
              <p>{{ t('inquiries', 'No child groups of this type yet') }}</p>
              <NcButton 
                type="tertiary"
                size="small"
                @click="createChildGroup(typeKey)"
              >
                {{ t('inquiries', 'Create first') }} {{ getGroupLabel(typeKey).toLowerCase() }}
              </NcButton>
            </div>
          </div>
          
          <!-- No child groups at all -->
          <div v-if="Object.keys(childGroupsByTypeForCurrent).length === 0" class="no-child-groups-section">
            <NcEmptyContent 
              :name="t('inquiries', 'No child groups')"
              :description="t('inquiries', 'Create child groups to organize inquiries')"
            />
            
            <div v-if="allowedResponseTypes.length > 0" class="create-child-group-options">
              <h4>{{ t('inquiries', 'Create your first child group:') }}</h4>
              <div class="child-type-options">
                <NcButton 
                  v-for="responseType in allowedResponseTypes"
                  :key="responseType.type"
                  type="secondary"
                  @click="createChildGroup(responseType.type)"
                >
                  <span class="option-icon">{{ responseType.icon || '🧩' }}</span>
                  {{ responseType.label }}
                </NcButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Inquiries Section -->
        <div v-if="activeSection === 'inquiries'" class="content-section">
          <div class="section-header-with-action">
            <h3 class="section-title">{{ t('inquiries', 'Inquiries in this Group') }}</h3>
            <NcButton 
              v-if="inquiryGroupStore.permissions.addInquiries && allowedInquiryTypes.length > 0"
              type="primary"
              @click="createInquiry(allowedInquiryTypes[0].inquiry_type)"
            >
              {{ t('inquiries', 'Create Inquiry') }}
            </NcButton>
          </div>

          <!-- Inquiries by Type -->
          <div v-for="(inquiries, type) in inquiriesByType" :key="type" class="inquiry-type-section">
            <h4 class="inquiry-type-title">
              {{ getInquiryTypeData(type)?.label || type }} ({{ inquiries.length }})
            </h4>
            <div class="inquiries-grid">
              <div
                v-for="inquiry in inquiries"
                :key="inquiry.id"
                class="inquiry-card"
                @click="viewInquiry(inquiry.id)"
              >
                <div class="inquiry-card-header">
                  <div class="inquiry-icon">
                    {{ getInquiryTypeData(inquiry.type)?.icon || '📄' }}
                  </div>
                  <h5 class="inquiry-title">{{ inquiry.title }}</h5>
                </div>
                <p class="inquiry-description">{{ inquiry.description }}</p>
                <div class="inquiry-status">
                  <span :class="`status-badge status-${inquiry.status?.inquiryStatus || 'draft'}`">
                    {{ inquiry.status?.inquiryStatus || 'Draft' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Create Inquiry Cards -->
          <div v-if="allowedInquiryTypes.length > 0" class="creation-section">
            <h4 class="creation-title">{{ t('inquiries', 'Create New Inquiry') }}</h4>
            <div class="creation-cards">
              <div
                v-for="inquiryType in allowedInquiryTypes"
                :key="inquiryType.inquiry_type"
                class="creation-card"
                @click="createInquiry(inquiryType.inquiry_type)"
              >
                <div class="creation-icon">
                  {{ getInquiryItemData(inquiryType).icon }}
                </div>
                <div class="creation-content">
                  <h5>{{ getInquiryItemData(inquiryType).label }}</h5>
                  <p>{{ getInquiryItemData(inquiryType).description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Sidebar - Inquiry Types -->
        <div class="right-sidebar">
          <div class="sidebar-section">
            <h4 class="sidebar-title">{{ t('inquiries', 'Inquiry Types in this Group') }}</h4>
            <div class="sidebar-list">
              <div
                v-for="inquiryType in allowedInquiryTypes"
                :key="inquiryType.inquiry_type"
                class="sidebar-item"
              >
                <span class="sidebar-icon">
                  {{ getInquiryItemData(inquiryType).icon }}
                </span>
                <span class="sidebar-label">
                  {{ getInquiryItemData(inquiryType).label }}
                </span>
                <span class="sidebar-count">
                  ({{ inquiriesByType[inquiryType.inquiry_type]?.length || 0 }})
                </span>
              </div>
            </div>
          </div>
          
          <!-- Allowed Response Types -->
          <div v-if="allowedResponseTypes.length > 0" class="sidebar-section">
            <h4 class="sidebar-title">{{ t('inquiries', 'Allowed Response Types') }}</h4>
            <div class="sidebar-list">
              <div
                v-for="responseType in allowedResponseTypes"
                :key="responseType.type"
                class="sidebar-item"
              >
                <span class="sidebar-icon">
                  {{ responseType.icon || '🧩' }}
                </span>
                <span class="sidebar-label">
                  {{ responseType.label }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Group Info -->
          <div class="sidebar-section">
            <h4 class="sidebar-title">{{ t('inquiries', 'Group Information') }}</h4>
            <div class="sidebar-info">
              <div class="info-item">
                <span class="info-label">{{ t('inquiries', 'Type:') }}</span>
                <span class="info-value">{{ getGroupLabel(currentInquiryGroup.type) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">{{ t('inquiries', 'Created:') }}</span>
                <span class="info-value">{{ new Date(currentInquiryGroup.created * 1000).toLocaleDateString() }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">{{ t('inquiries', 'Owner:') }}</span>
                <span class="info-value">{{ currentInquiryGroup.owner?.displayName }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialogs -->
    <InquiryCreateDlg
      v-if="createInquiryDlgToggle"
      :inquiry-type="selectedInquiryType"
      :inquiry-group-id="currentInquiryGroup.id"
      @added="inquiryAdded"
      @close="createInquiryDlgToggle = false"
    />

    <InquiryGroupCreateDlg
      v-if="createGroupDlgToggle"
      :inquiry-group-type="selectedInquiryGroupTypeForCreation"
      :selected-groups="selectedGroups"
      :available-groups="availableGroups"
      :parent-group-id="currentInquiryGroup.id"
      @close="handleCloseGroupDialog"
      @added="inquiryGroupAdded"
      @update:selected-groups="handleGroupUpdate"
    />

    <InquiryGroupEditViewForm
      v-if="editGroupDlgToggle && currentInquiryGroup.id"
      :inquiry-group="currentInquiryGroup"
      @close="editGroupDlgToggle = false"
      @updated="inquiryGroupStore.load(currentInquiryGroup.id)"
    />
  </NcAppContent>
</template>

<style lang="scss" scoped>
.inquiry-groups {
  .area__main {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
}

// Page Title
.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 24px;
  font-weight: 700;
  
  .title-icon {
    font-size: 28px;
  }
}

// Header Actions
.header-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 0;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--color-border);

  .back-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 18px;
    color: var(--color-text-lighter);
    transition: all 0.2s ease;

    &:hover {
      background: var(--color-background-hover);
      border-color: var(--color-primary-element);
    }

    &__icon {
      font-size: 18px;
      font-weight: 600;
    }
  }

  .action-buttons {
    display: flex;
    gap: 12px;
  }
}

// Main Inquiry Group Type Section
.main-inquiry-group-section {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.type-hero-section {
  background: linear-gradient(135deg, var(--color-primary-element) 0%, var(--color-primary-element-hover) 100%);
  border-radius: 24px;
  padding: 60px 40px;
  color: white;
  text-align: center;

  .type-hero-content {
    max-width: 800px;
    margin: 0 auto;
  }

  .type-icon-large {
    font-size: 80px;
    margin-bottom: 24px;
  }

  .type-hero-title {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 16px;
  }

  .type-hero-description {
    font-size: 20px;
    opacity: 0.9;
    line-height: 1.5;
  }
}

// Root Groups Section
.root-groups-section {
  .section-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--color-main-text);
  }

  .section-description {
    font-size: 18px;
    color: var(--color-text-lighter);
    margin-bottom: 32px;
  }
}

.root-groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

.root-group-card {
  background: var(--color-main-background);
  border: 2px solid var(--color-border);
  border-radius: 20px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;

  &:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--color-primary-element);
  }

  &--blue {
    border-left: 6px solid #3b82f6;
  }

  &--green {
    border-left: 6px solid #10b981;
  }

  &--purple {
    border-left: 6px solid #8b5cf6;
  }

  &--orange {
    border-left: 6px solid #f59e0b;
  }

  &--red {
    border-left: 6px solid #ef4444;
  }

  .card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;

    .card-icon {
      width: 50px;
      height: 50px;
      background: var(--color-background-dark);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .card-title {
      font-size: 20px;
      font-weight: 700;
      margin: 0;
      color: var(--color-main-text);
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  .card-description {
    color: var(--color-text-lighter);
    line-height: 1.5;
    margin-bottom: 20px;
    font-size: 14px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .card-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;

    .stat {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;

      .stat-icon {
        font-size: 14px;
      }

      .stat-label {
        color: var(--color-text-lighter);
      }

      .stat-value {
        font-weight: 600;
        color: var(--color-main-text);
      }
    }
  }

  .card-actions {
    text-align: right;
  }

  .view-button {
    background: var(--color-primary-element);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    font-size: 14px;

    &:hover {
      background: var(--color-primary-element-hover);
    }
  }
}

// Child Groups by Type Section
.child-groups-by-type-section {
  margin-top: 40px;

  .type-section-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--color-border);

    .type-section-title {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .type-section-icon {
      font-size: 32px;
    }

    .type-section-description {
      font-size: 16px;
      color: var(--color-text-lighter);
      margin-left: 44px; // Align with icon
    }
  }
}

.type-groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.type-group-card {
  background: var(--color-background-dark);
  border-radius: 16px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  border: 2px solid transparent;

  &:hover {
    background: var(--color-background-hover);
    transform: translateY(-4px);
    border-color: var(--color-primary-element);
  }

  .type-group-icon {
    font-size: 32px;
    margin-bottom: 16px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .type-group-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-main-text);
  }

  .type-group-description {
    font-size: 13px;
    color: var(--color-text-lighter);
    margin-bottom: 16px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .type-group-count {
    font-size: 12px;
    color: var(--color-text-lighter);
    background: var(--color-main-background);
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
  }
}

.add-new-card {
  background: var(--color-main-background);
  border: 2px dashed var(--color-border);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  &:hover {
    border-color: var(--color-primary-element);
    background: var(--color-background-hover);
  }

  .add-new-icon {
    font-size: 32px;
    color: var(--color-primary-element);
    margin-bottom: 16px;
    width: 60px;
    height: 60px;
    border: 2px solid var(--color-primary-element);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .add-new-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-primary-element);
  }

  .add-new-description {
    font-size: 13px;
    color: var(--color-text-lighter);
  }
}

// Empty State
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: var(--color-background-dark);
  border-radius: 20px;
  border: 2px dashed var(--color-border);

  .create-first-actions {
    margin-top: 32px;
  }
}

// Inquiry Group Detail View
.inquiry-group-detail-view {
  display: flex;
  flex-direction: column;
  gap: 24px;
  
  .content-area {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 32px;
    align-items: start;
  }
}

.inquiry-group-hero {
  background: linear-gradient(135deg, var(--color-background-dark) 0%, var(--color-main-background) 100%);
  border-radius: 24px;
  padding: 32px;
  border: 1px solid var(--color-border);
  position: relative;

  &:hover .inquiry-group-admin-actions {
    opacity: 1;
    transform: translateY(0);
  }

  .inquiry-group-hero-content {
    .inquiry-group-title {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .inquiry-group-icon {
      font-size: 36px;
    }

    .inquiry-group-description {
      font-size: 18px;
      color: var(--color-text-lighter);
      margin-bottom: 20px;
      line-height: 1.6;
    }

    .inquiry-group-meta {
      margin-bottom: 20px;
      
      .meta-item {
        background: var(--color-background-hover);
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 14px;
        color: var(--color-text-lighter);
      }
    }

    .inquiry-group-admin-actions {
      display: flex;
      gap: 12px;
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.3s ease;
      
      button {
        padding: 8px 16px;
        font-size: 14px;
      }
    }
  }
}

// Navigation Tabs
.navigation-tabs {
  background: var(--color-main-background);
  border-radius: 16px;
  padding: 8px;
  border: 1px solid var(--color-border);

  .tabs-container {
    display: flex;
    overflow-x: auto;
    gap: 4px;
  }

  .tab-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;
    font-weight: 500;

    &:hover {
      background: var(--color-background-hover);
    }

    &--active {
      background: var(--color-primary-element);
      color: white;
    }

    .tab-icon {
      font-size: 16px;
    }
  }
}

// Content Area
.content-area {
  .content-section {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
}

// Stats Grid
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;

  .stat-card {
    background: var(--color-background-dark);
    border-radius: 16px;
    padding: 24px;
    text-align: center;

    .stat-value {
      font-size: 36px;
      font-weight: 800;
      color: var(--color-primary-element);
      margin-bottom: 8px;
      line-height: 1;
    }

    .stat-label {
      font-size: 14px;
      color: var(--color-text-lighter);
      font-weight: 500;
    }
  }
}

// Child Groups by Type Preview
.child-groups-by-type-preview {
  .preview-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 24px;
    color: var(--color-main-text);
  }
}

.child-type-section {
  margin-bottom: 32px;

  .child-type-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--color-main-text);

    .child-type-icon {
      font-size: 20px;
    }
  }
}

.child-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}

.child-group-preview-card {
  background: var(--color-background-dark);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;

  &:hover {
    background: var(--color-background-hover);
    transform: translateY(-4px);
  }

  .child-group-preview-icon {
    font-size: 24px;
    margin-bottom: 12px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .child-group-preview-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-main-text);
  }

  .child-group-preview-description {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .child-group-preview-count {
    font-size: 11px;
    color: var(--color-text-lighter);
    background: var(--color-main-background);
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
  }
}

.add-new-preview-card {
  background: var(--color-main-background);
  border: 2px dashed var(--color-border);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  &:hover {
    border-color: var(--color-primary-element);
    background: var(--color-background-hover);
  }

  .add-new-preview-icon {
    font-size: 24px;
    color: var(--color-primary-element);
    margin-bottom: 12px;
    width: 40px;
    height: 40px;
    border: 2px solid var(--color-primary-element);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .add-new-preview-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-primary-element);
  }

  .add-new-preview-description {
    font-size: 12px;
    color: var(--color-text-lighter);
  }
}

// Section Header with Action
.section-header-with-action {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;

  .section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
  }

  .child-group-actions {
    display: flex;
    align-items: center;
    gap: 16px;

    .quick-create-buttons {
      display: flex;
      align-items: center;
      gap: 8px;

      .quick-create-label {
        font-size: 14px;
        color: var(--color-text-lighter);
      }
    }
  }
}

// Child Groups Type Section in detail view
.child-groups-type-section {
  margin-bottom: 32px;

  .child-type-header {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--color-main-text);

    .child-type-header-icon {
      font-size: 20px;
    }
  }
}

.child-groups-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.child-group-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--color-background-dark);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    background: var(--color-background-hover);
    transform: translateX(4px);
  }

  .child-group-item-icon {
    font-size: 20px;
    width: 40px;
    height: 40px;
    background: var(--color-main-background);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .child-group-item-content {
    flex: 1;

    .child-group-item-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 4px;
      color: var(--color-main-text);
    }

    .child-group-item-description {
      color: var(--color-text-lighter);
      font-size: 13px;
      margin-bottom: 8px;
    }

    .child-group-item-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;

      .meta-item {
        color: var(--color-text-lighter);
      }
    }
  }

  .child-group-item-arrow {
    font-size: 18px;
    color: var(--color-text-lighter);
    font-weight: 600;
  }
}

.no-child-groups {
  text-align: center;
  padding: 32px;
  background: var(--color-background-dark);
  border-radius: 12px;
  border: 1px dashed var(--color-border);
  color: var(--color-text-lighter);
}

.no-child-groups-section {
  text-align: center;
  padding: 40px 20px;
  background: var(--color-background-dark);
  border-radius: 16px;
  border: 2px dashed var(--color-border);

  .create-child-group-options {
    margin-top: 32px;

    h4 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 16px;
      color: var(--color-main-text);
    }

    .child-type-options {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;

      .option-icon {
        margin-right: 6px;
      }
    }
  }
}

// Inquiries by Type
.inquiry-type-section {
  margin-bottom: 32px;

  .inquiry-type-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-main-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
}

// Inquiries Grid (Decidim-style)
.inquiries-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.inquiry-card {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 12px;

  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  }

  .inquiry-card-header {
    display: flex;
    align-items: center;
    gap: 12px;

    .inquiry-icon {
      font-size: 20px;
      width: 40px;
      height: 40px;
      background: var(--color-background-dark);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .inquiry-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--color-main-text);
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  .inquiry-description {
    color: var(--color-text-lighter);
    font-size: 13px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .inquiry-status {
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;

      &.status-draft {
        background: #e5e7eb;
        color: #6b7280;
      }

      &.status-open {
        background: #dbeafe;
        color: #1d4ed8;
      }

      &.status-closed {
        background: #f3f4f6;
        color: #374151;
      }

      &.status-accepted {
        background: #dcfce7;
        color: #16a34a;
      }

      &.status-pending {
        background: #fef3c7;
        color: #d97706;
      }
    }
  }
}

// Creation Section
.creation-section {
  margin-top: 32px;
  padding-top: 32px;
  border-top: 1px solid var(--color-border);

  .creation-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--color-main-text);
  }

  .creation-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
  }

  .creation-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--color-background-dark);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;

    &:hover {
      border-color: var(--color-primary-element);
      transform: translateY(-2px);
    }

    .creation-icon {
      font-size: 20px;
      width: 40px;
      height: 40px;
      background: var(--color-main-background);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .creation-content {
      h5 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
        color: var(--color-main-text);
      }

      p {
        color: var(--color-text-lighter);
        font-size: 12px;
        margin: 0;
      }
    }
  }
}

// Right Sidebar
.right-sidebar {
  position: sticky;
  top: 20px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sidebar-section {
  background: var(--color-background-dark);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--color-border);

  .sidebar-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-main-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .sidebar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
    
    &:last-child {
      border-bottom: none;
    }

    .sidebar-icon {
      font-size: 16px;
      width: 32px;
      height: 32px;
      background: var(--color-main-background);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .sidebar-label {
      flex: 1;
      font-size: 14px;
      color: var(--color-main-text);
    }

    .sidebar-count {
      font-size: 12px;
      color: var(--color-text-lighter);
      font-weight: 600;
    }
  }

  .sidebar-info {
    display: flex;
    flex-direction: column;
    gap: 12px;

    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid var(--color-border);

      &:last-child {
        border-bottom: none;
      }

      .info-label {
        font-size: 13px;
        color: var(--color-text-lighter);
      }

      .info-value {
        font-size: 13px;
        font-weight: 500;
        color: var(--color-main-text);
        text-align: right;
      }
    }
  }
}

// Responsive Design
@media (max-width: 1024px) {
  .inquiry-group-detail-view .content-area {
    grid-template-columns: 1fr;
  }
  
  .right-sidebar {
    position: static;
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .type-hero-section {
    padding: 40px 20px;
    
    .type-icon-large {
      font-size: 60px;
    }
    
    .type-hero-title {
      font-size: 36px;
    }
  }

  .root-groups-grid,
  .type-groups-grid,
  .inquiries-grid,
  .creation-cards {
    grid-template-columns: 1fr;
  }

  .inquiry-group-hero {
    padding: 24px;
    
    .inquiry-group-admin-actions {
      flex-direction: column;
      opacity: 1;
      transform: translateY(0);
    }
  }

  .navigation-tabs .tabs-container {
    flex-wrap: wrap;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .child-type-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }

  .header-actions {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
    
    .action-buttons {
      flex-wrap: wrap;
      width: 100%;
    }
  }

  .section-header-with-action {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .child-group-actions {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
