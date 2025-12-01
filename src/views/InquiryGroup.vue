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
import InquiryGroupEditDlg from '../components/Create/InquiryGroupEditDlg.vue'
import InquiryGroupEditViewForm from '../components/InquiryGroup/InquiryGroupEditViewForm.vue'
import { AgoraAppIcon } from '../components/AppIcons/index.ts'
import { useSessionStore } from '../stores/session.ts'
import { useInquiriesStore } from '../stores/inquiries.ts'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import { useInquiryGroupStore } from '../stores/inquiryGroup.ts'
import { getInquiryItemData } from '../helpers/modules/InquiryHelper.ts'
import type { InquiryGroup, InquiryGroupType } from '../types/inquiryGroups.types'
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

// Check if we're in inquiry group detail view based on route
const isInquiryGroupView = computed(() => {
  return route.name === 'group' && route.params.id
})

// Get current inquiry group from route
const currentInquiryGroupId = computed(() => {
  if (!isInquiryGroupView.value) return null
  return parseInt(route.params.id as string)
})

// Get child inquiry groups
const childGroups = computed(() => {
  if (!currentInquiryGroup.value.id) return []
  return inquiryGroups.value.filter(group => 
    group.parent_id === currentInquiryGroup.value.id
  )
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
    inquiryTypeList.includes(type.inquiry_type)
  )
})

// Get root inquiry groups (parent_id = null) by type
const rootInquiryGroups = computed(() => {
  const groupType = route.query.type as string || 'assembly'
  return inquiryGroups.value.filter(group => 
    group.parent_id === null && group.type === groupType
  )
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
function createChildGroup() {
  selectedInquiryGroupTypeForCreation.value = null
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
</script>

<template>
  <NcAppContent class="inquiry-groups">
    <!-- Inquiry Groups List View (when no ID) -->
    <template v-if="!currentInquiryGroupId" #title>
      <div class="page-title">
        <span class="title-icon">🏛️</span>
        {{ t('inquiries', 'Inquiry Groups') }}
      </div>
    </template>
    
    <!-- Inquiry Group Detail View -->
    <template v-else #title>
      <div class="page-title">
        <span class="title-icon">
          {{ getGroupIcon(currentInquiryGroup.type) }}
        </span>
        {{ t('inquiries', 'Inquiry Group:') }} {{ currentInquiryGroup.name || currentInquiryGroup.title }}
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
        {{ t('inquiries', 'Back to Groups') }}
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

    <!-- Inquiry Groups List View -->
    <div v-if="!currentInquiryGroupId" class="inquiry-groups-list-view">
      <!-- Hero Section -->
      <div class="hero-section">
        <div class="hero-content">
          <h1 class="hero-title">
            <span class="hero-icon">🏛️</span>
            {{ t('inquiries', 'Inquiry Groups') }}
          </h1>
          <p class="hero-description">
            {{ t('inquiries', 'Organize and manage your inquiries in groups') }}
          </p>
        </div>
      </div>

      <!-- Inquiry Groups Grid -->
      <div class="inquiry-groups-grid-section">
        <div class="section-header">
          <h2 class="section-title">{{ t('inquiries', 'Root Inquiry Groups') }}</h2>
          <div class="view-controls">
            <span class="view-label">{{ t('inquiries', 'GRID VIEW') }}</span>
            <span class="view-style">Decidim Style</span>
          </div>
        </div>

        <div class="inquiry-groups-grid">
          <div
            v-for="(group, index) in rootInquiryGroups"
            :key="group.id"
            class="inquiry-group-card"
            :class="`inquiry-group-card--${getGroupColor(index)}`"
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

        <NcEmptyContent 
          v-if="rootInquiryGroups.length === 0" 
          :name="t('inquiries', 'No inquiry groups created')"
          :description="t('inquiries', 'Create your first inquiry group to start')"
        >
          <template #icon>
            <AgoraAppIcon />
          </template>
        </NcEmptyContent>
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
              ({{ inquiriesInGroup.length }} inquiries)
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
              <div class="stat-value">{{ currentInquiryGroupTypeData?.label || currentInquiryGroup.type }}</div>
              <div class="stat-label">{{ t('inquiries', 'Group Type') }}</div>
            </div>
          </div>

          <!-- Child Groups Preview -->
          <div v-if="childGroups.length > 0" class="child-groups-preview">
            <h3 class="preview-title">{{ t('inquiries', 'Child Groups') }}</h3>
            <div class="child-groups-grid">
              <div
                v-for="child in childGroups"
                :key="child.id"
                class="child-group-card"
                @click="viewInquiryGroup(child.id)"
              >
                <div class="child-group-icon">
                  {{ getGroupIcon(child.type) }}
                </div>
                <h4 class="child-group-title">{{ child.name || child.title }}</h4>
                <p class="child-group-description">{{ child.description }}</p>
                <div class="child-group-count">
                  {{ child.inquiryIds?.length || 0 }} inquiries
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Child Groups Section -->
        <div v-if="activeSection === 'child-groups'" class="content-section">
          <div class="section-header-with-action">
            <h3 class="section-title">{{ t('inquiries', 'Child Groups') }}</h3>
            <NcButton 
              v-if="inquiryGroupStore.permissions.addInquiries"
              type="primary"
              @click="createChildGroup"
            >
              {{ t('inquiries', 'Create Child Group') }}
            </NcButton>
          </div>
          
          <div v-if="childGroups.length > 0" class="child-groups-list">
            <div
              v-for="child in childGroups"
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
          
          <NcEmptyContent 
            v-else 
            :name="t('inquiries', 'No child groups')"
            :description="t('inquiries', 'Create child groups to organize inquiries')"
          />
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
        </div>
      </div>
    </div>

    <InquiryCreateDlg
      v-if="createInquiryDlgToggle"
      :inquiry-type="selectedInquiryType"
      :inquiry-group-id="currentInquiryGroup.id"
      @added="inquiryAdded"
      @close="createInquiryDlgToggle = false"
    />

    <!-- Group Creation Dialog -->
    <nquiryGroupEditDlg
      v-if="createGroupDlgToggle"
      :inquiry-group-type="selectedInquiryGroupTypeForCreation"
      :selected-groups="selectedGroups"
      :available-groups="availableGroups"
      :parent-group-id="currentInquiryGroup.id"
      @close="handleCloseGroupDialog"
      @added="inquiryGroupAdded"
      @update:selected-groups="handleGroupUpdate"
    />

    <!-- Group Edit Dialog -->
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

// Hero Section
.hero-section {
  background: linear-gradient(135deg, var(--color-primary-element) 0%, var(--color-primary-element-hover) 100%);
  border-radius: 24px;
  padding: 60px 40px;
  margin-bottom: 40px;
  color: white;
  text-align: center;

  .hero-content {
    max-width: 600px;
    margin: 0 auto;
  }

  .hero-title {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
  }

  .hero-icon {
    font-size: 56px;
  }

  .hero-description {
    font-size: 20px;
    margin-bottom: 32px;
    opacity: 0.9;
    line-height: 1.5;
  }
}

// Inquiry Groups Grid Section
.inquiry-groups-grid-section {
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;

    .section-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--color-main-text);
    }

    .view-controls {
      display: flex;
      align-items: center;
      gap: 12px;
      background: var(--color-background-dark);
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 14px;

      .view-label {
        font-weight: 600;
        color: var(--color-text-lighter);
      }

      .view-style {
        color: var(--color-primary-element);
        font-weight: 500;
      }
    }
  }
}

// Inquiry Groups Grid
.inquiry-groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.inquiry-group-card {
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

// Child Groups
.child-groups-preview {
  .preview-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-main-text);
  }

  .child-groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }

  .child-group-card {
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

    .child-group-icon {
      font-size: 24px;
      margin-bottom: 12px;
    }

    .child-group-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--color-main-text);
    }

    .child-group-description {
      font-size: 12px;
      color: var(--color-text-lighter);
      margin-bottom: 12px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .child-group-count {
      font-size: 11px;
      color: var(--color-text-lighter);
      background: var(--color-main-background);
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
    }
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
}

// Child Groups List
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
  .hero-section {
    padding: 40px 20px;
    
    .hero-title {
      font-size: 36px;
      flex-direction: column;
      gap: 8px;
    }
  }

  .inquiry-groups-grid {
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

  .inquiries-grid,
  .creation-cards {
    grid-template-columns: 1fr;
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
}
</style>
