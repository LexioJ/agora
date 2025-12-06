<!--
- SPDX-FileCopyrightText: 2024 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-vignette" :class="`vignette-${inquiryType}`" @click="handleClick">
    <div class="vignette-header">
      <div class="vignette-icon">
        <component :is="typeData.icon" v-if="typeData.icon" />
        <span v-else class="default-icon">{{ getTypeInitial(inquiry.inquiry_type) }}</span>
      </div>
      <div class="vignette-title">
        <h4>{{ inquiry.title }}</h4>
        <p class="vignette-subtitle">{{ getSubtitle(inquiry) }}</p>
      </div>
    </div>
    
    <div v-if="inquiry.description" class="vignette-description">
      {{ truncateDescription(inquiry.description) }}
    </div>
    
    <div class="vignette-footer">
      <div class="vignette-meta">
        <span v-if="inquiry.status" class="status-badge" :class="`status-${inquiry.status}`">
          {{ getStatusText(inquiry.status) }}
        </span>
        <span v-if="inquiry.date" class="date-info">{{ formatDate(inquiry.date) }}</span>
      </div>
      
      <NcButton class="action-button" :class="`action-${getActionType(inquiry.status)}`">
        {{ getActionText(inquiry.status, inquiry.inquiry_type) }}
      </NcButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import { useSessionStore } from '../../stores/session.ts'

const sessionStore = useSessionStore()

const props = defineProps<{
  inquiry: any
}>()

const emit = defineEmits(['click'])

// Get inquiry type data
const inquiryType = computed(() => props.inquiry?.inquiry_type || 'unknown')

const typeData = computed(() => {
  const inquiryTypes = sessionStore.appSettings.inquiryTypeTab || []
  return getInquiryTypeData(inquiryType.value, inquiryTypes) || {
    label: inquiryType.value,
    icon: null,
    description: ''
  }
})

// Helper functions
function truncateDescription(text: string, maxLength = 120) {
  if (!text) return ''
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

function getTypeInitial(type: string) {
  return type ? type.charAt(0).toUpperCase() : '?'
}

function getSubtitle(inquiry: any) {
  // Get subtitle from inquiry type data first, then fallback to defaults
  if (typeData.value.description) {
    return t('agora', typeData.value.description)
  }
  
  // Fallback defaults based on inquiry type
  switch (inquiry.inquiry_type) {
    case 'proposal':
      return t('agora', 'Citizen proposal')
    case 'meeting':
      return t('agora', 'Public meeting')
    case 'news':
      return t('agora', 'Announcement')
    case 'poll':
      return t('agora', 'Poll - open vote')
    case 'survey':
      return t('agora', 'Survey')
    case 'announcement':
      return t('agora', 'Official announcement')
    default:
      return t('agora', 'Inquiry')
  }
}

function getStatusText(status: string) {
  switch (status) {
    case 'open': return t('agora', 'Open')
    case 'closed': return t('agora', 'Closed')
    case 'draft': return t('agora', 'Draft')
    case 'pending': return t('agora', 'Pending')
    case 'archived': return t('agora', 'Archived')
    default: return status
  }
}

function getActionType(status: string) {
  switch (status) {
    case 'open': return 'open'
    case 'closed': return 'closed'
    case 'vote': return 'vote'
    default: return 'read'
  }
}

function getActionText(status: string, type: string) {
  switch (status) {
    case 'open':
      if (type === 'poll' || type === 'survey') return t('agora', 'Vote')
      return t('agora', 'Open')
    case 'closed': return t('agora', 'Closed')
    case 'vote': return t('agora', 'Vote')
    default:
      if (type === 'news' || type === 'announcement') return t('agora', 'Read')
      return t('agora', 'View')
  }
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString()
  } catch (e) {
    return dateString
  }
}

function handleClick() {
  emit('click')
}
</script>

<style lang="scss" scoped>
.inquiry-vignette {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 100%;
  min-height: 180px;
  
  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  }
  
  // Type-specific styling
  &.vignette-proposal {
    border-left: 4px solid var(--color-success);
  }
  
  &.vignette-meeting {
    border-left: 4px solid var(--color-warning);
  }
  
  &.vignette-news {
    border-left: 4px solid var(--color-info);
  }
  
  &.vignette-poll {
    border-left: 4px solid var(--color-error);
  }
  
  &.vignette-survey {
    border-left: 4px solid var(--color-primary-element);
  }
  
  &.vignette-announcement {
    border-left: 4px solid var(--color-text-maxcontrast);
  }
}

.vignette-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.vignette-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background-dark);
  border-radius: 12px;
  flex-shrink: 0;
  
  .default-icon {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-primary-element);
  }
  
  :deep(svg) {
    width: 24px;
    height: 24px;
    color: var(--color-primary-element);
  }
}

.vignette-title {
  flex: 1;
  
  h4 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: var(--color-main-text);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .vignette-subtitle {
    font-size: 13px;
    color: var(--color-text-lighter);
    margin: 0;
    line-height: 1.3;
  }
}

.vignette-description {
  font-size: 14px;
  color: var(--color-text-lighter);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

.vignette-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.vignette-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  
  &.status-open {
    background: var(--color-success);
    color: white;
  }
  
  &.status-closed {
    background: var(--color-error);
    color: white;
  }
  
  &.status-draft {
    background: var(--color-warning);
    color: white;
  }
  
  &.status-pending {
    background: var(--color-info);
    color: white;
  }
  
  &.status-archived {
    background: var(--color-text-maxcontrast);
    color: white;
  }
}

.date-info {
  font-size: 12px;
  color: var(--color-text-lighter);
}

.action-button {
  min-width: 80px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  
  &.action-open {
    background: var(--color-success);
    color: white;
    border-color: var(--color-success);
    
    &:hover {
      background: var(--color-success-hover);
      border-color: var(--color-success-hover);
    }
  }
  
  &.action-closed {
    background: var(--color-error);
    color: white;
    border-color: var(--color-error);
    
    &:hover {
      background: var(--color-error-hover);
      border-color: var(--color-error-hover);
    }
  }
  
  &.action-vote {
    background: var(--color-warning);
    color: white;
    border-color: var(--color-warning);
    
    &:hover {
      background: var(--color-warning-hover);
      border-color: var(--color-warning-hover);
    }
  }
  
  &.action-read {
    background: var(--color-primary-element);
    color: white;
    border-color: var(--color-primary-element);
    
    &:hover {
      background: var(--color-primary-element-hover);
      border-color: var(--color-primary-element-hover);
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .inquiry-vignette {
    padding: 16px;
  }
  
  .vignette-header {
    gap: 12px;
  }
  
  .vignette-icon {
    width: 40px;
    height: 40px;
  }
  
  .vignette-title h4 {
    font-size: 15px;
  }
  
  .vignette-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .action-button {
    width: 100%;
  }
}
</style>
