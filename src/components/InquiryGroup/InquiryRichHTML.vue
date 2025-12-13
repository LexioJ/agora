<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-rich-html">
    <!-- Header -->
    <div class="rich-header">
      <div class="rich-type">
        <component :is="typeIcon" class="type-icon" />
        <span class="type-label">{{ typeLabel }}</span>
      </div>
      
      <h1 class="rich-title">{{ inquiry.title }}</h1>
      
      <div class="rich-meta">
        <div class="meta-author">
          <NcAvatar
            :user="inquiry.owner?.id"
            :size="32"
            class="author-avatar"
            :show-name="false"
          />
          <span class="author-name">{{ inquiry.owner?.displayName }}</span>
        </div>
        
        <div v-if="inquiry.created_at" class="meta-date">
          <CalendarIcon :size="14" />
          <span>{{ formatDate(inquiry.created_at) }}</span>
        </div>
      </div>
    </div>
    
    <!-- Main HTML Content -->
    <div class="rich-content" v-html="sanitizedContent"></div>
    
    <!-- Stats Footer -->
    <div class="rich-footer">
      <div class="footer-stats">
        <div class="stat-item">
          <MessageIcon :size="16" />
          <span class="stat-value">{{ inquiry.responsesCount || 0 }}</span>
          <span class="stat-label">{{ t('agora', 'Responses') }}</span>
        </div>
        
        <div class="stat-item supports" @click="handleSupportClick">
          <HeartIcon :size="16" :fill="isSupported ? 'var(--color-primary-element)' : 'none'" />
          <span class="stat-value">{{ inquiry.supportsCount || 0 }}</span>
          <span class="stat-label">{{ t('agora', 'Supports') }}</span>
        </div>
        
        <div class="stat-item">
          <UsersIcon :size="16" />
          <span class="stat-value">{{ participantsCount }}</span>
          <span class="stat-label">{{ t('agora', 'Participants') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import MessageIcon from 'vue-material-design-icons/Message.vue'
import HeartIcon from 'vue-material-design-icons/Heart.vue'
import UsersIcon from 'vue-material-design-icons/AccountGroup.vue'
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

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)
const participantsCount = computed(() => props.inquiry.status?.countParticipants || 0)

// Content
const sanitizedContent = computed(() => {
  if (!props.inquiry.description) {
    return `<p class="no-content">${t('agora', 'No content available')}</p>`
  }
  return DOMPurify.sanitize(props.inquiry.description)
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

// Handler
function handleSupportClick() {
  emit('support', props.inquiry.id)
}
</script>

<style lang="scss" scoped>
.inquiry-rich-html {
  background: white;
  border-radius: 16px;
  overflow: hidden;
}

.rich-header {
  padding: 32px;
  background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-background-dark) 100%);
  border-bottom: 1px solid var(--color-border);
  
  .rich-type {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    
    .type-icon {
      width: 18px;
      height: 18px;
      color: var(--color-primary-element);
    }
    
    .type-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--color-primary-text);
    }
  }
  
  .rich-title {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.3;
    color: var(--color-main-text);
    margin: 0 0 24px 0;
  }
  
  .rich-meta {
    display: flex;
    align-items: center;
    gap: 24px;
    
    .meta-author {
      display: flex;
      align-items: center;
      gap: 12px;
      
      .author-avatar {
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }
      
      .author-name {
        font-size: 16px;
        font-weight: 600;
        color: var(--color-main-text);
      }
    }
    
    .meta-date {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--color-text-lighter);
    }
  }
}

.rich-content {
  padding: 32px;
  font-size: 16px;
  line-height: 1.8;
  color: var(--color-text-lighter);
  
  :deep(*) {
    margin: 0 0 16px 0;
    
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
  }
  
  :deep(h2) {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 24px 0 12px 0;
  }
  
  :deep(h3) {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 20px 0 10px 0;
  }
  
  :deep(p) {
    margin-bottom: 16px;
  }
  
  :deep(ul), :deep(ol) {
    padding-left: 32px;
    margin: 16px 0;
  }
  
  :deep(li) {
    margin-bottom: 8px;
  }
  
  :deep(blockquote) {
    border-left: 4px solid var(--color-primary-element);
    padding-left: 20px;
    margin: 20px 0;
    font-style: italic;
    color: var(--color-text-lighter);
  }
  
  :deep(code) {
    background: var(--color-background-dark);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
  }
  
  :deep(pre) {
    background: var(--color-background-dark);
    padding: 16px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 20px 0;
    
    code {
      background: transparent;
      padding: 0;
    }
  }
  
  :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
  }
  
  :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    
    th, td {
      padding: 12px;
      border: 1px solid var(--color-border);
      text-align: left;
    }
    
    th {
      background: var(--color-background-dark);
      font-weight: 600;
      color: var(--color-main-text);
    }
  }
  
  :deep(.no-content) {
    text-align: center;
    padding: 40px;
    color: var(--color-text-maxcontrast);
    font-style: italic;
  }
}

.rich-footer {
  padding: 24px 32px;
  background: var(--color-background-dark);
  border-top: 1px solid var(--color-border);
}

.footer-stats {
  display: flex;
  justify-content: center;
  gap: 48px;
  
  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 24px;
    background: white;
    border-radius: 12px;
    min-width: 120px;
    transition: all 0.3s ease;
    
    &.supports {
      cursor: pointer;
      
      &:hover {
        background: var(--color-background-darker);
        transform: translateY(-2px);
      }
    }
    
    .stat-value {
      font-size: 24px;
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

// Responsive
@media (max-width: 768px) {
  .rich-header {
    padding: 24px;
    
    .rich-title {
      font-size: 22px;
    }
  }
  
  .rich-content {
    padding: 24px;
    font-size: 15px;
  }
  
  .rich-footer {
    padding: 20px 24px;
  }
  
  .footer-stats {
    gap: 16px;
    
    .stat-item {
      padding: 12px 16px;
      min-width: 100px;
      
      .stat-value {
        font-size: 20px;
      }
    }
  }
}

@media (max-width: 480px) {
  .rich-header {
    padding: 20px;
    
    .rich-meta {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }
  }
  
  .footer-stats {
    flex-direction: column;
    gap: 12px;
    
    .stat-item {
      flex-direction: row;
      justify-content: space-between;
      min-width: auto;
      
      .stat-label {
        margin-left: auto;
      }
    }
  }
}
</style>
