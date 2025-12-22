<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-rich-html">
    <!-- Cover Image with Title -->
    <div v-if="coverUrl" class="rich-cover">
      <img :src="coverUrl" :alt="inquiry.title" class="cover-image" />
      <div class="cover-overlay">
        <div class="cover-content">
          <div class="cover-type-badge">
            <component :is="typeIconComponent" class="type-icon" :size="20" />
            <span class="type-label">{{ typeLabel }}</span>
          </div>
          <h1 class="cover-title">{{ inquiry.title }}</h1>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="rich-content-wrapper" :class="{ 'has-cover': !!coverUrl }">
      <!-- Header without cover -->
      <div v-if="!coverUrl" class="rich-header">
        <div class="header-top">
          <div class="type-badge">
            <component :is="typeIconComponent" class="type-icon" :size="20" />
            <span class="type-label">{{ typeLabel }}</span>
          </div>
        </div>
        
        <h1 class="rich-title">{{ inquiry.title }}</h1>
      </div>

      <!-- Meta Information -->
      <div class="rich-meta">
        <!-- Owner/Group Info -->
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
              <div v-if="inquiry.status?.created" class="meta-date">
                <component :is="InquiryGeneralIconsComponents.Calendar" class="date-icon" :size="14" />
                <span>{{ formattedDate }}</span>
              </div>
              
              <div v-if="locationPath" class="meta-location">
                <component :is="InquiryGeneralIconsComponents.Location" class="location-icon" :size="14" />
                <span>{{ truncatedLocation }}</span>
              </div>
              
              <div v-if="categoryPath" class="meta-category">
                <component :is="InquiryGeneralIconsComponents.Category" class="category-icon" :size="14" />
                <span>{{ truncatedCategory }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main HTML Content -->
      <div class="rich-content" v-html="sanitizedContent"></div>

      <!-- Expiry Warning (only if expired) -->
      <div v-if="isExpired" class="expiry-warning">
        <component :is="InquiryGeneralIconsComponents.AlertCircle" class="warning-icon" :size="18" />
        <span>{{ t('agora', 'This inquiry has expired') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import DOMPurify from 'dompurify'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { Inquiry } from '../../Types/index.ts'
import { useSessionStore } from '../../stores/session.ts'
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import { BaseEntry } from '../../Types/index.ts'

interface Props {
  inquiry: Inquiry
}

const props = defineProps<Props>()

const sessionStore = useSessionStore()

// Import icon components from utils/icons.ts
const InquiryGeneralIconsComponents = InquiryGeneralIcons

// Get type data
const inquiryTypes = computed(() => sessionStore.appSettings?.inquiryTypeTab || [])

const typeData = computed(() => getInquiryTypeData(props.inquiry.type, inquiryTypes.value))

const typeLabel = computed(() => typeData.value?.label || props.inquiry.type)

// Get type icon
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
    'document': 'Document',
    'proposal': 'Scale',
    'general': 'FolderMultiple',
    'draft': 'Empty',
  }
  
  const mappedIconName = iconMap[props.inquiry.type?.toLowerCase()] || 'FolderMultiple'
  return InquiryGeneralIconsComponents[mappedIconName]
})

// Cover image
const coverUrl = computed(() => {
  if (!props.inquiry.coverId || props.inquiry.coverId === 0) return ''
  return getNextcloudPreviewUrl(props.inquiry.coverId)
})

function getNextcloudPreviewUrl(fileId: number, x = 1200, y = 400, autoScale = true) {
  const baseUrl = window.location.origin
  return `${baseUrl}/index.php/core/preview?fileId=${fileId}&x=${x}&y=${y}&a=${autoScale}`
}

// Expiry check
const isExpired = computed(() => {
  if (!props.inquiry.configuration?.expire) return false
  const now = Date.now() / 1000
  return props.inquiry.configuration.expire < now
})

// Location and Category paths
function getHierarchyPath(items, targetId) {
  if (!items || !Array.isArray(items)) return ''
  
  const itemMap = {}

  items.forEach((item) => {
    itemMap[item.id] = item
  })

  if (!itemMap[targetId]) {
    return itemMap[1]?.name || t('agora', 'Not defined')
  }

  function buildPath(item) {
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

const locationPath = computed(() => getHierarchyPath(sessionStore.appSettings?.locationTab, props.inquiry.locationId))
const categoryPath = computed(() => getHierarchyPath(sessionStore.appSettings?.categoryTab, props.inquiry.categoryId))

// Truncated text
const truncatedLocation = computed(() => {
  if (!locationPath.value) return ''
  return locationPath.value.length > 30 
    ? `${locationPath.value.substring(0, 30)}…` 
    : locationPath.value
})

const truncatedCategory = computed(() => {
  if (!categoryPath.value) return ''
  return categoryPath.value.length > 30 
    ? `${categoryPath.value.substring(0, 30)}…` 
    : categoryPath.value
})

// Date formatting
const formattedDate = computed(() => {
  if (!props.inquiry.status?.created) return ''
  
  try {
    const date = new Date(props.inquiry.status.created * 1000)
    return date.toLocaleDateString('default', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return ''
  }
})

// Content
const sanitizedContent = computed(() => {
  if (!props.inquiry.description) {
    return `<div class="no-content">
              <p>${t('agora', 'No content available')}</p>
            </div>`
  }
  
  const content = props.inquiry.descriptionSafe || props.inquiry.description
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
</script>

<style lang="scss" scoped>
.inquiry-rich-html {
  border-radius: 12px;
  background: transparent;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

// Cover Image
.rich-cover {
  position: relative;
  height: 320px;
  width: 100%;
  
  .cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  .cover-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.7) 100%);
    display: flex;
    align-items: flex-end;
    padding: 32px;
  }
  
  .cover-content {
    width: 100%;
    color: white;
    
    .cover-type-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 16px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 24px;
      margin-bottom: 20px;
      backdrop-filter: blur(10px);
      
      .type-icon {
        color: var(--color-primary-element);
      }
      
      .type-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-main-text);
      }
    }
    
    .cover-title {
      font-size: 36px;
      font-weight: 700;
      line-height: 1.2;
      margin: 0;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
  }
}

// Content wrapper
.rich-content-wrapper {
  padding: 32px;
  
  &.has-cover {
    padding-top: 32px;
  }
}

// Header (without cover)
.rich-header {
  margin-bottom: 32px;
  
  .header-top {
    margin-bottom: 20px;
  }
  
  .type-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--color-primary-element-light), rgba(var(--color-primary-rgb), 0.1));
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-primary-element);
    border: 1px solid rgba(var(--color-primary-rgb), 0.2);
    
    .type-icon {
      color: var(--color-primary-element);
    }
    
    .type-label {
      font-weight: 600;
    }
  }
  
  .rich-title {
    font-size: 36px;
    font-weight: 700;
    line-height: 1.3;
    color: var(--color-main-text);
    margin: 0;
  }
}

// Meta Information
.rich-meta {
  margin-bottom: 40px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
  
  .meta-author {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .author-avatar {
      flex-shrink: 0;
      border: 3px solid var(--color-main-background);
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
        gap: 24px;
        flex-wrap: wrap;
        
        .meta-date,
        .meta-location,
        .meta-category {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 14px;
          color: var(--color-text-lighter);
          
          .date-icon,
          .location-icon,
          .category-icon {
            color: var(--color-text-maxcontrast);
          }
        }
      }
    }
  }
}

// Main HTML Content
.rich-content {
  font-size: 16px;
  line-height: 1.8;
  color: var(--color-main-text);
  
  // Rich text styling
  :deep(*) {
    margin: 0 0 20px 0;
    
    &:last-child {
      margin-bottom: 0;
    }
  }
  
  :deep(h1) {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-main-text);
    margin: 40px 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--color-border);
    
    &:first-child {
      margin-top: 0;
    }
  }
  
  :deep(h2) {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 32px 0 16px 0;
  }
  
  :deep(h3) {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 28px 0 14px 0;
  }
  
  :deep(h4) {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 24px 0 12px 0;
  }
  
  :deep(p) {
    margin-bottom: 20px;
    line-height: 1.8;
  }
  
  :deep(ul), :deep(ol) {
    padding-left: 28px;
    margin: 16px 0;
    
    li {
      margin-bottom: 8px;
      line-height: 1.6;
    }
  }
  
  :deep(blockquote) {
    border-left: 4px solid var(--color-primary-element);
    padding: 20px 24px;
    margin: 24px 0;
    background: var(--color-background-dark);
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: var(--color-text-lighter);
    
    p {
      margin-bottom: 0;
    }
    
    cite {
      display: block;
      margin-top: 12px;
      font-size: 14px;
      color: var(--color-text-maxcontrast);
      font-style: normal;
    }
  }
  
  :deep(code) {
    background: var(--color-background-dark);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    color: var(--color-main-text);
  }
  
  :deep(pre) {
    background: var(--color-background-dark);
    padding: 20px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 20px 0;
    
    code {
      background: transparent;
      padding: 0;
      font-size: 14px;
      line-height: 1.5;
    }
  }
  
  :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 24px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
  
  :deep(figure) {
    margin: 24px 0;
    
    img {
      margin: 0;
    }
    
    figcaption {
      text-align: center;
      font-size: 14px;
      color: var(--color-text-lighter);
      margin-top: 8px;
      font-style: italic;
    }
  }
  
  :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    
    th, td {
      padding: 12px 16px;
      border: 1px solid var(--color-border);
      text-align: left;
    }
    
    th {
      background: var(--color-background-dark);
      font-weight: 600;
      color: var(--color-main-text);
    }
    
    tr:nth-child(even) {
      background: var(--color-background-hover);
    }
  }
  
  :deep(a) {
    color: var(--color-primary-element);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 0.2s ease;
    
    &:hover {
      border-bottom-color: var(--color-primary-element);
    }
  }
  
  :deep(hr) {
    border: none;
    height: 1px;
    background: var(--color-border);
    margin: 32px 0;
  }
  
  :deep(.no-content) {
    text-align: center;
    padding: 60px 20px;
    color: var(--color-text-maxcontrast);
    font-style: italic;
    font-size: 18px;
  }
}

// Expiry Warning
.expiry-warning {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 24px;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
  border-radius: 12px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  margin-top: 40px;
  color: #ef4444;
  font-weight: 600;
  
  .warning-icon {
    color: #ef4444;
  }
}

// Responsive Design
@media (max-width: 768px) {
  .rich-cover {
    height: 240px;
    
    .cover-overlay {
      padding: 20px;
    }
    
    .cover-content {
      .cover-title {
        font-size: 28px;
      }
    }
  }
  
  .rich-content-wrapper {
    padding: 24px;
  }
  
  .rich-header {
    .rich-title {
      font-size: 28px;
    }
  }
  
  .rich-content {
    font-size: 15px;
    
    :deep(h1) {
      font-size: 24px;
    }
    
    :deep(h2) {
      font-size: 20px;
    }
    
    :deep(h3) {
      font-size: 18px;
    }
  }
  
  .rich-meta {
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
}

@media (max-width: 480px) {
  .rich-cover {
    height: 200px;
    
    .cover-content {
      .cover-title {
        font-size: 24px;
      }
    }
  }
  
  .rich-meta {
    .meta-author {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }
  }
}
</style>
