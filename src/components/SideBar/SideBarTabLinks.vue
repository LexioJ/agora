<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { 
  NcButton, 
  NcListItem,
  NcActionButton,
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { InquiryLink, useInquiryLinksStore } from '../../stores/inquiryLinks'
import { useInquiryStore } from '../../stores/inquiry'
import { Attachment, useAttachmentsStore } from '../../stores/attachments'
import { useSessionStore } from '../../stores/session'
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import {
  canEdit,
  createPermissionContextForContent,
  ContentType,
  AccessLevel,
} from '../../utils/permissions.ts'
import {
  isInquiryFinalStatus
} from '../../helpers/modules/InquiryHelper.ts'

// Components
import AddResourceModal from '../Modals/AddResourceModal.vue'

// Props
const props = defineProps<{
  availableApps?: string[]
}>()

// Stores
const inquiryLinksStore = useInquiryLinksStore()
const inquiryStore = useInquiryStore()
const sessionStore = useSessionStore()
const attachmentsStore = useAttachmentsStore()

// State
const isLoading = ref(false)
const showAddModal = ref(false)

interface ResourceItem {
  id: number | string
  target_app: string
  target_type: string
  target_id: string
  type: 'link' | 'attachment'
  displayName: string
  icon: string
  attachment?: Attachment
  sort_order: number
}

interface GroupedResources {
  [key: string]: ResourceItem[]
}


// Context for permissions
const context = computed(() => {
  const ctx = createPermissionContextForContent(
    ContentType.Inquiry,
    inquiryStore.owner.id,
    inquiryStore.configuration.access === 'public',
    inquiryStore.status.isLocked,
    inquiryStore.status.isExpired,
    inquiryStore.status.deletionDate > 0,
    inquiryStore.status.isArchived,
    inquiryStore.inquiryGroups.length > 0,
    inquiryStore.inquiryGroups,
    inquiryStore.type,
    inquiryStore.family, 
    inquiryStore.configuration.access as AccessLevel,
    isInquiryFinalStatus(inquiryStore,sessionStore.appSettings),
    inquiryStore.status.moderationStatus 
  )
  return ctx
})


// Computed - Combine links and attachments for display
const groupedResources = computed((): GroupedResources => {
  const groups: Record<string, GroupedResources> = {}
  
  // Add linked resources
  const links = inquiryLinksStore.getByInquiryId(inquiryStore.id)
    .sort((a, b) => a.sort_order - b.sort_order)
  
  links.forEach((link:InquiryLink) => {
    if (!groups[link.target_app]) {
      groups[link.target_app] = []
    }
    groups[link.target_app].push({
      ...link,
      type: 'link',
      displayName: getResourceDisplayName(link),
      icon: getAppIcon(link.target_app)
    })
  })
  
  // Add file attachments (excluding cover image)
 // Add this filter to exclude the cover image
const fileAttachments = attachmentsStore.attachments
  .filter(att => att.fileId !== inquiryStore.coverId && att.id !== inquiryStore.coverId)

  if (fileAttachments.length > 0) {
    groups.files = fileAttachments.map(attachment => ({
      id: attachment.id,
      target_app: 'files',
      target_type: 'file',
      target_id: attachment.id?.toString() || '',
      type: 'attachment',
      displayName: attachment.name,
      icon: InquiryGeneralIcons.Document,
      attachment,
      sort_order: 0
    }))
  }
  
  return groups
})

const hasResources = computed(() => Object.keys(groupedResources.value).length > 0)

const openAddModal = () => {
  showAddModal.value = true
}

const getResourceDisplayName = (link: InquiryLink): string => {
  const typeNames: Record<string, string> = {
    poll: t('agora', 'Poll'),
    form: t('agora', 'Form'),
    card: t('agora', 'Card'),
    expense: t('agora', 'Expense'),
    file: t('agora', 'File'),
    page: t('agora', 'Collective Page'),
  }
  
  const typeName = typeNames[link.target_type] || link.target_type
  const resourceId = link.target_id
  
  // For files, show the filename
  if (link.target_app === 'files') {
    const attachment = attachmentsStore.attachments.find(att => 
      att.id?.toString() === resourceId
    )
    if (attachment) {
      return attachment.name
    }
  }
  
  return `${typeName} #${resourceId}`
}

const getAppName = (appId: string): string => {
  const appNames: Record<string, string> = {
    polls: t('agora', 'Polls'),
    forms: t('agora', 'Forms'),
    deck: t('agora', 'Deck'),
    cospend: t('agora', 'Cospend'),
    files: t('agora', 'Files'),
    collectives: t('agora', 'Collectives'),
  }
  return appNames[appId] || appId
}

const getAppIcon = (appId: string) => {
  const icons: Record<string> = {
    polls: InquiryGeneralIcons.Poll,
    forms: InquiryGeneralIcons.Form,
    deck: InquiryGeneralIcons.Deck,
    cospend: InquiryGeneralIcons.Money,
    files: InquiryGeneralIcons.Document,
    collectives: InquiryGeneralIcons.Collectives,
  }
  return icons[appId] || InquiryGeneralIcons.Link
}

const deleteResource = async (resource: ResourceItem) => {
  try {
    if (resource.type === 'link') {
      await inquiryLinksStore.delete(resource.id)
      showSuccess(t('agora', 'Link deleted successfully'))
    } else if (resource.type === 'attachment') {
      const attachment = attachmentsStore.attachments.find(att => att.id === resource.id)
      if (!attachment) {
        console.warn('No attachment found with id:', resource.id)
        return
      }

      if (attachment.id && attachment.id > 0) {
        await attachmentsStore.delete(attachment.id)
      }

      // Update attachments store reactively
      attachmentsStore.attachments = attachmentsStore.attachments.filter(att => att.id !== resource.id)
      showSuccess(t('agora', 'File has been removed!'))
    }
    
  } catch (error) {
    console.error('Error deleting resource:', error)
    const errorMsg = resource.type === 'link' 
      ? t('agora', 'Failed to delete link')
      : t('agora', 'Failed to remove file')
    showError(errorMsg)
  }
}

const getResourceSubtitle = (resource: ResourceItem): string => {
  // For attachments, show file size - FIX: Check both resource.attachment and resource directly
  if (resource.type === 'attachment') {
    const size = resource.attachment?.size ?? resource.size
    if (size !== undefined && size !== null) {
      return `${formatFileSize(size)}`
    }
  }
  // For links, show app name
  return getAppName(resource.target_app)
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const unitIndex = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, unitIndex)).toFixed(2))} ${sizes[unitIndex]}`
}

// Get the target for the link
const getResourceTarget = (resource: ResourceItem): string => 
   '_blank' // Always open in new tab


// Add this function to check if resource can be edited
const canEditResource = (resource: ResourceItem): boolean => {
  // Only owners can delete resources
  if (!inquiryStore.currentUserStatus?.isOwner) {
    return false
  }

  // Use your permission system if available, otherwise simple owner check
  try {
    return canEdit?.(context.value) ?? inquiryStore.currentUserStatus?.isOwner
  } catch (error) {
    console.error('Error checking edit permissions:', error)
    return inquiryStore.currentUserStatus?.isOwner
  }
}

// Get the href for the link
const getResourceHref = (resource: ResourceItem): string => {
  if (resource.type === 'link') {
    const baseUrls: Record<string, string> = {
      polls: '/apps/polls/poll/',
      forms: '/apps/forms/s/',
      deck: '/apps/deck/#/board/',
      cospend: '/apps/cospend/',
      files: '/apps/files/?fileid=',
      collectives: '/apps/collectives/#/collective/',
    }
    const baseUrl = baseUrls[resource.target_app] || '/'
    return `${baseUrl}${resource.target_id}`
  } if (resource.type === 'attachment' && resource.attachment?.url) {
    return resource.attachment.url
  }
  return '#'
}

// Handle click with proper prevention
const handleResourceClick = (resource: ResourceItem, event: Event) => {
  // Let the native href/target handle the navigation
}

</script>

<template>
  <div class="sidebar-links">
    <div class="sidebar-header">
      <div class="header-content">
        <div class="header-text">
          <h2>{{ t('agora', 'Linked Resources') }}</h2>
          <p class="description">{{ t('agora', 'Manage links to other Nextcloud resources and files') }}</p>
        </div>
        <NcButton
          v-if="inquiryStore.currentUserStatus?.isOwner"
          type="primary"
          class="add-resource-btn"
          @click="openAddModal"
        >
          <template #icon>
            <component :is="InquiryGeneralIcons.Plus" :size="20" />
          </template>
          {{ t('agora', 'Add Resource') }}
        </NcButton>
      </div>
    </div>

    <div class="resources-list">
      <div v-if="isLoading" class="loading-state">
        {{ t('agora', 'Loading resources...') }}
      </div>

      <div v-else-if="!hasResources" class="empty-state">
        <component :is="InquiryGeneralIcons.Link" :size="48" class="empty-icon" />
        <p>{{ t('agora', 'No linked resources') }}</p>
        <p class="empty-description">{{ t('agora', 'Add resources or files to connect with other apps') }}</p>
      </div>

      <div v-else class="resources-groups">
          <div v-for="(resources, appId) in groupedResources" :key="appId" class="resource-group">
              <h3 class="group-title">
                  <component :is="getAppIcon(appId)" :size="20" class="group-icon" />
                  {{ getAppName(appId) }}
              </h3>
              <div class="group-items">
                  <NcListItem
                          v-for="resource in resources"
                          :key="resource.id || resource.target_id"
                          :name="resource.displayName"
                          :bold="true"
                          :force-display-actions="true"
                          :target="getResourceTarget(resource)"
                          :href="getResourceHref(resource)"
                          :to="null"
                  @click="handleResourceClick(resource, $event)"
                  >
                  <template #icon>
                      <component :is="resource.icon" :size="20" />
                  </template>

                  <template #indicator>
                      {{ getResourceSubtitle(resource) }}
                  </template>

                  <template #actions >
                      <NcActionButton
                              v-if="canEditResource(resource)"
                              @click.stop="deleteResource(resource)"
                              >
                              <template #icon>
                                  <component :is="InquiryGeneralIcons.Delete" :size="16" />
                              </template>
                      {{ t('agora', 'Delete') }}
                      </NcActionButton>
                  </template>
                  </NcListItem>
              </div>
          </div>
      </div>
    </div>

    <AddResourceModal
            v-model:open="showAddModal"
            :inquiry-store="inquiryStore"
            :available-apps="props.availableApps"
            />
  </div>
</template>

<style scoped lang="scss">
.sidebar-links {
    padding: var(--default-grid-baseline);
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: var(--default-grid-baseline);

    .sidebar-header {
        margin-bottom: var(--default-grid-baseline);
        border-bottom: 1px solid var(--color-border);
        padding-bottom: var(--default-grid-baseline);

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--default-grid-baseline);

            .header-text {
                flex-grow: 1;

                h2 {
                    margin: 0;
                    font-size: 1.2em;
                    font-weight: 600;
                    color: var(--color-text);
                }

                .description {
                    margin: calc(var(--default-grid-baseline) / 2) 0 0 0;
                    font-size: 0.9em;
                    color: var(--color-text-lighter);
                }
            }

            .add-resource-btn {
                flex-shrink: 0;
            }
        }
    }

    .resources-list {
        flex-grow: 1;
        overflow-y: auto;

        .loading-state,
        .empty-state {
            text-align: center;
            color: var(--color-text-lighter);
            padding: calc(var(--default-grid-baseline) * 3);
            font-style: italic;

            .empty-icon {
                margin-bottom: var(--default-grid-baseline);
                color: var(--color-border);
            }

            .empty-description {
                font-size: 0.9em;
                margin-top: calc(var(--default-grid-baseline) / 2);
                margin-bottom: var(--default-grid-baseline);
            }
        }

        .resources-groups {
            display: flex;
            flex-direction: column;
            gap: var(--default-grid-baseline);
        }

        .resource-group {
            .group-title {
                display: flex;
                align-items: center;
                gap: calc(var(--default-grid-baseline) / 2);
                margin: 0 0 calc(var(--default-grid-baseline) / 2) 0;
                font-size: 1em;
                font-weight: 600;
                color: var(--color-text);

                .group-icon {
                    color: var(--color-primary-element);
                }
            }

            .group-items {
                display: flex;
                flex-direction: column;
                gap: 1px;
                background-color: var(--color-background-dark);
                border-radius: var(--border-radius);
                overflow: hidden;
            }
        }
    }
}
</style>
