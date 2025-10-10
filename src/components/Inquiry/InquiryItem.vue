<!--
	- SPDX-FileCopyrightText: 2018 Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { computed } from 'vue'
import { showSuccess } from '@nextcloud/dialogs'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { ThumbIcon } from '../AppIcons'
import { useSupportsStore } from '../../stores/supports'
import {
  canComment,
  canSupport,
  createPermissionContextForContent,
  ContentType,
} from '../../utils/permissions.ts'

import { InquiryGeneralIcons, StatusIcons, BadgeIcons } from '../../utils/icons.ts'
import { useInquiryStore, Inquiry } from '../../stores/inquiry'
import { useInquiriesStore } from '../../stores/inquiries'
import { usePreferencesStore } from '../../stores/preferences.ts'
import { useSessionStore } from '../../stores/session.ts'

const inquiryStore = useInquiryStore()
const inquiriesStore = useInquiriesStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()
const supportsStore = useSupportsStore()

interface Props {
  inquiry: Inquiry
  noLink?: boolean
  gridView?: boolean
}

const { inquiry, noLink = false, gridView = false } = defineProps<Props>()

const context = computed(() =>
  createPermissionContextForContent(
    ContentType.Inquiry,
    inquiry.owner.id,
    inquiry.configuration.access,
    inquiry.status.isLocked,
    inquiry.status.isExpired,
    inquiry.status.deletionDate > 0,
    inquiry.status.isArchived,
    inquiry.inquiryGroups.length > 0,
    inquiry.inquiryGroups,
    inquiry.type
  )
)

const onToggleSupport = async () => {
  supportsStore.toggleSupport(inquiry.id, sessionStore.currentUser.id, inquiryStore, inquiriesStore)
  if (inquiry.currentUserStatus.hasSupported) {
    showSuccess(t('agora', 'Inquiry supported, thanks for her !'), { timeout: 2000 })
  } else {
    showSuccess(t('agora', 'Inquiry support removed !'), { timeout: 2000 })
  }
}

// Get inquiry type info from app settings
const inquiryTypeInfo = computed(() => {
  return sessionStore.appSettings.inquiryTypeTab?.find(t => t.inquiry_type === inquiry.type)
})

// Get inquiry type icon
const inquiryTypeIcon = computed(() => {
  const iconName = inquiryTypeInfo.value?.icon?.toLowerCase() || 'activity'
  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
})

// Get inquiry type label
const inquiryTypeLabel = computed(() => {
  return inquiryTypeInfo.value?.label || inquiry.type
})

function htmlToFirstLine(html) {
  if (!html) return ''
  const tempDiv = document.createElement('div')
  tempDiv.innerHTML = html
  let text = tempDiv.textContent || tempDiv.innerText || ''
  text = text.replace(/\s+/g, ' ').trim()
  return text.split(/\r?\n/)[0] || ''
}

const closeToClosing = computed(
  () =>
    !inquiry.status.isExpired &&
    inquiry.configuration.expire &&
    DateTime.fromMillis(inquiry.configuration.expire * 1000).diffNow('hours').hours < 36
)

const timeExpirationRelative = computed(() => {
  if (inquiry.configuration.expire) {
    return DateTime.fromMillis(inquiry.configuration.expire * 1000).toRelative()
  }
  return t('agora', 'never')
})

const expiryClass = computed(() => {
  if (inquiry.status.isExpired) {
    return 'error'
  }
  if (inquiry.configuration.expire && closeToClosing.value) {
    return 'warning'
  }
  if (inquiry.configuration.expire && !inquiry.status.isExpired) {
    return 'success'
  }
  return 'success'
})

const timeCreatedRelative = computed(
  () => DateTime.fromMillis(inquiry.status.created * 1000).toRelative() as string
)

const safeDescription = computed(() => {
  if (preferencesStore.user.verboseInquiriesList) {
    if (inquiry.description) {
      return htmlToFirstLine(inquiry.description)
    }
    return t('agora', 'No description provided')
  }

  if (inquiry.status.isArchived) {
    return t('agora', 'Archived {relativeTime}', {
      relativeTime: DateTime.fromMillis(inquiry.status.archivedDate * 1000).toRelative() as string,
    })
  }

  return t('agora', 'Started {relativeTime} from {ownerName}', {
    ownerName: inquiry.owner.displayName,
    relativeTime: timeCreatedRelative.value,
  })
})

const formatDate = (timestamp: number) =>
  DateTime.fromMillis(timestamp * 1000).toLocaleString(DateTime.DATE_SHORT)

const moderationStatus = computed(
  () => inquiry.moderationStatus || inquiryStore.getInquiryModerationStatus?.(inquiry.id)
)

const moderationStatusIcon = computed(() => {
  const statusItem = sessionStore.appSettings.moderationStatusTab?.find(
    (item) => item.inquiryType === inquiry.type && item.statusKey === inquiry.moderationStatus
  )

  if (!statusItem) {
    return StatusIcons.Draft
  }

  return StatusIcons[statusItem.icon] || StatusIcons.Draft
})

const moderationStatusLabel = computed(() => {
  const statusItem = sessionStore.appSettings.moderationStatusTab?.find(
    (item) => item.inquiryType === inquiry.type && item.statusKey === inquiry.moderationStatus
  )

  if (!statusItem) {
    return 'Draft'
  }

  return statusItem.label || 'Draft'
})

const moderationStatusInfo = computed(() => {
  if (!moderationStatus.value || !sessionStore.appSettings?.moderationStatusTab) {
    return null
  }

  return sessionStore.appSettings.moderationStatusTab.find(
    (status) => status.status_key === moderationStatus.value
  )
})
</script>

<template>
  <div class="inquiry-item" :class="{ 'grid-view': gridView, 'list-view': !gridView }">
    <!-- Grid View (Professional Cards) -->
    <template v-if="gridView">
      <div class="inquiry-card">
        <!-- Card Header -->
        <div class="card-header">
          <div class="type-badge">
            <component :is="inquiryTypeIcon" :size="16" />
            <span class="type-label">{{ inquiryTypeLabel }}</span>
          </div>
          
          <div class="header-actions">
            <NcAvatar
              :user="inquiry.owner.id"
              :size="24"
              class="owner-avatar"
              :show-name="false"
            />
          </div>
        </div>

        <!-- Card Content -->
        <RouterLink
          v-if="!noLink"
          class="card-content"
          :to="{ name: 'inquiry', params: { id: inquiry.id } }"
        >
          <h3 class="card-title" :title="inquiry.title">
            {{ inquiry.title }}
          </h3>
          <p class="card-description" v-if="safeDescription">
            {{ safeDescription }}
          </p>
        </RouterLink>
        
        <div v-else class="card-content">
          <h3 class="card-title">
            {{ inquiry.title }}
          </h3>
          <p class="card-description">
            {{
              t('agora', 'No access to this inquiry of {ownerName}.', {
                ownerName: inquiry.owner.displayName,
              })
            }}
          </p>
        </div>

        <!-- Card Metadata -->
        <div class="card-metadata">
          <!-- Moderation Status -->
          <div 
            v-if="moderationStatusInfo && inquiry.type !== 'official'" 
            class="metadata-item status-badge"
            :title="moderationStatusInfo.description || moderationStatusInfo.label"
          >
            <component :is="moderationStatusIcon" :size="12" />
            <span>{{ moderationStatusLabel }}</span>
          </div>

          <!-- Parent Link -->
          <div v-if="inquiry.parentId !== 0" class="metadata-item">
            <RouterLink :to="`/inquiry/${inquiry.parentId}`" class="parent-link">
              <component :is="StatusIcons.LinkIcon" :size="14" />
              <span>{{ t('agora', 'Parent') }}</span>
            </RouterLink>
          </div>

          <!-- Participants -->
          <div 
            v-if="inquiry.type !== 'official' && inquiry.status.countParticipants > 0"
            class="metadata-item"
            :title="t('agora', '{count} participants', { count: inquiry.status.countParticipants })"
          >
            <component :is="BadgeIcons.participated" :size="14" />
            <span>{{ inquiry.status.countParticipants }}</span>
          </div>

          <!-- Comments -->
          <div 
            v-if="canComment(context)"
            class="metadata-item"
            :title="t('agora', '{count} comments', { count: inquiry.status.countComments || 0 })"
          >
            <component :is="StatusIcons.ForumOutline" :size="14" />
            <span>{{ inquiry.status.countComments || 0 }}</span>
          </div>

          <!-- Supports -->
          <div 
            v-if="canSupport(context)"
            class="metadata-item support-item"
            :title="t('agora', '{count} supports', { count: inquiry.status.countSupports || 0 })"
            @click="onToggleSupport"
          >
            <ThumbIcon :supported="inquiry.currentUserStatus.hasSupported" :size="18" />
            <span>{{ inquiry.status.countSupports || 0 }}</span>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer">
          <div class="footer-left">
            <!-- Access Badges -->
            <div 
              v-if="!inquiry.status.isArchived && inquiry.configuration.access === 'private'"
              class="access-badge private"
              :title="t('agora', 'Private inquiry, only invited participants have access')"
            >
              <component :is="StatusIcons.Lock" :size="12" />
            </div>
            
            <div 
              v-else-if="!inquiry.status.isArchived && inquiry.configuration.access === 'open'"
              class="access-badge open"
              :title="t('agora', 'Open inquiry, accessible to all users')"
            >
              <component :is="StatusIcons.LockOpen" :size="12" />
            </div>
            
            <div 
              v-if="inquiry.status.isArchived"
              class="access-badge archived"
              :title="t('agora', 'Archived inquiry')"
            >
              <component :is="BadgeIcons.archived" :size="12" />
            </div>

            <!-- Expiration -->
            <div 
              v-if="inquiry.configuration.expire"
              class="expiry-badge"
              :class="expiryClass"
              :title="t('agora', 'Expires {time}', { time: timeExpirationRelative })"
            >
              <component 
                :is="inquiry.status.isExpired ? BadgeIcons.closed : BadgeIcons.expiration" 
                :size="12" 
              />
              <span>{{ timeExpirationRelative }}</span>
            </div>
          </div>

          <div class="footer-right">
            <slot name="actions" />
          </div>
        </div>
      </div>
    </template>

    <!-- List View -->
    <template v-else>
      <!-- ... (keep existing list view code, but you can simplify it) ... -->
      <div class="list-item">
        <div class="item-type">
          <component :is="inquiryTypeIcon" :size="20" :title="inquiryTypeLabel" />
        </div>

        <RouterLink
          v-if="!noLink"
          class="item-content"
          :to="{ name: 'inquiry', params: { id: inquiry.id } }"
        >
          <div class="item-title">
            {{ inquiry.title }}
          </div>
          <div class="item-description">
            {{ safeDescription }}
          </div>
        </RouterLink>

        <div v-else class="item-content">
          <div class="item-title">
            {{ inquiry.title }}
          </div>
          <div class="item-description">
            {{
              t('agora', 'No access to this inquiry of {ownerName}.', {
                ownerName: inquiry.owner.displayName,
              })
            }}
          </div>
        </div>

        <div class="item-actions">
          <slot name="actions" />
        </div>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.inquiry-item {
  &.grid-view {
    .inquiry-card {
      background: var(--color-main-background);
      border: 1px solid var(--color-border);
      border-radius: 12px;
      padding: 16px;
      height: 100%;
      display: flex;
      flex-direction: column;
      transition: all 0.2s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--color-primary-element);
      }
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;

      .type-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        background: var(--color-background-dark);
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        color: var(--color-text-lighter);

        .type-label {
          text-transform: capitalize;
        }
      }

      .header-actions {
        .owner-avatar {
          border: 2px solid var(--color-border);
        }
      }
    }

    .card-content {
      flex: 1;
      margin-bottom: 16px;
      text-decoration: none;
      color: inherit;

      .card-title {
        font-size: 16px;
        font-weight: 600;
        line-height: 1.4;
        margin: 0 0 8px 0;
        color: var(--color-main-text);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      .card-description {
        font-size: 13px;
        line-height: 1.5;
        color: var(--color-text-lighter);
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
    }

    .card-metadata {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--color-border-light);

      .metadata-item {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        background: var(--color-background-dark);
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        color: var(--color-text-lighter);
        transition: all 0.2s ease;

        &.status-badge {
          background: var(--color-warning-light);
          color: var(--color-warning-text);
        }

        &.support-item {
          cursor: pointer;
          
          &:hover {
            background: var(--color-primary-light);
            color: var(--color-primary-text);
          }
        }

        .parent-link {
          display: flex;
          align-items: center;
          gap: 4px;
          text-decoration: none;
          color: inherit;
        }
      }
    }

    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;

      .footer-left {
        display: flex;
        align-items: center;
        gap: 8px;

        .access-badge,
        .expiry-badge {
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 3px 6px;
          border-radius: 4px;
          font-size: 10px;
          font-weight: 500;

          &.private {
            background: var(--color-error-light);
            color: var(--color-error);
          }

          &.open {
            background: var(--color-success-light);
            color: var(--color-success);
          }

          &.archived {
            background: var(--color-background-darker);
            color: var(--color-text-lighter);
          }

          &.error {
            background: var(--color-error-light);
            color: var(--color-error);
          }

          &.warning {
            background: var(--color-warning-light);
            color: var(--color-warning-text);
          }

          &.success {
            background: var(--color-success-light);
            color: var(--color-success);
          }
        }
      }

      .footer-right {
        display: flex;
        align-items: center;
      }
    }
  }

  &.list-view {
    .list-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 8px;
      border-bottom: 1px solid var(--color-border);
      transition: background-color 0.2s ease;

      &:hover {
        background-color: var(--color-background-hover);
      }

      .item-type {
        flex-shrink: 0;
      }

      .item-content {
        flex: 1;
        min-width: 0;
        text-decoration: none;
        color: inherit;

        .item-title {
          font-weight: 600;
          color: var(--color-main-text);
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .item-description {
          font-size: 13px;
          color: var(--color-text-lighter);
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }

      .item-actions {
        flex-shrink: 0;
      }
    }
  }
}

// Responsive Design
@media (max-width: 768px) {
  .inquiry-item.grid-view {
    .inquiry-card {
      padding: 12px;
    }

    .card-header {
      margin-bottom: 10px;
    }

    .card-metadata {
      gap: 6px;
      margin-bottom: 12px;
      padding-bottom: 12px;
    }

    .card-footer {
      .footer-left {
        gap: 6px;
      }
    }
  }
}

@media (max-width: 480px) {
  .inquiry-item.grid-view {
    .card-metadata {
      .metadata-item {
        font-size: 10px;
        padding: 3px 6px;
      }
    }

    .card-footer {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;

      .footer-left {
        order: 2;
      }

      .footer-right {
        order: 1;
        width: 100%;
        justify-content: flex-end;
      }
    }
  }
}
</style>
