<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-full">
    <!-- Header -->
    <div class="full-header">
      <!-- Cover Image -->
      <div v-if="coverUrl" class="full-cover">
        <img :src="coverUrl" :alt="inquiry.title" class="cover-image" />
        <div class="cover-gradient"></div>
      </div>
      
      <!-- Header Content -->
      <div class="header-content" :class="{ 'has-cover': coverUrl }">
        <div class="header-meta">
          <!-- Type -->
          <div class="type-badge">
            <component :is="typeIcon" class="type-icon" />
            <span class="type-label">{{ typeLabel }}</span>
          </div>
          
          <!-- Status -->
          <div class="status-badge" :class="statusClass">
            {{ statusText }}
          </div>
          
          <!-- Dates -->
          <div class="date-badge">
            <CalendarIcon :size="14" />
            <span>{{ formatDate(inquiry.created_at) }}</span>
          </div>
        </div>
        
        <!-- Title -->
        <h1 class="full-title">{{ inquiry.title }}</h1>
        
        <!-- Author -->
        <div class="author-section">
          <NcAvatar
            :user="inquiry.owner?.id"
            :size="40"
            class="author-avatar"
            :show-name="false"
          />
          <div class="author-info">
            <div class="author-name">{{ inquiry.owner?.displayName }}</div>
            <div class="author-role">{{ t('agora', 'Inquiry Author') }}</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="full-content">
      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-value">{{ inquiry.responsesCount || 0 }}</div>
          <div class="stat-label">{{ t('agora', 'Responses') }}</div>
        </div>
        
        <div class="stat-card supports" @click="handleSupportClick">
          <div class="stat-value">{{ inquiry.supportsCount || 0 }}</div>
          <div class="stat-label">{{ t('agora', 'Supports') }}</div>
          <HeartIcon 
            :size="20" 
            :fill="isSupported ? 'var(--color-primary-element)' : 'none'" 
            class="support-icon"
          />
        </div>
        
        <div class="stat-card">
          <div class="stat-value">{{ participantsCount }}</div>
          <div class="stat-label">{{ t('agora', 'Participants') }}</div>
        </div>
      </div>
      
      <!-- Description -->
      <div v-if="inquiry.description" class="description-section">
        <h2 class="section-title">{{ t('agora', 'Description') }}</h2>
        <div class="description-content" v-html="sanitizedDescription"></div>
      </div>
      
      <!-- Additional Content -->
      <div v-if="hasAdditionalContent" class="additional-sections">
        <!-- Custom sections based on inquiry type -->
        <slot name="additional-sections" />
      </div>
      
      <!-- Actions -->
      <div class="action-buttons">
        <NcButton type="primary" class="participate-button">
          {{ t('agora', 'Participate') }}
        </NcButton>
        
        <NcButton type="secondary" class="share-button">
          {{ t('agora', 'Share') }}
        </NcButton>
        
        <div class="secondary-actions">
          <NcButton type="tertiary" class="comment-button">
            {{ t('agora', 'Comment') }}
          </NcButton>
          
          <NcButton type="tertiary" class="follow-button">
            {{ t('agora', 'Follow') }}
          </NcButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import HeartIcon from 'vue-material-design-icons/Heart.vue'
import DOMPurify from 'dompurify'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useAppSettingsStore } from '../../stores/appSettings.ts'

interface Props {
  inquiry: Inquiry
}

const props = defineProps<Props>()

const emit = defineEmits<{
  support: [inquiryId: number]
  participate: [inquiryId: number]
  comment: [inquiryId: number]
}>()

const appSettingsStore = useAppSettingsStore()

// Get inquiry types
const inquiryTypes = computed(() => appSettingsStore.inquiryTypeTab || [])

// Get type data
const typeData = computed(() => {
  return getInquiryTypeData(props.inquiry.type, inquiryTypes.value)
})

const typeIcon = computed(() => typeData.value?.icon || '📝')
const typeLabel = computed(() => typeData.value?.label || props.inquiry.type)

// Status
const statusClass = computed(() => {
return appSettingsStore.getStatusByKey(props.inquiry.type,props.inquiry.status.inquiryStatus)
})

const statusText = computed(() => {
return appSettingsStore.getStatusByKey(props.inquiry.type,props.inquiry.status.inquiryStatus)
})


// Cover image
const coverUrl = computed(() => {
  if (props.inquiry.coverId) {
    const baseUrl = window.location.origin
    return `${baseUrl}/index.php/core/preview?fileId=${props.inquiry.coverId}&x=1200&y=400&a=1`
  }
  return null
})

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)
const participantsCount = computed(() => props.inquiry.status?.countParticipants || 0)

// Description
const sanitizedDescription = computed(() => {
  if (!props.inquiry.description) return ''
  return DOMPurify.sanitize(props.inquiry.description)
})

// Additional content
const hasAdditionalContent = computed(() => {
  return props.inquiry.miscFields && Object.keys(props.inquiry.miscFields).length > 0
})

// Format date
function formatDate(dateString: string) {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString('default', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

// Handlers
function handleSupportClick() {
  emit('support', props.inquiry.id)
}

function handleParticipate() {
  emit('participate', props.inquiry.id)
}

function handleComment() {
  emit('comment', props.inquiry.id)
}
</script>

<style lang="scss" scoped>
.inquiry-full {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.full-header {
  position: relative;
  
  .full-cover {
    position: relative;
    height: 280px;
    overflow: hidden;
    
    .cover-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .cover-gradient {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 60%;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
    }
  }
}

.header-content {
  padding: 40px;
  
  &.has-cover {
    position: relative;
    margin-top: -60px;
    background: white;
    border-radius: 20px 20px 0 0;
    padding-top: 60px;
  }
}

.header-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--color-primary-light);
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-primary-text);
  
  .type-icon {
    width: 18px;
    height: 18px;
    color: var(--color-primary-element);
  }
}

.status-badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  
  &.status-open {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
  }
  
  &.status-closed {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
  }
  
  &.status-draft {
    background: rgba(148, 163, 184, 0.1);
    color: #64748b;
  }
  
  &.status-unknown {
    background: var(--color-background-dark);
    color: var(--color-text-lighter);
  }
}

.date-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: var(--color-background-dark);
  border-radius: 20px;
  font-size: 14px;
  color: var(--color-text-lighter);
  
  svg {
    opacity: 0.7;
  }
}

.full-title {
  font-size: 36px;
  font-weight: 800;
  line-height: 1.2;
  color: var(--color-main-text);
  margin: 0 0 32px 0;
}

.author-section {
  display: flex;
  align-items: center;
  gap: 16px;
  
  .author-avatar {
    border: 3px solid white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  }
  
  .author-info {
    .author-name {
      font-size: 18px;
      font-weight: 600;
      color: var(--color-main-text);
      margin-bottom: 4px;
    }
    
    .author-role {
      font-size: 14px;
      color: var(--color-text-lighter);
    }
  }
}

.full-content {
  padding: 40px;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  margin-bottom: 40px;
}

.stat-card {
  position: relative;
  background: var(--color-background-dark);
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  transition: all 0.3s ease;
  
  &.supports {
    cursor: pointer;
    
    &:hover {
      background: var(--color-background-darker);
      transform: translateY(-2px);
      
      .support-icon {
        transform: scale(1.1);
      }
    }
  }
  
  .stat-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--color-main-text);
    margin-bottom: 8px;
  }
  
  .stat-label {
    font-size: 14px;
    color: var(--color-text-lighter);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .support-icon {
    position: absolute;
    top: 16px;
    right: 16px;
    color: var(--color-primary-element);
    transition: transform 0.3s ease;
  }
}

.description-section {
  margin-bottom: 40px;
  
  .section-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-main-text);
    margin: 0 0 20px 0;
  }
  
  .description-content {
    font-size: 16px;
    line-height: 1.7;
    color: var(--color-text-lighter);
    
    :deep(*) {
      margin: 0 0 16px 0;
      
      &:last-child {
        margin-bottom: 0;
      }
    }
    
    :deep(h2) {
      font-size: 20px;
      font-weight: 600;
      color: var(--color-main-text);
      margin: 32px 0 16px 0;
    }
    
    :deep(h3) {
      font-size: 18px;
      font-weight: 600;
      color: var(--color-main-text);
      margin: 24px 0 12px 0;
    }
    
    :deep(ul), :deep(ol) {
      padding-left: 24px;
      margin: 16px 0;
    }
    
    :deep(li) {
      margin-bottom: 8px;
    }
  }
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-top: 40px;
  border-top: 1px solid var(--color-border);
  
  .participate-button,
  .share-button {
    width: 100%;
    justify-content: center;
    font-size: 16px;
    padding: 16px 24px;
    border-radius: 12px;
  }
  
  .secondary-actions {
    display: flex;
    gap: 12px;
    
    .comment-button,
    .follow-button {
      flex: 1;
      justify-content: center;
    }
  }
}

// Responsive
@media (max-width: 992px) {
  .full-title {
    font-size: 28px;
  }
  
  .header-content,
  .full-content {
    padding: 32px;
  }
  
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .full-header .full-cover {
    height: 200px;
  }
  
  .header-content {
    padding: 24px;
    
    &.has-cover {
      margin-top: -40px;
      padding-top: 40px;
    }
  }
  
  .full-title {
    font-size: 24px;
    margin-bottom: 24px;
  }
  
  .full-content {
    padding: 24px;
  }
  
  .stats-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .stat-card {
    padding: 20px;
    
    .stat-value {
      font-size: 28px;
    }
  }
  
  .description-section {
    .section-title {
      font-size: 20px;
    }
    
    .description-content {
      font-size: 15px;
    }
  }
  
  .action-buttons {
    .participate-button,
    .share-button {
      padding: 14px 20px;
    }
  }
}

@media (max-width: 480px) {
  .header-meta {
    gap: 8px;
    
    .type-badge,
    .status-badge,
    .date-badge {
      padding: 6px 12px;
      font-size: 12px;
    }
  }
  
  .author-section {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}
</style>
