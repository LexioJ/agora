<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-summary" :class="summaryClasses" @click="handleClick">
    <!-- Compact Mode -->
    <div v-if="compact" class="summary-compact">
      <div class="compact-icon">
        <component :is="typeIcon" class="icon" />
      </div>
      
      <div class="compact-content">
        <div class="compact-title" :title="inquiry.title">
          {{ truncatedTitle }}
        </div>
        
        <div class="compact-meta">
          <div v-if="inquiry.responsesCount" class="meta-responses">
            {{ inquiry.responsesCount }}
          </div>
          
          <div v-if="inquiry.supportsCount" class="meta-supports" @click.stop="handleSupportClick">
            {{ inquiry.supportsCount }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Regular Mode -->
    <div v-else class="summary-regular">
      <!-- Header -->
      <div class="summary-header">
        <div class="header-type">
          <component :is="typeIcon" class="type-icon" />
          <span class="type-label">{{ typeLabel }}</span>
        </div>
        
        <div v-if="inquiry.status" class="header-status" :class="statusClass">
          {{ statusText }}
        </div>
      </div>
      
      <!-- Title -->
      <div class="summary-title" :title="inquiry.title">
        {{ inquiry.title }}
      </div>
      
      <!-- Description -->
      <div v-if="inquiry.description && !compact" class="summary-description">
        {{ truncatedDescription }}
      </div>
      
      <!-- Footer -->
      <div class="summary-footer">
        <!-- Author -->
        <div class="footer-author">
          <NcAvatar
            :user="inquiry.owner?.id"
            :size="24"
            class="author-avatar"
            :show-name="false"
          />
          <span class="author-name">{{ truncatedAuthorName }}</span>
        </div>
        
        <!-- Stats -->
        <div class="footer-stats">
          <div v-if="inquiry.responsesCount" class="stat-item">
            <MessageIcon :size="12" />
            <span>{{ inquiry.responsesCount }}</span>
          </div>
          
          <div v-if="inquiry.supportsCount" class="stat-item supports" @click.stop="handleSupportClick">
            <HeartIcon :size="12" :fill="isSupported ? 'var(--color-primary-element)' : 'none'" />
            <span>{{ inquiry.supportsCount }}</span>
          </div>
          
          <div v-if="inquiry.created_at" class="stat-item">
            <CalendarIcon :size="12" />
            <span>{{ timeAgo }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { useRouter } from 'vue-router'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import MessageIcon from 'vue-material-design-icons/Message.vue'
import HeartIcon from 'vue-material-design-icons/Heart.vue'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useAppSettingsStore } from '../../stores/appSettings.ts'

interface Props {
  inquiry: Inquiry
  compact?: boolean
  interactive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  compact: false,
  interactive: true
})

const emit = defineEmits<{
  click: [inquiry: Inquiry]
  support: [inquiryId: number]
}>()

const router = useRouter()
const appSettingsStore = useAppSettingsStore()

// Get inquiry types
const inquiryTypes = computed(() => appSettingsStore.inquiryTypeTab || [])

// Summary classes
const summaryClasses = computed(() => ({
  'is-compact': props.compact,
  'is-interactive': props.interactive
}))

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

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)

// Truncated text
const truncatedTitle = computed(() => {
  if (!props.inquiry.title) return ''
  return props.inquiry.title.length > 40 
    ? props.inquiry.title.substring(0, 40) + '...' 
    : props.inquiry.title
})

const truncatedDescription = computed(() => {
  if (!props.inquiry.description) return ''
  const text = props.inquiry.description.replace(/<[^>]*>/g, '')
  return text.length > 80 
    ? text.substring(0, 80) + '...' 
    : text
})

const truncatedAuthorName = computed(() => {
  if (!props.inquiry.owner?.displayName) return ''
  const name = props.inquiry.owner.displayName
  return name.length > 20 
    ? name.substring(0, 20) + '...' 
    : name
})

// Time ago
const timeAgo = computed(() => {
  if (!props.inquiry.created_at) return ''
  
  const created = new Date(props.inquiry.created_at)
  const now = new Date()
  const diff = now.getTime() - created.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor(diff / (1000 * 60))
  
  if (days > 0) {
    return t('agora', '{days}d', { days })
  } else if (hours > 0) {
    return t('agora', '{hours}h', { hours })
  } else if (minutes > 0) {
    return t('agora', '{minutes}m', { minutes })
  } else {
    return t('agora', 'Just now')
  }
})

// Handlers
function handleClick() {
  if (props.interactive) {
    emit('click', props.inquiry)
    viewInquiry()
  }
}

function viewInquiry() {
  router.push({
    name: 'inquiry',
    params: { id: props.inquiry.id }
  })
}

function handleSupportClick() {
  emit('support', props.inquiry.id)
}
</script>

<style lang="scss" scoped>
.inquiry-summary {
  &.is-interactive {
    cursor: pointer;
    
    &:hover {
      .summary-regular {
        transform: translateX(4px);
        border-color: var(--color-primary-element);
      }
      
      .summary-compact {
        background: var(--color-background-hover);
      }
    }
  }
}

// Compact Mode
.summary-compact {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 10px;
  transition: background-color 0.2s ease;
  
  .compact-icon {
    flex-shrink: 0;
    
    .icon {
      width: 20px;
      height: 20px;
      color: var(--color-primary-element);
    }
  }
  
  .compact-content {
    flex: 1;
    min-width: 0;
  }
  
  .compact-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-main-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
  }
  
  .compact-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    color: var(--color-text-maxcontrast);
    
    .meta-responses,
    .meta-supports {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .meta-supports {
      cursor: pointer;
      
      &:hover {
        color: var(--color-primary-element);
      }
    }
  }
}

// Regular Mode
.summary-regular {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.header-type {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: var(--color-background-dark);
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-lighter);
  
  .type-icon {
    width: 12px;
    height: 12px;
    color: var(--color-primary-element);
  }
}

.header-status {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 10px;
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

.summary-title {
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--color-main-text);
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.summary-description {
  font-size: 13px;
  line-height: 1.4;
  color: var(--color-text-lighter);
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.summary-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.footer-author {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
  
  .author-avatar {
    flex-shrink: 0;
    border: 1px solid white;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  }
  
  .author-name {
    font-size: 12px;
    color: var(--color-text-lighter);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.footer-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
  
  .stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--color-text-maxcontrast);
    
    &.supports {
      cursor: pointer;
      
      &:hover {
        color: var(--color-primary-element);
      }
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .summary-regular {
    padding: 14px;
  }
  
  .summary-title {
    font-size: 14px;
  }
  
  .summary-description {
    font-size: 12px;
  }
  
  .footer-stats {
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .summary-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .footer-stats {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
