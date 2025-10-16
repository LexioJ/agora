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
import {
  getInquiryTypeData,
} from '../../helpers/modules/InquiryHelper.ts'

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

const ownedGroup = computed(() => inquiry.ownedGroup || inquiry.owned_group)

const context = computed(() =>
  createPermissionContextForContent(
    ContentType.Inquiry,
    inquiry.owner.id,
    inquiry.status.inquiryStatus,
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
    showSuccess(t('agora', 'Inquiry supported, thanks for your support!'), { timeout: 2000 })
  } else {
    showSuccess(t('agora', 'Inquiry support removed!'), { timeout: 2000 })
  }
}

//Get inquiry type data
const inquiryTypeData = computed(() =>
  getInquiryTypeData(inquiry.type, sessionStore.appSettings.inquiryTypeTab || [])
)

function htmlToFirstLine(html: string): string {
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

//Inquiry Status

const inquiryStatus = computed(
  () => inquiry.status.inquiryStatus || inquiryStore.status.inquiryStatus?.(inquiry.id)
)

const inquiryStatusIcon = computed(() => {
  const statusItem = sessionStore.appSettings.inquiryStatusTab?.find(
    (item) => item.inquiryType === inquiry.type && item.statusKey === inquiry.status.inquiryStatus
  )

  if (!statusItem) {
    return StatusIcons.Draft
  }

  return StatusIcons[statusItem.icon] || StatusIcons.Draft
})

const inquiryStatusLabel = computed(() => {
  const statusItem = sessionStore.appSettings.inquiryStatusTab?.find(
    (item) => item.inquiryType === inquiry.type && item.statusKey === inquiry.status.inquiryStatus
  )

  if (!statusItem) {
    return 'Draft'
  }

  return statusItem.label || 'Draft'
})

const inquiryStatusInfo = computed(() => {
  if (!inquiryStatus.value || !sessionStore.appSettings?.inquiryStatusTab) {
    return null
  }

  return sessionStore.appSettings.inquiryStatusTab.find(
    (status) => status.statusKey === inquiryStatus.value
  )
})
</script>

<template>
  <div class="inquiry-item" :class="{ 'grid-view': gridView, 'list-view': !gridView }">
    <!-- Grid View (Professional Cards) -->
    <template v-if="gridView">
      <div class="inquiry-card">
        <div class="card-header">
          <div class="header-left">
            <NcAvatar
              v-if="ownedGroup"
              :display-name="ownedGroup"
              :size="32"
              class="group-avatar"
              :show-name="false"
            />
            <NcAvatar
              v-else
              :user="inquiry.owner.id"
              :size="32"
              class="owner-avatar"
              :show-name="false"
            />
            
            <div class="header-info">
              <div class="type-badge">
                <component :is="inquiryTypeData.icon" :size="16" />
                <span class="type-label">{{ inquiryTypeData.label }}</span>
              </div>
              
              <div 
                v-if="inquiryStatusInfo && inquiry.type !== 'official'" 
                class="inquiry-status"
                :title="inquiryStatusInfo.description || inquiryStatusInfo.label"
              >
                <component :is="inquiryStatusIcon" :size="16" />
                <span class="status-label">{{ inquiryStatusLabel }}</span>
              </div>
            </div>
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
          
          <div v-if="inquiry.cover_id" class="card-image-container">
            <img :src="`/apps/agora/covers/${inquiry.cover_id}`" :alt="inquiry.title" class="card-image" />
          </div>
          <p v-else class="card-description" :title="safeDescription">
            {{ safeDescription }}
          </p>
        </RouterLink>
        
        <div v-else class="card-content">
          <h3 class="card-title">
            {{ inquiry.title }}
          </h3>
          <div v-if="inquiry.cover_id" class="card-image-container">
            <img :src="`/apps/agora/covers/${inquiry.cover_id}`" :alt="inquiry.title" class="card-image" />
          </div>
          <p v-else class="card-description">
            {{
              t('agora', 'No access to this inquiry of {ownerName}.', {
                ownerName: inquiry.owner.displayName,
              })
            }}
          </p>
        </div>

        <div class="metadata-row">
          <!-- Dates -->
          <div class="metadata-group">
            <div class="metadata-item" :title="t('agora', 'Created on {date}', { date: formatDate(inquiry.status.created) })">
              <component :is="StatusIcons.Calendar" :size="14" />
              <span class="metadata-label">{{ formatDate(inquiry.status.created) }}</span>
            </div>
            
            <div v-if="inquiry.status.lastInteraction" class="metadata-item" :title="t('agora', 'Last updated on {date}', { date: formatDate(inquiry.status.lastInteraction) })">
              <component :is="StatusIcons.Updated" :size="14" />
              <span class="metadata-label">{{ formatDate(inquiry.status.lastInteraction) }}</span>
            </div>
          </div>

          <div class="metadata-group">
            <!-- Supports -->
            <div 
              v-if="canSupport(context)"
              class="metadata-item support-item"
              :title="t('agora', '{count} supports', { count: inquiry.status.countSupports || 0 })"
              @click="onToggleSupport"
            >
              <ThumbIcon :supported="inquiry.currentUserStatus.hasSupported" :size="16" />
              <span class="metadata-label">{{ inquiry.status.countSupports || 0 }}</span>
            </div>

            <!-- Comments -->
            <div 
              v-if="canComment(context)"
              class="metadata-item"
              :title="t('agora', '{count} comments', { count: inquiry.status.countComments || 0 })"
            >
              <component :is="StatusIcons.ForumOutline" :size="14" />
              <span class="metadata-label">{{ inquiry.status.countComments || 0 }}</span>
            </div>

            <div 
              v-if="inquiry.type !== 'official' && inquiry.status.countParticipants > 0"
              class="metadata-item"
              :title="t('agora', '{count} participants', { count: inquiry.status.countParticipants })"
            >
              <component :is="BadgeIcons.participated" :size="14" />
              <span class="metadata-label">{{ inquiry.status.countParticipants }}</span>
            </div>
          </div>

          <div class="metadata-group">
            <!-- Access Status -->
            <div 
              v-if="!inquiry.status.isArchived && inquiry.status.inquiryStatus"
            >
              <component :is="StatusIcons.Lock" :size="12" />
            </div>
            
            <div 
              v-else-if="!inquiry.status.isArchived && inquiry.status.acess === 'open'"
              class="status-badge open"
              :title="t('agora', 'Open inquiry')"
            >
              <component :is="StatusIcons.LockOpen" :size="12" />
            </div>

            <!-- Archived -->
            <div 
              v-if="inquiry.status.isArchived"
              class="status-badge archived"
              :title="t('agora', 'Archived inquiry')"
            >
              <component :is="BadgeIcons.archived" :size="12" />
            </div>

            <!-- Expiration -->
            <div 
              v-if="inquiry.configuration.expire"
              class="status-badge expiry"
              :class="expiryClass"
              :title="t('agora', 'Expires {time}', { time: timeExpirationRelative })"
            >
              <component 
                :is="inquiry.status.isExpired ? BadgeIcons.closed : BadgeIcons.expiration" 
                :size="12" 
              />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <slot name="actions" />
        </div>
      </div>
    </template>

    <!-- List View -->
<!-- List View -->
<template v-else>
  <div class="list-item">
    <div class="item-avatar">
      <NcAvatar
        v-if="ownedGroup"
        :display-name="ownedGroup"
        :size="32"
        class="group-avatar"
        :show-name="false"
      />
      <NcAvatar
        v-else
        :user="inquiry.owner.id"
        :size="32"
        class="owner-avatar"
        :show-name="false"
      />
    </div>

    <div class="item-content">
      <RouterLink
        v-if="!noLink"
        class="content-link"
        :to="{ name: 'inquiry', params: { id: inquiry.id } }"
      >
        <div class="item-title">
          {{ inquiry.title }}
        </div>
        <div class="item-description">
          {{ safeDescription }}
        </div>
      </RouterLink>

      <div v-else class="content-link">
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

      <div class="list-metadata">
        <!-- Dates -->
        <div class="metadata-item">
          <component :is="StatusIcons.Calendar" :size="12" />
          <span>{{ formatDate(inquiry.status.created) }}</span>
        </div>
        
        <div v-if="inquiry.status.lastInteraction" class="metadata-item">
          <component :is="StatusIcons.Updated" :size="12" />
          <span>{{ formatDate(inquiry.status.lastInteraction) }}</span>
        </div>

        <!-- Inquiry Status -->
        <div 
          v-if="inquiryStatusInfo && inquiry.type !== 'official'" 
          class="metadata-item inquiry-status"
        >
          <component :is="inquiryStatusIcon" :size="12" />
          <span>{{ inquiryStatusLabel }}</span>
        </div>

        <!-- Moderation Status -->
        <div 
          v-if="inquiryStatusInfo && inquiry.type !== 'official'" 
          class="metadata-item inquiry-status"
        >
          <component :is="inquiryStatusIcon" :size="12" />
          <span>{{ inquiryStatusLabel }}</span>
        </div>

        <!-- Stats -->
        <div v-if="canSupport(context)" class="metadata-item">
          <ThumbIcon :supported="inquiry.currentUserStatus.hasSupported" :size="16" />
          <span>{{ inquiry.status.countSupports || 0 }}</span>
        </div>

        <div v-if="canComment(context)" class="metadata-item">
          <component :is="StatusIcons.ForumOutline" :size="16" />
          <span>{{ inquiry.status.countComments || 0 }}</span>
        </div>
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
      padding: 20px;
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
      margin-bottom: 16px;

      .header-left {
        display: flex;
        align-items: flex-start;
        gap: 12px;

        .owner-avatar,
        .group-avatar {
          border: 2px solid var(--color-border);
          flex-shrink: 0;
        }

        .header-info {
          display: flex;
          flex-direction: column;
          gap: 8px;
          flex: 1;

          .type-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: var(--color-background-dark);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-lighter);
            align-self: flex-start;

            .type-label {
              text-transform: capitalize;
            }
          }

          .inquiry-status {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: var(--color-warning-light);
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            color: var(--color-warning-text);
            align-self: flex-start;

            .status-label {
              text-transform: capitalize;
            }
          }
        }
      }
    }

    .card-content {
      flex: 1;
      margin-bottom: 16px;
      text-decoration: none;
      color: inherit;

      .card-title {
        font-size: 18px;
        font-weight: 600;
        line-height: 1.4;
        margin: 0 0 12px 0;
        color: var(--color-main-text);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      .card-image-container {
        margin: 12px 0;
        border-radius: 8px;
        overflow: hidden;
        max-height: 160px;
        background: var(--color-background-dark);

        .card-image {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
      }

      .card-description {
        font-size: 14px;
        line-height: 1.5;
        color: var(--color-text-lighter);
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
    }

    .metadata-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 16px;
      padding: 12px 0;
      border-top: 1px solid var(--color-border-light);

      .metadata-group {
        display: flex;
        align-items: center;
        gap: 12px;

        .metadata-item {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 12px;
          color: var(--color-text-lighter);
          transition: all 0.2s ease;

          &.support-item {
            cursor: pointer;
            
            &:hover {
              color: var(--color-primary-element);
            }
          }

          .metadata-label {
            font-weight: 500;
          }
        }
      }

      .status-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        transition: all 0.2s ease;

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

        &.expiry {
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
    }

    .card-actions {
      display: flex;
      justify-content: flex-end;
    }
  }

  &.list-view {
    .list-item {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 16px;
      border-radius: 8px;
      border-bottom: 1px solid var(--color-border);
      transition: background-color 0.2s ease;

      &:hover {
        background-color: var(--color-background-hover);
      }

      .item-avatar {
        flex-shrink: 0;
        
        .owner-avatar,
        .group-avatar {
          border: 2px solid var(--color-border);
        }
      }

      .item-content {
        flex: 1;
        min-width: 0;

        .content-link {
          text-decoration: none;
          color: inherit;
          display: block;
          margin-bottom: 12px;
        }

        .item-title {
          font-weight: 600;
          font-size: 16px;
          color: var(--color-main-text);
          margin-bottom: 6px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .item-description {
          font-size: 14px;
          color: var(--color-text-lighter);
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .list-metadata {
          display: flex;
          align-items: center;
          gap: 16px;
          flex-wrap: wrap;

          .metadata-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--color-text-lighter);

            &.inquiry-status {
              padding: 2px 6px;
              background: var(--color-warning-light);
              border-radius: 4px;
              color: var(--color-warning-text);
            }
          }
        }
      }

      .item-actions {
        flex-shrink: 0;
        display: flex;
        align-items: center;
      }
    }
  }
}

// Responsive Design
@media (max-width: 768px) {
  .inquiry-item.grid-view {
    .inquiry-card {
      padding: 16px;
    }

    .metadata-row {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;

      .metadata-group {
        gap: 8px;
      }
    }
  }

  .inquiry-item.list-view {
    .list-item {
      .list-metadata {
        gap: 12px;
      }
    }
  }
}

@media (max-width: 480px) {
  .inquiry-item.grid-view {
    .card-header {
      .header-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
    }
  }

  .inquiry-item.list-view {
    .list-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;

      .item-avatar {
        align-self: flex-start;
      }

      .list-metadata {
        gap: 8px;
      }
    }
  }
}
</style>
