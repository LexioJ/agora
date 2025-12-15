<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-full" :class="{ 'comments-open': showComments }">
    <!-- Main Content -->
    <div class="full-content-wrapper">
      <div class="full-content" ref="contentRef">
        <!-- Title Section -->
        <div class="title-section">
          <div class="title-header">
            <div class="type-badge">
              <component :is="typeIconComponent" class="type-icon" :size="16" />
              <span class="type-label">{{ typeLabel }}</span>
            </div>
            <div v-if="inquiry.ownedGroup" class="group-badge">
              {{ inquiry.ownedGroup }}
            </div>
          </div>
          
          <h1 class="inquiry-title">{{ inquiry.title }}</h1>
          
          <!-- Status Badge -->
          <div v-if="inquiry.status?.inquiryStatus" class="status-badge" :class="statusClass">
            <component :is="statusIconComponent" class="status-icon" :size="14" />
            {{ statusText }}
          </div>
        </div>

        <!-- Cover Image -->
        <div v-if="coverUrl" class="cover-section">
          <img :src="coverUrl" :alt="inquiry.title" class="cover-image" />
        </div>

        <!-- Meta Information -->
        <div class="meta-section">
          <div class="meta-author">
            <NcAvatar
              v-if="inquiry.ownedGroup"
              :display-name="inquiry.ownedGroup"
              :show-user-status="false"
              :size="44"
              class="author-avatar"
            />
            <NcAvatar
              v-else
              :user="inquiry.owner?.id"
              :display-name="inquiry.owner?.displayName"
              :size="44"
              class="author-avatar"
            />
            <div class="author-info">
              <div class="author-name">
                {{ inquiry.ownedGroup || inquiry.owner?.displayName }}
              </div>
              <div class="meta-details">
                <div v-if="formattedDate" class="meta-item">
                  <InquiryGeneralIcons.Calendar :size="14" />
                  {{ formattedDate }}
                </div>
                
                <div v-if="locationPath" class="meta-item">
                  <InquiryGeneralIcons.MapMarker :size="14" />
                  {{ truncatedLocation }}
                </div>
                
                <div v-if="categoryPath" class="meta-item">
                  <InquiryGeneralIcons.Tag :size="14" />
                  {{ truncatedCategory }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Description Content -->
        <div class="description-section" v-html="sanitizedContent"></div>

        <!-- Action Buttons -->
        <div class="actions-section">
          <!-- Support Section -->
          <div class="support-section">
            <h3 v-if="inquiry.configuration?.supportMode === 'ternary'">
              {{ t('agora', 'Support this inquiry:') }}
            </h3>
            
            <div class="support-buttons">
              <!-- Ternary Support Mode -->
              <div v-if="inquiry.configuration?.supportMode === 'ternary'" class="ternary-support">
                <NcButton
                  type="secondary"
                  :class="{ 'active': currentSupportValue === 1 }"
                  @click="handleSupport(1)"
                >
                  <template #icon>
                    <TernarySupportIcon :support-value="currentSupportValue === 1 ? 1 : null" :size="20" />
                  </template>
                  {{ t('agora', 'Support') }}
                  <span v-if="inquiry.status?.countPositiveSupports" class="button-count">
                    {{ inquiry.status.countPositiveSupports }}
                  </span>
                </NcButton>
                
                <NcButton
                  type="secondary"
                  :class="{ 'active': currentSupportValue === 0 }"
                  @click="handleSupport(0)"
                >
                  <template #icon>
                    <TernarySupportIcon :support-value="currentSupportValue === 0 ? 0 : null" :size="20" />
                  </template>
                  {{ t('agora', 'Neutral') }}
                  <span v-if="inquiry.status?.countNeutralSupports" class="button-count">
                    {{ inquiry.status.countNeutralSupports }}
                  </span>
                </NcButton>
                
                <NcButton
                  type="secondary"
                  :class="{ 'active': currentSupportValue === -1 }"
                  @click="handleSupport(-1)"
                >
                  <template #icon>
                    <TernarySupportIcon :support-value="currentSupportValue === -1 ? -1 : null" :size="20" />
                  </template>
                  {{ t('agora', 'Oppose') }}
                  <span v-if="inquiry.status?.countNegativeSupports" class="button-count">
                    {{ inquiry.status.countNegativeSupports }}
                  </span>
                </NcButton>
              </div>
              
              <!-- Simple Support Mode -->
              <div v-else-if="inquiry.configuration?.supportMode" class="simple-support">
                <NcButton
                  type="primary"
                  :class="{ 'active': isSupported }"
                  @click="handleSupport(1)"
                >
                  <template #icon>
                    <ThumbIcon :supported="isSupported" :size="20" />
                  </template>
                  {{ isSupported ? t('agora', 'Supported') : t('agora', 'Support') }}
                  <span v-if="inquiry.status?.countSupports" class="button-count">
                    {{ inquiry.status.countSupports }}
                  </span>
                </NcButton>
              </div>
            </div>
            
            <!-- Quorum Info -->
            <div v-if="hasQuorum" class="quorum-info">
              {{ t('agora', 'Quorum: {current} / {needed}', { 
                current: inquiry.status?.countSupports || 0, 
                needed: quorumValue 
              }) }}
            </div>
          </div>

          <!-- Comments Button -->
          <div class="comments-button">
            <NcButton
              type="secondary"
              @click="toggleComments"
              :aria-expanded="showComments"
            >
              <template #icon>
                <InquiryGeneralIcons.Comment :size="20" />
              </template>
              {{ t('agora', 'Comments') }}
              <span v-if="inquiry.status?.countComments" class="comments-count">
                ({{ inquiry.status.countComments }})
              </span>
            </NcButton>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-section">
          <div class="stats-grid">
            <div v-if="inquiry.status?.countSupports" class="stat-item">
              <ThumbIcon :supported="true" :size="18" />
              <div class="stat-content">
                <span class="stat-value">{{ inquiry.status.countSupports }}</span>
                <span class="stat-label">{{ t('agora', 'Supports') }}</span>
              </div>
            </div>
            
            <div v-if="inquiry.status?.countComments" class="stat-item">
              <InquiryGeneralIcons.Comment :size="18" />
              <div class="stat-content">
                <span class="stat-value">{{ inquiry.status.countComments }}</span>
                <span class="stat-label">{{ t('agora', 'Comments') }}</span>
              </div>
            </div>
            
            <div v-if="participantsCount" class="stat-item">
              <InquiryGeneralIcons.Users :size="18" />
              <div class="stat-content">
                <span class="stat-value">{{ participantsCount }}</span>
                <span class="stat-label">{{ t('agora', 'Participants') }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Attachments Section -->
        <div v-if="hasResources" class="attachments-section">
          <h3>
            <InquiryGeneralIcons.Paperclip :size="18" />
            {{ t('agora', 'Attachments') }}
          </h3>
          <SideBarTabResources :inquiry="inquiry" />
        </div>
      </div>

      <!-- Comments Sidebar Panel -->
      <div v-if="showComments" class="comments-panel">
        <div class="comments-header">
          <h3 class="comments-title">
            <InquiryGeneralIcons.Comment :size="20" />
            {{ t('agora', 'Comments') }}
            <span v-if="inquiry.status?.countComments" class="comments-badge">
              {{ inquiry.status.countComments }}
            </span>
          </h3>
          <NcButton
            type="tertiary"
            :aria-label="t('agora', 'Close comments')"
            @click="toggleComments"
            class="close-comments simple-button"
          >
            <template #icon>
              <InquiryGeneralIcons.Close :size="20" />
            </template>
          </NcButton>
        </div>
        <div class="comments-content">
          <CommentAdd :inquiry-id="inquiry.id" />
          <Comments :inquiry-id="inquiry.id" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { t } from '@nextcloud/l10n'
import { showSuccess, showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import DOMPurify from 'dompurify'

// Import icons and components
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import { StatusIcons } from '../../utils/icons.ts'
import { TernarySupportIcon, ThumbIcon } from '../AppIcons'

// Import components
import SideBarTabResources from '../SideBar/SideBarTabResources.vue'
import CommentAdd from '../Comments/CommentAdd.vue'
import Comments from '../Comments/Comments.vue'

// Import helpers and stores
import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useSupportsStore } from '../../stores/supports.ts'
import { useInquiryStore } from '../../stores/inquiry.ts'
import { useInquiriesStore } from '../../stores/inquiries.ts'

interface Props {
  inquiry: Inquiry
  renderMode?: string
  fullWidth?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  support: [inquiryId: number, value: number]
}>()

const sessionStore = useSessionStore()
const supportsStore = useSupportsStore()
const inquiryStore = useInquiryStore()
const inquiriesStore = useInquiriesStore()

// State
const showComments = ref(false)
const currentSupportValue = ref(props.inquiry.currentUserStatus?.supportValue || 0)
const isProcessingSupport = ref(false)
const contentRef = ref<HTMLElement>()

// Get type data
const inquiryTypes = computed(() => sessionStore.appSettings?.inquiryTypeTab || [])

const typeData = computed(() => {
  return getInquiryTypeData(props.inquiry.type, inquiryTypes.value)
})

const typeLabel = computed(() => typeData.value?.label || props.inquiry.type)

// Get type icon
const typeIconComponent = computed(() => {
  if (typeData.value?.icon) {
    const iconName = typeData.value.icon
    
    if (typeof iconName === 'string' && InquiryGeneralIcons[iconName]) {
      return InquiryGeneralIcons[iconName]
    }
  }
  
  // Fallback mapping based on inquiry type
  const iconMap: Record<string, any> = {
    'survey': 'ClipboardList',
    'poll': 'CheckCircle',
    'question': 'Question',
    'discussion': 'MessageSquare',
    'news': 'Newspaper',
    'announcement': 'Megaphone',
    'meeting': 'Users',
    'document': 'BookOpen',
    'proposal': 'Scale',
    'general': 'FolderMultiple',
    'draft': 'Empty',
  }
  
  const iconName = iconMap[props.inquiry.type?.toLowerCase()] || 'FolderMultiple'
  return InquiryGeneralIcons[iconName] || InquiryGeneralIcons.FolderMultiple
})

// Status
const currentInquiryStatus = computed(() => {
  if (!props.inquiry.status?.inquiryStatus) return null
  
  const specialStatuses = {
    'draft': {
      statusKey: 'draft',
      label: 'Draft',
      icon: 'draft',
      inquiryType: props.inquiry.type,
      order: 0,
    },
    'waiting_approval': {
      statusKey: 'waiting_approval',
      label: 'Waiting Approval',
      icon: 'waitingapproval',
      inquiryType: props.inquiry.type,
      order: 1,
    }
  }

  const currentStatus = props.inquiry.status.inquiryStatus
  if (specialStatuses[currentStatus]) {
    return specialStatuses[currentStatus]
  }

  const statusesFromSettings = sessionStore.appSettings?.inquiryStatusTab
    ?.filter((status) => status.inquiryType === props.inquiry.type) || []
    
  return statusesFromSettings.find(
    (status) => status.statusKey === currentStatus
  ) || specialStatuses.draft
})

const statusClass = computed(() => {
  if (!currentInquiryStatus.value) return 'status-unknown'
  
  const statusKey = currentInquiryStatus.value.statusKey
  const statusMap: Record<string, string> = {
    'draft': 'status-draft',
    'waiting_approval': 'status-waiting',
    'open': 'status-open',
    'closed': 'status-closed',
    'archived': 'status-archived',
    'published': 'status-published',
    'completed': 'status-completed',
    'cancelled': 'status-cancelled',
  }
  
  return statusMap[statusKey] || 'status-unknown'
})

const statusText = computed(() => {
  return currentInquiryStatus.value?.label ? t('agora', currentInquiryStatus.value.label) : ''
})

const statusIconComponent = computed(() => {
  if (!currentInquiryStatus.value?.icon) return StatusIcons.Default
  
  const iconName = currentInquiryStatus.value.icon
  return StatusIcons[iconName] || StatusIcons.Default
})

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)

// Quorum
const hasQuorum = computed(() => props.inquiry.configuration?.quorum && props.inquiry.configuration.quorum > 0)
const quorumValue = computed(() => props.inquiry.configuration?.quorum || 0)

// Cover image
const coverUrl = computed(() => {
  if (!props.inquiry.coverId || props.inquiry.coverId === 0) return ''
  return getNextcloudPreviewUrl(props.inquiry.coverId)
})

function getNextcloudPreviewUrl(fileId: number, x = 1200, y = 400, autoScale = true) {
  const baseUrl = window.location.origin
  return `${baseUrl}/index.php/core/preview?fileId=${fileId}&x=${x}&y=${y}&a=${autoScale}`
}

// Location and Category paths
const locationPath = computed(() => {
  if (!props.inquiry.locationId || props.inquiry.locationId === 0 || !sessionStore.appSettings?.locationTab) return ''
  
  const getHierarchyPath = (items: any[], targetId: number): string => {
    const itemMap: Record<number, any> = {}
    
    items.forEach((item) => {
      itemMap[item.id] = item
    })
    
    if (!itemMap[targetId]) {
      return ''
    }
    
    function buildPath(item: any): string {
      if (item.parentId === 0) {
        return item.name
      }
      const parent = itemMap[item.parentId]
      if (parent) {
        return `${buildPath(parent)} → ${item.name}`
      }
      return item.name
    }
    
    return buildPath(itemMap[targetId])
  }
  
  return getHierarchyPath(sessionStore.appSettings.locationTab, props.inquiry.locationId)
})

const categoryPath = computed(() => {
  if (!props.inquiry.categoryId || props.inquiry.categoryId === 0 || !sessionStore.appSettings?.categoryTab) return ''
  
  const getHierarchyPath = (items: any[], targetId: number): string => {
    const itemMap: Record<number, any> = {}
    
    items.forEach((item) => {
      itemMap[item.id] = item
    })
    
    if (!itemMap[targetId]) {
      return ''
    }
    
    function buildPath(item: any): string {
      if (item.parentId === 0) {
        return item.name
      }
      const parent = itemMap[item.parentId]
      if (parent) {
        return `${buildPath(parent)} → ${item.name}`
      }
      return item.name
    }
    
    return buildPath(itemMap[targetId])
  }
  
  return getHierarchyPath(sessionStore.appSettings.categoryTab, props.inquiry.categoryId)
})

// Truncated text
const truncatedLocation = computed(() => {
  if (!locationPath.value) return ''
  return locationPath.value.length > 25 
    ? locationPath.value.substring(0, 25) + '…' 
    : locationPath.value
})

const truncatedCategory = computed(() => {
  if (!categoryPath.value) return ''
  return categoryPath.value.length > 25 
    ? categoryPath.value.substring(0, 25) + '…' 
    : categoryPath.value
})

// Date formatting
const formattedDate = computed(() => {
  if (!props.inquiry.status?.created) return ''
  
  try {
    const date = new Date(props.inquiry.status.created * 1000) // Convert timestamp to milliseconds
    return date.toLocaleDateString('default', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return ''
  }
})

// Participants count
const participantsCount = computed(() => props.inquiry.status?.countParticipants || 0)

// Content
const sanitizedContent = computed(() => {
  const content = props.inquiry.descriptionSafe || props.inquiry.description
  if (!content || content.trim() === '') {
    return `<div class="no-content">
              <p>${t('agora', 'No content available')}</p>
            </div>`
  }
  
  return DOMPurify.sanitize(content, {
    ALLOWED_TAGS: [
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'p', 'br', 'strong', 'em', 'u', 's',
      'ul', 'ol', 'li',
      'blockquote', 'pre', 'code',
      'img', 'figure', 'figcaption',
      'table', 'thead', 'tbody', 'tr', 'th', 'td',
      'a', 'span', 'div',
      'hr'
    ],
    ALLOWED_ATTR: [
      'href', 'target', 'rel', 'title',
      'src', 'alt', 'width', 'height', 'loading',
      'class', 'id', 'style'
    ]
  })
})

function formatDate(timestamp: number) {
  return new Date(timestamp * 1000).toLocaleDateString()
}

// Has resources (check for miscFields)
const hasResources = computed(() => {
  return props.inquiry.miscFields && Object.keys(props.inquiry.miscFields).length > 0
})

// Handlers
function toggleComments() {
  showComments.value = !showComments.value
}

async function handleSupport(value: number) {
  if (isProcessingSupport.value) return
  
  const hadSupportedBefore = props.inquiry.currentUserStatus?.hasSupported || false
  const supportValueBefore = props.inquiry.currentUserStatus?.supportValue || null
  
  // For ternary mode, if clicking the same button, remove support
  const newValue = (props.inquiry.configuration?.supportMode === 'ternary' && supportValueBefore === value) ? null : value
  
  isProcessingSupport.value = true
  
  try {
    // Update local state immediately for better UX
    currentSupportValue.value = newValue
    
    // Call the support toggle function
    await supportsStore.toggleSupport(
      props.inquiry.id, 
      sessionStore.currentUser.id, 
      props.inquiry, 
      inquiriesStore
    )
    
    // Emit the support event
    emit('support', props.inquiry.id, newValue)
    
    // Show success messages based on support mode
    if (props.inquiry.configuration?.supportMode === 'simple') {
      if (newValue === 1 && !hadSupportedBefore) {
        showSuccess(t('agora', 'Inquiry supported, thanks for your support!'), { timeout: 2000 })
      } else if (newValue === null && hadSupportedBefore) {
        showSuccess(t('agora', 'Inquiry support removed!'), { timeout: 2000 })
      }
    } else if (props.inquiry.configuration?.supportMode === 'ternary') {
      if (newValue === 1) {
        showSuccess(t('agora', 'Inquiry supported, thanks for your support!'), { timeout: 2000 })
      } else if (newValue === 0) {
        showSuccess(t('agora', 'Neutral position saved!'), { timeout: 2000 })
      } else if (newValue === -1) {
        showSuccess(t('agora', 'Against position saved!'), { timeout: 2000 })
      } else if (newValue === null && hadSupportedBefore) {
        showSuccess(t('agora', 'Participation removed!'), { timeout: 2000 })
      }
    }
    
  } catch (error) {
    console.error('Failed to update support:', error)
    showError(t('agora', 'Failed to update support status'))
    
    // Revert local state on error
    currentSupportValue.value = supportValueBefore
  } finally {
    isProcessingSupport.value = false
  }
}

// Watch for changes in support value
watch(() => props.inquiry.currentUserStatus?.supportValue, (newValue) => {
  currentSupportValue.value = newValue || 0
})
</script>

<style lang="scss" scoped>
.inquiry-full {
  background: transparent !important;
  border-radius: 0 !important;
  padding: 0 !important;
  margin: 0 !important;
  box-shadow: none !important;
  width: 100%;
  height: 100%;

  &.full-width {
    width: 100% !important;
    max-width: 100% !important;
    grid-column: 1 / -1;
  }
}

.inquiry-title {
  font-size: 24px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.inquiry-description {
  font-size: 16px;
  line-height: 1.6;
  color: #666;
  margin-bottom: 24px;
}

.inquiry-meta {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #eee;

  span {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
  }

  .inquiry-type {
    background: #e3f2fd;
    color: #1976d2;
  }

  .inquiry-status {
    &.active {
      background: #e8f5e9;
      color: #2e7d32;
    }
    &.draft {
      background: #fff3e0;
      color: #f57c00;
    }
  }

  .inquiry-date {
    background: #f5f5f5;
    color: #666;
  }
}

.inquiry-body {
  min-height: 200px;
}

/* Remove the back button header */
.full-header {
  display: none !important;
}

/* Main Content Wrapper - simplified */
.full-content-wrapper {
  width: 100%;
  height: 100%;
  overflow: visible;
  background: transparent;
}

/* Main Content - simplified */
.full-content {
  width: 100%;
  max-width: 100%;
  padding: 0;
  margin: 0;
  overflow: visible;
  background: transparent;
  box-sizing: border-box;
}

/* Title Section - keep content but remove container backgrounds */
.title-section {
  margin-bottom: 24px;

  .title-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;

    .type-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      background: var(--color-background-dark);
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      color: var(--color-text-lighter);

      .type-icon {
        color: var(--color-primary-element);
      }
    }

    .group-badge {
      padding: 4px 10px;
      background: rgba(var(--color-primary-rgb), 0.1);
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
      color: var(--color-primary-element);
    }
  }

  .inquiry-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-main-text);
    margin: 0 0 16px 0;
    line-height: 1.3;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;

    .status-icon {
      color: inherit;
    }

    &.status-open {
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    &.status-closed {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    &.status-draft {
      background: linear-gradient(135deg, rgba(148, 163, 184, 0.1), rgba(148, 163, 184, 0.05));
      color: #64748b;
      border: 1px solid rgba(148, 163, 184, 0.2);
    }

    &.status-waiting {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
      color: #f59e0b;
      border: 1px solid rgba(245, 158, 11, 0.2);
    }

    &.status-unknown {
      background: var(--color-background-dark);
      color: var(--color-text-lighter);
    }
  }
}

/* Cover Image */
.cover-section {
  margin: 0 0 24px 0;
  border-radius: 12px;
  overflow: hidden;

  .cover-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
  }
}

/* Meta Information */
.meta-section {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--color-border);

  .meta-author {
    display: flex;
    align-items: center;
    gap: 16px;

    .author-avatar {
      flex-shrink: 0;
      border: 2px solid white;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .author-info {
      flex: 1;

      .author-name {
        font-size: 16px;
        font-weight: 600;
        color: var(--color-main-text);
        margin-bottom: 8px;
      }

      .meta-details {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;

        .meta-item {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 13px;
          color: var(--color-text-lighter);

          :deep(svg) {
            color: var(--color-text-maxcontrast);
          }
        }
      }
    }
  }
}

/* Description Content */
.description-section {
  font-size: 16px;
  line-height: 1.8;
  color: var(--color-main-text);
  margin-bottom: 40px;

  // Rich text styling
  :deep(*) {
    margin: 0 0 20px 0;

    &:last-child {
      margin-bottom: 0;
    }
  }

  :deep(h1) {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-main-text);
    margin: 32px 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--color-border);

    &:first-child {
      margin-top: 0;
    }
  }

  :deep(h2) {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 28px 0 14px 0;
  }

  :deep(p) {
    margin-bottom: 20px;
    line-height: 1.8;
  }

  :deep(ul), :deep(ol) {
    padding-left: 24px;
    margin: 16px 0;

    li {
      margin-bottom: 8px;
      line-height: 1.6;
    }
  }

  :deep(a) {
    color: var(--color-primary-element);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  :deep(.no-content) {
    text-align: center;
    padding: 40px 20px;
    color: var(--color-text-maxcontrast);
    font-style: italic;
    background: var(--color-background-dark);
    border-radius: 8px;
  }
}

/* Action Buttons */
.actions-section {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 32px;
  padding: 24px;
  background: var(--color-background-dark);
  border-radius: 12px;

  .support-section {
    flex: 1;

    h3 {
      font-size: 16px;
      font-weight: 600;
      color: var(--color-main-text);
      margin: 0 0 16px 0;
    }

    .support-buttons {
      display: flex;
      gap: 12px;
      margin-bottom: 12px;

      .ternary-support,
      .simple-support {
        display: flex;
        gap: 8px;
      }

      :deep(button) {
        position: relative;
        min-width: 120px;

        &.active {
          background: var(--color-primary-element);
          color: white;
          border-color: var(--color-primary-element);
        }

        &:disabled {
          opacity: 0.6;
          cursor: not-allowed;
        }

        .button-count {
          margin-left: 6px;
          font-weight: 600;
          font-size: 14px;
          opacity: 0.9;
        }
      }
    }

    .quorum-info {
      font-size: 13px;
      color: var(--color-text-lighter);
      font-weight: 500;
    }
  }

  .comments-button {
    flex-shrink: 0;

    :deep(button) {
      .comments-count {
        margin-left: 4px;
        font-weight: normal;
        opacity: 0.8;
      }
    }
  }
}

/* Stats Section */
.stats-section {
  margin-bottom: 40px;

  .stats-grid {
    display: flex;
    gap: 24px;

    .stat-item {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px;
      background: white;
      border: 1px solid var(--color-border);
      border-radius: 12px;

      .stat-content {
        display: flex;
        flex-direction: column;
        gap: 4px;

        .stat-value {
          font-size: 20px;
          font-weight: 700;
          color: var(--color-main-text);
        }

        .stat-label {
          font-size: 12px;
          color: var(--color-text-lighter);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
      }
    }
  }
}

/* Attachments Section */
.attachments-section {
  margin-bottom: 40px;

  h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

/* Comments Panel - keep this if needed, but make it work within layout */
.comments-panel {
  position: fixed;
  top: 0;
  right: -400px;
  bottom: 0;
  width: 400px;
  background: var(--color-main-background);
  border-left: 1px solid var(--color-border);
  box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  transition: right 0.3s ease;
  display: flex;
  flex-direction: column;

  .comments-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--color-border);
    background: white;
    flex-shrink: 0;

    .comments-title {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 0;
      font-size: 16px;
      font-weight: 600;

      .comments-badge {
        background: var(--color-primary-element);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
      }
    }

    .close-comments {
      min-width: auto;
      min-height: auto;
      width: 32px;
      height: 32px;
      padding: 0;
    }
  }

  .comments-content {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    display: flex;
    flex-direction: column;

    > * {
      padding: 16px 20px;
    }

    > :last-child {
      flex: 1;
      overflow-y: auto;
    }
  }
}

/* Remove the problematic mobile responsive styles that break layout */
@media (max-width: 768px) {
  .inquiry-full {
    /* Remove the comments-open transform that breaks layout */
    &:not(.comments-open) {
      .full-content-wrapper {
        .full-content {
          transform: none !important;
          margin-right: 0 !important;
        }
      }
    }
  }

  .full-content {
    padding: 16px;
  }

  .comments-panel {
    right: -100%;
    width: 100%;

    .comments-header {
      padding: 12px 16px;
    }

    .comments-content {
      > * {
        padding: 12px 16px;
      }
    }
  }

  .title-section {
    .inquiry-title {
      font-size: 24px;
    }
  }

  .cover-section {
    .cover-image {
      height: 200px;
    }
  }

  .meta-section {
    .meta-author {
      .author-info {
        .meta-details {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;
        }
      }
    }
  }

  .actions-section {
    flex-direction: column;
    gap: 16px;
    padding: 20px;

    .support-buttons {
      flex-wrap: wrap;

      .ternary-support {
        flex-wrap: wrap;
      }
    }
  }

  .stats-section {
    .stats-grid {
      flex-direction: column;
      gap: 12px;
    }
  }
}

@media (max-width: 480px) {
  .title-section {
    .title-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
  }

  .meta-section {
    .meta-author {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }
  }

  .actions-section {
    .support-buttons {
      .ternary-support {
        flex-direction: column;
        width: 100%;

        :deep(button) {
          width: 100%;
          justify-content: center;
        }
      }
    }
  }
}
</style>
