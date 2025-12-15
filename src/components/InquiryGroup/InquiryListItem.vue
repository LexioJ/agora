<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <NcListItem
    :name="inquiry.title"
    :details="listItemDetails"
    :active="isActiveListItem"
    :bold="isBold"
    :aria-label="listItemAriaLabel"
    :to="inquiryRoute"
    @click="handleClick"
  >
    <!-- Icon slot -->
    <template #icon>
      <component :is="typeIconComponent" :size="20" />
    </template>

    <!-- Subtitle with metadata -->
    <template #subname>
      <div class="inquiry-subtitle">
        <!-- Location -->
        <span v-if="locationPath" class="subtitle-location">
          <component :is="InquiryGeneralIconsComponents.IdCard" class="location-icon" :size="12" />
          {{ truncatedLocation }}
        </span>
        
        <!-- Category -->
        <span v-if="categoryPath" class="subtitle-category">
          <component :is="InquiryGeneralIconsComponents.FolderMultiple" class="category-icon" :size="12" />
          {{ truncatedCategory }}
        </span>
        
        <!-- Expiry -->
        <span v-if="showExpiryBadge" class="subtitle-expiry" :class="expiryBadgeClass">
          <component :is="InquiryGeneralIconsComponents.Clock" class="expiry-icon" :size="12" />
          {{ expiryText }}
        </span>
      </div>
    </template>
    
    <!-- Details slot - Support and Comments only -->
    <template #details>
      <div class="inquiry-details">
        <!-- Support -->
        <div 
           v-if="canSupport && inquiry.status?.countSupports !== undefined && inquiry.status?.countSupports >0" 
          class="detail-item supports" 
          :title="t('agora', '{count} supports', { count: inquiry.status.countSupports })" 
          @click.stop="handleSupportClick"
        >
          <TernarySupportIcon 
            v-if="inquiry.configuration?.supportMode === 'ternary'" 
            :support-value="inquiry.currentUserStatus?.supportValue || 0" 
            :size="16" 
          />
          <ThumbIcon 
            v-else 
            :supported="isSupported" 
            :size="16" 
          />
          <span class="support-count">{{ inquiry.status.countSupports }}</span>
          <span v-if="hasQuorum" class="quorum-compact">
            <span class="quorum-separator"> / </span>
            <span class="quorum-target">{{ quorumValue }}</span>
          </span>
        </div>
        
        <!-- Comments -->
        <div 
          v-if="canComment && inquiry.status?.countComments" 
          class="detail-item comments"
          :title="t('agora', '{count} comments', { count: inquiry.status.countComments })"
          @click.stop="handleCommentsClick"
        >
          <component :is="InquiryGeneralIconsComponents.MessageSquare" class="comments-icon" :size="16" />
          <span class="comments-count">{{ inquiry.status.countComments }}</span>
        </div>
      </div>
    </template>
  </NcListItem>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { useRouter } from 'vue-router'
import NcListItem from '@nextcloud/vue/components/NcListItem'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useSessionStore } from '../../stores/session.ts'
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import TernarySupportIcon from '../AppIcons/modules/TernarySupportIcon.vue'
import ThumbIcon from '../AppIcons/modules/ThumbIcon.vue'

// Import permissions if needed
// import { canSupport, canComment } from '../../utils/permissions.ts'

interface Props {
  inquiry: Inquiry
  isActive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false
})

const emit = defineEmits<{
  click: [inquiry: Inquiry]
  support: [inquiryId: number]
  comments: [inquiryId: number]
}>()

const router = useRouter()
const sessionStore = useSessionStore()

// Import icon components from utils/icons.ts
const InquiryGeneralIconsComponents = InquiryGeneralIcons

// Route for the list item
const inquiryRoute = computed(() => ({
  name: 'inquiry',
  params: { id: props.inquiry.id }
}))

// Permissions (simplified - adjust based on your actual permission logic)
const canSupport = computed(() => {
  // Simplified: always show if inquiry has support mode
  return props.inquiry.configuration?.supportMode !== undefined
})

const canComment = computed(() => {
  // Simplified: always show comments count
  return true
})

// List item properties
const listItemDetails = computed(() => {
  const details = []
  if (props.inquiry.owner?.displayName) {
    details.push(props.inquiry.owner.displayName)
  }
  if (props.inquiry.created_at) {
    const date = new Date(props.inquiry.created_at)
    details.push(date.toLocaleDateString())
  }
  return details.join(' • ')
})

const isActiveListItem = computed(() => {
  return props.isActive || props.inquiry.currentUserStatus?.hasSupported || false
})

const isBold = computed(() => {
  return props.inquiry.status?.inquiryStatus === 'open' && !props.inquiry.currentUserStatus?.hasRead
})

const listItemAriaLabel = computed(() => {
  return t('agora', 'Inquiry: {title}. {supports} supports, {comments} comments', {
    title: props.inquiry.title,
    supports: props.inquiry.status?.countSupports || 0,
    comments: props.inquiry.status?.countComments || 0
  })
})

// Get type icon
const inquiryTypes = computed(() => sessionStore.appSettings?.inquiryTypeTab || [])

const typeData = computed(() => {
  return getInquiryTypeData(props.inquiry.type, inquiryTypes.value)
})

const typeIconComponent = computed(() => {
  if (typeData.value?.icon) {
    const iconName = typeData.value.icon
    
    if (typeof iconName === 'string' && InquiryGeneralIconsComponents[iconName]) {
      return InquiryGeneralIconsComponents[iconName]
    }
  }
  
  const iconMap = {
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
  
  const mappedIconName = iconMap[props.inquiry.type?.toLowerCase()] || 'FolderMultiple'
  return InquiryGeneralIconsComponents[mappedIconName]
})

// Support
const isSupported = computed(() => props.inquiry.currentUserStatus?.hasSupported || false)

// Quorum
const hasQuorum = computed(() => props.inquiry.miscFields?.quorum)
const quorumValue = computed(() => props.inquiry.miscFields?.quorum || 0)

// Location and Category paths
const locationPath = computed(() => {
  if (!props.inquiry.locationId || !sessionStore.appSettings?.locationTab) return ''
  
  const getHierarchyPath = (items: any[], targetId: number): string => {
    const itemMap: Record<number, any> = {}
    
    items.forEach((item) => {
      itemMap[item.id] = item
    })
    
    if (!itemMap[targetId]) {
      return 'ID not found'
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
  if (!props.inquiry.categoryId || !sessionStore.appSettings?.categoryTab) return ''
  
  const getHierarchyPath = (items: any[], targetId: number): string => {
    const itemMap: Record<number, any> = {}
    
    items.forEach((item) => {
      itemMap[item.id] = item
    })
    
    if (!itemMap[targetId]) {
      return 'ID not found'
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
  return locationPath.value.length > 20 
    ? locationPath.value.substring(0, 20) + '…' 
    : locationPath.value
})

const truncatedCategory = computed(() => {
  if (!categoryPath.value) return ''
  return categoryPath.value.length > 20 
    ? categoryPath.value.substring(0, 20) + '…' 
    : categoryPath.value
})

// Expiry
const showExpiryBadge = computed(() => {
  return !!props.inquiry.expire && new Date(props.inquiry.expire) > new Date()
})

const expiryText = computed(() => {
  if (!props.inquiry.expire) return ''
  
  const expiryDate = new Date(props.inquiry.expire)
  const now = new Date()
  const diff = expiryDate.getTime() - now.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (days > 0) {
    return t('agora', '{days}d', { days })
  } else if (hours > 0) {
    return t('agora', '{hours}h', { hours })
  } else {
    return t('agora', 'Soon')
  }
})

const expiryBadgeClass = computed(() => {
  if (!props.inquiry.expire) return ''
  
  const expiryDate = new Date(props.inquiry.expire)
  const now = new Date()
  const diff = expiryDate.getTime() - now.getTime()
  const hours = diff / (1000 * 60 * 60)
  
  if (hours < 24) return 'expiry-soon'
  if (hours < 72) return 'expiry-warning'
  return 'expiry-normal'
})

// Handlers
function handleClick() {
  emit('click', props.inquiry)
}

function handleSupportClick() {
  emit('support', props.inquiry.id)
}

function handleCommentsClick() {
  emit('comments', props.inquiry.id)
}
</script>

<style lang="scss" scoped>
// Custom subtitle styling
.inquiry-subtitle {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 12px;
  color: var(--color-text-lighter);
  
  > span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
  }
  
  // Location and category styling
  .subtitle-location,
  .subtitle-category {
    .location-icon,
    .category-icon {
      color: var(--color-text-maxcontrast);
    }
  }
  
  // Expiry styling
  .subtitle-expiry {
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    
    .expiry-icon {
      color: inherit;
    }
    
    &.expiry-soon {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
      color: #ef4444;
    }
    
    &.expiry-warning {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
      color: #f59e0b;
    }
    
    &.expiry-normal {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
      color: #10b981;
    }
  }
}

// Details styling - Clean support and comments
.inquiry-details {
  display: flex;
  align-items: center;
  gap: 20px;
  
  .detail-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    
    // Support styling
    &.supports {
      color: var(--color-text-lighter);
      
      &:hover {
        color: var(--color-primary-element);
      }
      
      .support-count {
        font-weight: 600;
        font-size: 13px;
      }
      
      .quorum-compact {
        font-size: 11px;
        color: var(--color-text-maxcontrast);
        
        .quorum-separator {
          margin: 0 2px;
        }
        
        .quorum-target {
          font-weight: 600;
        }
      }
    }
    
    // Comments styling
    &.comments {
      color: var(--color-text-lighter);
      
      &:hover {
        color: #3b82f6;
        
        .comments-icon {
          color: #3b82f6;
        }
      }
      
      .comments-icon {
        color: var(--color-text-maxcontrast);
      }
      
      .comments-count {
        font-weight: 600;
        font-size: 13px;
      }
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .inquiry-subtitle {
    gap: 8px;
    
    > span {
      max-width: 120px;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
  
  .inquiry-details {
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .inquiry-subtitle {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    
    > span {
      max-width: 100%;
    }
  }
  
  .inquiry-details {
    gap: 12px;
  }
}
</style>
