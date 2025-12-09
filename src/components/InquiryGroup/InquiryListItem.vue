<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-list-item" :class="listItemClasses" @click="handleClick">
    <!-- Type Icon -->
    <div class="item-icon">
      <component :is="typeIcon" class="icon" />
    </div>
    
    <!-- Content -->
    <div class="item-content">
      <!-- Header -->
      <div class="item-header">
        <h3 class="item-title" :title="inquiry.title">
          {{ inquiry.title }}
        </h3>
        
        <div v-if="inquiry.status" class="item-status" :class="statusClass">
          {{ statusText }}
        </div>
      </div>
      
      <!-- Metadata -->
      <div class="item-meta">
        <div class="meta-left">
          <!-- Author -->
          <div class="meta-author">
            <NcAvatar
              :user="inquiry.owner?.id"
              :size="20"
              class="author-avatar"
              :show-name="false"
            />
            <span class="author-name">{{ inquiry.owner?.displayName }}</span>
          </div>
          
          <!-- Date -->
          <div v-if="inquiry.created_at" class="meta-date">
            <CalendarIcon :size="12" />
            <span>{{ formatDate(inquiry.created_at) }}</span>
          </div>
          
          <!-- Responses -->
          <div v-if="inquiry.responsesCount" class="meta-responses">
            <MessageIcon :size="12" />
            <span>{{ inquiry.responsesCount }}</span>
          </div>
        </div>
        
        <!-- Supports -->
        <div v-if="inquiry.supportsCount" class="meta-supports" @click.stop="handleSupportClick">
          <HeartIcon :size="14" :fill="isSupported ? 'var(--color-primary-element)' : 'none'" />
          <span>{{ inquiry.supportsCount }}</span>
        </div>
      </div>
    </div>
    
    <!-- Arrow -->
    <div class="item-arrow">
      <ChevronRightIcon :size="20" />
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
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useAppSettingsStore } from '../../stores/appSettings.ts'

interface Props {
  inquiry: Inquiry
  isActive?: boolean
  dense?: boolean
  interactive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false,
  dense: false,
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

// List item classes
const listItemClasses = computed(() => ({
  'is-active': props.isActive,
  'is-dense': props.dense,
  'is-interactive': props.interactive
}))

// Get type data
const typeData = computed(() => {
  return getInquiryTypeData(props.inquiry.inquiry_type, inquiryTypes.value)
})

const typeIcon = computed(() => typeData.value?.icon || '📝')

// Status
const statusClass = computed(() => {
  switch (props.inquiry.status) {
    case 'open': return 'status-open'
    case 'closed': return 'status-closed'
    case 'draft': return 'status-draft'
    default: return 'status-unknown'
  }
})

const statusText = computed(() => {
  switch (props.inquiry.status) {
    case 'open': return t('agora', 'Open')
    case 'closed': return t('agora', 'Closed')
    case 'draft': return t('agora', 'Draft')
    default: return props.inquiry.status || ''
  }
})

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)

// Format date
function formatDate(dateString: string) {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString()
  } catch {
    return dateString
  }
}

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
.inquiry-list-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: white;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  
  &.is-interactive {
    cursor: pointer;
    
    &:hover {
      transform: translateX(4px);
      border-color: var(--color-primary-element);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
      
      .item-arrow {
        transform: translateX(4px);
      }
    }
  }
  
  &.is-active {
    background: var(--color-primary-light);
    border-color: var(--color-primary-element);
    
    .item-title {
      color: var(--color-primary-text);
    }
  }
  
  &.is-dense {
    padding: 12px 16px;
    gap: 12px;
    
    .item-icon .icon {
      width: 20px;
      height: 20px;
    }
    
    .item-title {
      font-size: 15px;
    }
  }
}

.item-icon {
  flex-shrink: 0;
  
  .icon {
    width: 24px;
    height: 24px;
    color: var(--color-primary-element);
  }
}

.item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.item-title {
  flex: 1;
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--color-main-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-status {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  flex-shrink: 0;
  
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

.item-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.meta-left {
  display: flex;
  align-items: center;
  gap: 20px;
  flex: 1;
}

.meta-author {
  display: flex;
  align-items: center;
  gap: 8px;
  
  .author-avatar {
    border: 2px solid white;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  }
  
  .author-name {
    font-size: 13px;
    color: var(--color-text-lighter);
  }
}

.meta-date,
.meta-responses {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  
  svg {
    opacity: 0.7;
  }
}

.meta-supports {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--color-background-dark);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-main-text);
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  
  &:hover {
    background: var(--color-background-darker);
    color: var(--color-primary-element);
  }
  
  svg {
    transition: fill 0.2s ease;
  }
}

.item-arrow {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
  transition: transform 0.2s ease;
}

// Responsive
@media (max-width: 768px) {
  .inquiry-list-item {
    padding: 14px 16px;
    gap: 12px;
  }
  
  .item-icon .icon {
    width: 20px;
    height: 20px;
  }
  
  .item-title {
    font-size: 15px;
  }
  
  .meta-left {
    gap: 12px;
    flex-wrap: wrap;
  }
  
  .meta-author {
    min-width: 0;
    
    .author-name {
      max-width: 100px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
}
</style>
