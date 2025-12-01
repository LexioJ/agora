<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted, toRaw } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { useInquiryStore } from '../../stores/inquiry'
import { useInquiriesStore } from '../../stores/inquiries'
import { useSupportsStore } from '../../stores/supports'
import { useCommentsStore } from '../../stores/comments'
import { useSessionStore } from '../../stores/session'
import { useAttachmentsStore } from '../../stores/attachments'
import { BaseEntry, Event } from '../../Types/index.ts'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'
import {
  getInquiryTypeData,
  isInquiryFinalStatus
} from '../../helpers/modules/InquiryHelper.ts'

import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { NcTextArea, NcRichText } from '@nextcloud/vue'

import { TernarySupportIcon, ThumbIcon } from '../AppIcons'
import InquiryEditor from '../Editor/InquiryEditor.vue'
import { InquiryGeneralIcons, StatusIcons } from '../../utils/icons.ts'
import {
  canSupport,
  canComment,
  createPermissionContextForContent,
  ContentType,
  AccessLevel,
} from '../../utils/permissions.ts'


// Props
const props = defineProps<{
  isReadonly: boolean
  isReadonlyDescription?: boolean
}>()


// Store declarations
const sessionStore = useSessionStore()
const commentsStore = useCommentsStore()
const supportsStore = useSupportsStore()
const inquiryStore = useInquiryStore()
const inquiriesStore = useInquiriesStore()
const attachmentsStore = useAttachmentsStore()
const imageFileInput = ref(null)
const currentCoverUrl = ref('')

const triggerImageUpload = () => {
  imageFileInput.value?.click()
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

// Form fields
const selectedCategory = ref(inquiryStore.categoryId || 0)
const selectedLocation = ref(inquiryStore.locationId || 0)
const hasQuorum = computed(() => inquiryStore.miscFields?.quorum)
const quorumValue = computed(() => inquiryStore.miscFields?.quorum || 0)

const isLoaded = ref(false)

const hasSupported = computed(() => inquiryStore.currentUserStatus.hasSupported)

// Get current inquiry type data
const inquiryTypeData = computed(() => {
  const data = getInquiryTypeData(inquiryStore.type, sessionStore.appSettings.inquiryTypeTab || [])
  return data
})

const availableInquiryStatuses = computed(() => {
  const statusesFromSettings = sessionStore.appSettings.inquiryStatusTab
    ?.filter((status) => status.inquiryType === inquiryStore.type)
    ?.sort((a, b) => a.order - b.order) || [];

  if (inquiryStore.status.inquiryStatus === 'draft') {
    statusesFromSettings.unshift({
      statusKey: 'draft',
      label: 'Draft',
      icon: 'draft',
      inquiryType: inquiryStore.type,
      order: 0,
    });
  }

  if (inquiryStore.status.inquiryStatus === 'waiting_approval') {
    statusesFromSettings.unshift({
      statusKey: 'waiting_approval',
      label: 'Waiting Approval',
      icon: 'waitingapproval',
      inquiryType: inquiryStore.type,
      order: 1,
    });
  }

  return statusesFromSettings;
});

const currentInquiryStatus = computed(
  () => {
    const specialStatuses = {
      'draft': {
	statusKey: 'draft',
	label: 'Draft',
	icon: 'draft',
	inquiryType: inquiryStore.type,
	order: 0,
      },
      'waiting_approval': {
	statusKey: 'waiting_approval',
	label: 'Waiting Approval',
	icon: 'waitingapproval',
	inquiryType: inquiryStore.type,
	order: 1,
      }
    };

    const currentStatus = inquiryStore.status.inquiryStatus;

    if (specialStatuses[currentStatus]) {
      return specialStatuses[currentStatus];
    }

    return availableInquiryStatuses.value.find(
      (status) => status.statusKey === currentStatus
    ) || specialStatuses.draft; 
  }
)

const selectedInquiryStatusKey = ref(currentInquiryStatus.value?.statusKey)
const currentInquiryStatusLabel = computed(() => currentInquiryStatus.value?.label || 'Draft')
const currentInquiryStatusIcon = computed(() => {
	const iconName = currentInquiryStatus.value?.icon || 'draft'
	return StatusIcons[iconName] || StatusIcons.Draft
})

const selectedInquiryStatus = computed({
  get: () => statusInquiryOptions.value.find(option => option.id === selectedInquiryStatusKey.value),
  set: (newValue) => {
    if (newValue) {
      selectedInquiryStatusKey.value = newValue.id
    }
  }
})

const onStatusChange = async (newStatus: string) => {
  try {
    const statusId = newStatus?.id || newStatus
    await inquiryStore.setInquiryStatus(statusId)
    showSuccess(t('agora', 'Inquiry status of this inquiry has been updated'))
  } catch {
    selectedInquiryStatusKey.value = currentInquiryStatus.value.statusKey
  }
}

const statusInquiryOptions = computed(() => 
  availableInquiryStatuses.value.map(status => ({
    id: status.statusKey,
    label: t('agora', status.label),
  }))
)

// Get hierarchy path for location and category display
function getHierarchyPath(items, targetId) {
  const itemMap = {}

  items.forEach((item) => {
    itemMap[item.id] = item
  })

  if (!itemMap[targetId]) {
    return 'ID not found'
  }

  function buildPath(item) {
    if (item.parentId === 0) {
      return item.name
    }
    const parent = itemMap[item.parentId]
    if (parent) {
      return `${buildPath(parent)} -> ${item.name}`
    }
    return item.name
  }

  return buildPath(itemMap[targetId])
}
// Watchers for the image
watch(() => inquiryStore.coverId, (newCoverId) => {
  if (newCoverId) {
    currentCoverUrl.value = getNextcloudPreviewUrl(newCoverId)
  } else {
    currentCoverUrl.value = ''
  }
}, { immediate: true })



// Watchers for location and category
watch(
  selectedLocation,
  (newVal) => {
    const rawValue = toRaw(newVal)
    if (rawValue) {
      inquiryStore.locationId = rawValue.value
    }
  },
  { deep: true }
)

watch(
  selectedCategory,
  (newVal) => {
    const rawValue = toRaw(newVal)
    if (rawValue) {
      inquiryStore.categoryId = rawValue.value
    }
  },
  { deep: true }
)

// Build hierarchy for location and category dropdowns
function buildHierarchy(list: BaseEntry[], parentId = 0, depth = 0): BaseEntry[] {
  if (!Array.isArray(list)) return []
  return list
    .filter((item) => item?.parentId === parentId)
    .map((item) => {
      const children = buildHierarchy(list, item.id, depth + 1)
      return {
	...item,
	depth,
	children,
      }
    })
    .flatMap((item) => [item, ...item.children])
}

const hierarchicalLocation = computed(() => {
  if (!Array.isArray(sessionStore.appSettings.locationTab)) return []
  return buildHierarchy(sessionStore.appSettings.locationTab).map((item) => ({
    value: item.id,
    label: `${'— '.repeat(item.depth ?? 0)}${item.name ?? '[no name]'}`,
    original: item,
  }))
})

const hierarchicalCategory = computed(() => {
  if (!Array.isArray(sessionStore.appSettings.categoryTab)) return []
  return buildHierarchy(sessionStore.appSettings.categoryTab).map((item) => ({
    value: item.id,
    label: `${'— '.repeat(item.depth ?? 0)}${item.name ?? '[no name]'}`,
    original: item,
  }))
})

// Initialize location and category
watch(
  hierarchicalLocation,
  (locations) => {
    if (!locations.length) return
    if (inquiryStore.locationId === 0) {
      selectedLocation.value = locations[0]
      inquiryStore.locationId = locations[0].value
    } else {
      const selected = locations.find((loc) => loc.value === inquiryStore.locationId)
      selectedLocation.value = selected || locations[0]
      inquiryStore.locationId = selected?.value || locations[0].value
    }
  },
  { immediate: true }
)

watch(
  hierarchicalCategory,
  (categories) => {
    if (!categories.length) return
    if (inquiryStore.categoryId === 0) {
      selectedCategory.value = categories[0]
      inquiryStore.categoryId = categories[0].value
    } else {
      const selected = categories.find((loc) => loc.value === inquiryStore.categoryId)
      selectedCategory.value = selected || categories[0]
      inquiryStore.categoryId = selected?.value || categories[0].value
    }
  },
  { immediate: true }
)

// Toggle Support

const onToggleSupport = async () => {
  // Store the current state before toggling
  const hadSupportedBefore = inquiryStore.currentUserStatus.hasSupported
  const supportValueBefore = inquiryStore.currentUserStatus.supportValue

  try {
    await supportsStore.toggleSupport(inquiryStore.id, sessionStore.currentUser.id, inquiryStore, inquiriesStore)

    // Get the updated state after toggling
    const hasSupportedAfter = inquiryStore.currentUserStatus.hasSupported
    const supportValueAfter = inquiryStore.currentUserStatus.supportValue

    if (inquiryStore.configuration.supportMode === 'simple') {
      if (hasSupportedAfter && !hadSupportedBefore) {
        showSuccess(t('agora', 'Inquiry supported, thanks for your support!'), { timeout: 2000 })
      } else if (!hasSupportedAfter && hadSupportedBefore) {
        showSuccess(t('agora', 'Inquiry support removed!'), { timeout: 2000 })
      }
    }
    else if (inquiryStore.configuration.supportMode === 'ternary') {
      if (supportValueAfter === 1) {
        showSuccess(t('agora', 'Inquiry supported, thanks for your support!'), { timeout: 2000 })
      } else if (supportValueAfter === 0) {
        showSuccess(t('agora', 'Neutral position saved!'), { timeout: 2000 })
      } else if (supportValueAfter === -1) {
        showSuccess(t('agora', 'Against position saved!'), { timeout: 2000 })
      } else if (supportValueAfter === null && hadSupportedBefore) {
        showSuccess(t('agora', 'Participation removed!'), { timeout: 2000 })
      }
    }

  } catch (error) {
    console.error('Failed to toggle support:', error)
    showError(t('agora', 'Failed to update support status'))
  }
}


// Event subscriptions
onMounted(() => {
  if (inquiryStore.coverId) { 
        currentCoverUrl.value = getNextcloudPreviewUrl(inquiryStore.coverId)
   }
  subscribe(Event.UpdateComments, () => commentsStore.load())
  isLoaded.value = true
})

onUnmounted(() => {
  isLoaded.value = false
  unsubscribe(Event.UpdateComments, () => commentsStore.load())
})

// Determine if category/location should be shown as select or label
const showCategoryAsLabel = computed(() => {
  const result = inquiryStore.parentId !== 0 || props.isReadonly
  return result
})

const showLocationAsLabel = computed(() => {
  const result = inquiryStore.parentId !== 0 || props.isReadonly
  return result
})

// Image URL function
function getNextcloudPreviewUrl(fileId, x = 1920, y = 1080, autoScale = true) {
  const baseUrl = window.location.origin
  return `${baseUrl}/index.php/core/preview?fileId=${fileId}&x=${x}&y=${y}&a=${autoScale}`
}

/**
 * Upload a single file and add to attachments list
 * @param event
 */
const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    showError(t('agora', 'Please select an image file'))
    return
  }

  // Check image size 5Mb max
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    showError(t('agora', 'Image size should be less than 5MB'))
    return
  }

  try {

    const response = await attachmentsStore.upload(inquiryStore.id, file,true)

    const attachment = {
      id: response.id ?? `temp-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      name: response.name ?? file.name,
      size: response.size ?? file.size,
      fileId: response.fileId ?? undefined,
      mimeType: response.mimeType ?? undefined,
    }

    // Use immutable update for better reactivity
    attachmentsStore.attachments = [...attachmentsStore.attachments, attachment]
    currentCoverUrl.value = getNextcloudPreviewUrl(attachment.fileId)
    inquiryStore.coverId=attachment.fileId
    showSuccess(t('agora', '{file} uploaded', { file: response.name ?? file.name }))
  } catch (error) {
    showError(t('agora', 'Failed to upload {file}', { file: file.name }))
    throw error // Re-throw to handle in parent function
    }

}

const timeExpirationRelative = computed(() => {
  if (inquiryStore.configuration.expire) {
    return DateTime.fromMillis(inquiryStore.configuration.expire * 1000).toRelative()
  }
  return t('agora', 'never')
})


// Format date
const formatDate = (timestamp: number) => new Date(timestamp * 1000).toLocaleDateString()
</script>

<template>
	<div v-if="isLoaded" class="inquiry-edit-view">
		<!-- Debug info -->
		<!-- Cover Image Section -->
<div v-if="inquiryStore.currentUserStatus?.isOwner" class="cover-image-section">
  <input
    id="cover-upload-input"
    ref="imageFileInput"
    type="file"
    class="hidden"
    accept="image/*"
    :aria-label="t('agora', 'Select cover image')"
    @change="handleImageUpload"
  />

  <div
    v-if="currentCoverUrl"
    class="cover-image-container"
    @click="triggerImageUpload"
  >
    <img
      :src="currentCoverUrl"
      :alt="t('agora', 'Inquiry cover image')"
      class="cover-image"
    />
    <div class="cover-image-overlay">
      <NcButton type="primary" class="change-cover-btn">
        <template #icon>
          <component :is="InquiryGeneralIcons.Edit" :size="20" />
        </template>
        {{ t('agora', 'Change cover image') }}
      </NcButton>
    </div>
  </div>

  <div
    v-else
    class="cover-image-placeholder"
    @click="triggerImageUpload"
  >
    <div class="placeholder-content">
      <component :is="InquiryGeneralIcons.Image" :size="48" class="placeholder-icon" />
      <NcButton type="primary" class="add-cover-btn">
        {{ t('agora', 'Add cover image') }}
      </NcButton>
      <p class="placeholder-text">{{ t('agora', 'Click to add a cover image') }}</p>
    </div>
  </div>
</div>

<!-- Cover Image for non-owners (but editable if not readonly) -->
<div
  v-else-if="currentCoverUrl"
  class="cover-image-section"
  :class="{ 'clickable': !props.isReadonly }"
  @click="!props.isReadonly && triggerImageUpload()"
>
  <img
    :src="currentCoverUrl"
    :alt="t('agora', 'Inquiry cover image')"
    class="cover-image"
  />
  <div v-if="!props.isReadonly" class="cover-image-overlay">
    <NcButton type="primary" class="change-cover-btn">
      <template #icon>
        <component :is="InquiryGeneralIcons.Edit" :size="20" />
      </template>
      {{ t('agora', 'Change cover image') }}
    </NcButton>
  </div>
</div>
<!--			<div class="cover-image-section" v-if="inquiryStore.currentUserStatus?.isOwner">
			<input
				id="cover-upload-input"
				ref="imageFileInput"
				type="file"
				class="hidden"
				accept="image/*"
				:aria-label="t('agora', 'Select cover image')"
				@change="handleImageUpload"
			/>

			<div 
				v-if="currentCoverUrl" 
				class="cover-image-container"
				@click="triggerImageUpload"
			>
				<img
					:src="currentCoverUrl"
					:alt="t('agora', 'Inquiry cover image')"
					class="cover-image"
				/>
				<div class="cover-image-overlay">
					<NcButton type="primary" class="change-cover-btn">
					<template #icon>
						<component :is="InquiryGeneralIcons.Edit" :size="20" />
						</template>
						{{ t('agora', 'Change cover image') }}
					</NcButton>
				</div>
			</div>

			<div 
				v-else 
				class="cover-image-placeholder"
				@click="triggerImageUpload"
			>
				<div class="placeholder-content">
					<component :is="InquiryGeneralIcons.Image" :size="48" class="placeholder-icon" />
					<NcButton type="primary" class="add-cover-btn">
						{{ t('agora', 'Add cover image') }}
					</NcButton>
					<p class="placeholder-text">{{ t('agora', 'Click to add a cover image') }}</p>
				</div>
			</div>
</div>

		<div 
			v-else-if="currentCoverUrl" 
			class="cover-image-section readonly"
		>
			<img
				:src="currentCoverUrl"
				:alt="t('agora', 'Inquiry cover image')"
				class="cover-image"
			/>
		</div> -->
		
		<!-- User info section -->
		<div class="user-info-section">
			<div class="header-left-content">
				<component
						:is="NcAvatar"
						v-if="inquiryStore.ownedGroup !== ''"
						:display-name="inquiryStore.ownedGroup"
						:show-user-status="false"
						:size="44"
						/>
				<component
						:is="NcAvatar"
						v-else
						:user="inquiryStore.owner.id"
						:display-name="inquiryStore.owner.displayName"
						:size="44"
						/>
			</div>
		</div>

		<!-- Main content section -->
		<div class="main-content-section">
			<!-- Title row with counters -->
			<div class="title-row">
				<div class="title-content">
					<h1 class="inquiry-title">{{ inquiryStore.title }}</h1>
				</div>
				<div class="counters">
					<div v-if="canComment(context)" class="counter-item">
						<component :is="InquiryGeneralIcons.Comment" :size="20" />
                        <span>{{ commentsStore.comments.length || 0 }}</span>
                    </div>
                    <div
                            v-if="canSupport(context)"
                            class="metadata-item supports"
                            :title="
                                    t('agora', '{count} supports', {
                                    count: inquiryStore.status.countSupports || 0,
                                    })
                                    "
                            @click="onToggleSupport"
                            >
                            <TernarySupportIcon
                                    v-if="inquiryStore.configuration.supportMode === 'ternary'"
                                    :support-value="inquiryStore.currentUserStatus.supportValue"
                                    :size="22"
                                    />
                            <ThumbIcon
                                    v-else
                                    :supported="inquiryStore.currentUserStatus.hasSupported"
                                    :size="22"
                                    />
                            <span>{{ inquiryStore.status.countSupports }}</span>
                            <span v-if="hasQuorum" class="quorum-compact">
                                <span class="quorum-separator"> / </span>
                                <span class="quorum-target">{{ quorumValue }}</span>
                                <span class="quorum-label">{{ t('agora', 'supports') }}</span>
                            </span>
                    </div>
                </div>
            </div>

            <!-- Metadata section -->
            <div class="metadata-section">
                <div class="metadata-grid">
                    <div class="metadata-item">
                        <component :is="inquiryTypeData.icon" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Type') }}:</span>
                        <span class="metadata-value">{{ inquiryTypeData.label }}</span>
                    </div>

                    <div class="metadata-item">
                        <component :is="InquiryGeneralIcons.Location" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Location') }}:</span>
                        <NcSelect
                                v-if="!showLocationAsLabel"
                                v-model="selectedLocation"
                                :options="hierarchicalLocation"
                                :clearable="false"
                                :label-outside="true"
                                class="select-field location-select"
                                required
                                />
                        <span v-else class="metadata-value">
                            {{ getHierarchyPath(sessionStore.appSettings.locationTab, inquiryStore.locationId) || t('agora', 'Inherited from parent') }}
                        </span>
                    </div>

                    <div class="metadata-item">
                        <component :is="StatusIcons.Calendar" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Created') }}:</span>
                        <span class="metadata-value">{{ formatDate(inquiryStore.status.created) }}</span>
                    </div>

                    <div class="metadata-item">
                        <component :is="InquiryGeneralIcons.Tag" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Category') }}:</span>
                        <NcSelect
                                v-if="!showCategoryAsLabel"
                                v-model="selectedCategory"
                                :options="hierarchicalCategory"
                                :clearable="false"
                                :label-outside="true"
                                class="select-field category-select"
                                required
                                />
                        <span v-else class="metadata-value">
                            {{ getHierarchyPath(sessionStore.appSettings.categoryTab, inquiryStore.categoryId) || t('agora', 'Inherited from parent') }}
                        </span>
                    </div>

                    <div class="metadata-item last-interaction-item">
                        <component :is="StatusIcons.Updated" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Last interaction') }}:</span>
                        <span class="metadata-value">{{ formatDate(inquiryStore.status.lastInteraction) }}</span>
                    </div>

                    <div class="metadata-item">
                        <component :is="currentInquiryStatusIcon" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Status') }}:</span>
                        <template v-if="sessionStore.currentUser.isModerator">
                            <NcSelect
                                    v-model="selectedInquiryStatus"
                                    :options="statusInquiryOptions"
                                    :clearable="false"
                                    class="status-select"
                                    @update:model-value="onStatusChange"
                                    />
                        </template>
                        <template v-else>
                            <span class="metadata-value">{{ t('agora', currentInquiryStatusLabel) }}</span>
                        </template>
                    </div>

                    <div v-if="inquiryStore.configuration.expire" class="metadata-item">
                        <component :is="InquiryGeneralIcons.Expiration" :size="16" />
                        <span class="metadata-label">{{ t('agora', 'Expires') }}:</span>
                        <span class="metadata-value">{{ timeExpirationRelative }}</span>
                    </div>
                </div>
            </div>

            <!-- Description section -->
            <div class="description-section">
                <h3 class="section-title">{{ t('agora', 'Description') }}</h3>
                <div class="description-content">
                    <div
                            v-if="sessionStore.appSettings.inquiryTypeRights[inquiryStore.type]?.editorType === 'wysiwyg'"
                            class="editor-container"
                            >
                            <InquiryEditor v-model="inquiryStore.description" :readonly="props.isReadonlyDescription" />
                    </div>

                        <div
                                v-else-if="sessionStore.appSettings.inquiryTypeRights[inquiryStore.type]?.editorType === 'texteditor'"
                                class="editor-container"
                                >
                                <NcRichText
                                        v-model="inquiryStore.description"
                                        :autolink="true"
                                        :use-markdown="true"
                                        :disabled="props.isReadonlyDescription"
                                        class="rich-text-editor"
                                        />
                        </div>

                            <div v-else class="editor-container">
                                <NcTextArea
                                        v-model="inquiryStore.description"
                                        :disabled="props.isReadonlyDescription"
                                        class="text-area-editor"
                                        :rows="8"
                                        />
                            </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.cover-image-section {
    width: 100%;
    margin-bottom: 2rem;
    border-radius: var(--border-radius-large);
    overflow: hidden;
    position: relative;

    &:not(.readonly) {
        cursor: pointer;
        border: 2px dashed var(--color-border);
        transition: border-color 0.2s ease;

        &:hover {
            border-color: var(--color-primary);

            .cover-image-overlay {
                opacity: 1;
            }

            .cover-image-placeholder {
                background: var(--color-background-hover);
            }
        }
    }
}

    .cover-image-container {
        position: relative;
        width: 100%;
        height: 300px;

        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    }

    .cover-image-placeholder {
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--color-background-dark);
        border-radius: var(--border-radius-large);
        transition: background-color 0.2s ease;

        .placeholder-content {
            text-align: center;

            .placeholder-icon {
                color: var(--color-text-lighter);
                margin-bottom: 1rem;
            }

            .placeholder-text {
                margin-top: 0.5rem;
                color: var(--color-text-lighter);
                font-size: 0.9rem;
            }
        }
    }

    .hidden {
        display: none;

    }
    .inquiry-edit-view {
        padding: 10px;
        background: var(--color-main-background);
        border-radius: var(--border-radius-large);
    }

    .cover-image-section {
        width: 100%;
        margin-bottom: 1rem;
        border-radius: var(--border-radius-large);
        overflow: hidden;
        position: relative;
        cursor: pointer;

        &:hover {
            .cover-image-overlay {
                opacity: 1;
            }
        }
    }

    /* File upload section */
    .attachment-upload {
        margin-bottom: 1rem;
        padding: 1rem;
        background: var(--color-background-dark);
        border-radius: var(--border-radius-large);
        z-index: 10;
        position: relative;
    }

    .hidden {
        display: none;
    }

    /* User info section */
    .user-info-section {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        background: var(--color-background-dark);
        border-radius: var(--border-radius);
        position: relative;
        z-index: 5;
    }

    .header-left-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: var(--color-primary);
    }

    /* Main content section */
    .main-content-section {
        background: var(--color-background-dark);
        border-radius: var(--border-radius-large);
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    /* Title row */
    .title-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }

    .title-content {
        flex: 1;
    }

    .inquiry-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-primary-text);
        margin: 0;
        line-height: 1.3;
    }

    .counters {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }

    .counter-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: var(--border-radius);
        transition: background-color 0.2s;

        &:hover {
            background: var(--color-background-hover);
        }

        span {
            font-weight: bold;
            color: var(--color-primary-text);
        }
    }

    /* Metadata section */
    .metadata-section {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: var(--color-background-darker);
        border-radius: var(--border-radius);
    }

    .metadata-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 0.75rem;
    }

    .metadata-item:nth-child(1),
    .metadata-item:nth-child(2) {
        justify-self: start;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .metadata-item:nth-child(3),
    .metadata-item:nth-child(5),
    .metadata-item:nth-child(6) {
        justify-self: end;
        display: flex;
        align-items: center;
        gap: 0.5rem;

    }

    .metadata-item {
        font-size: 0.9rem;
        &.last-interaction-item {
            justify-self: start;
            padding-left: 40px;
        }
    }

    .metadata-label {
        font-weight: 600;
        color: var(--color-text-lighter);
        white-space: nowrap;
    }

    .metadata-value {
        color: var(--color-primary-text);
        white-space: nowrap;
    }

    .select-field {
        width: 100%;

        &.location-select,
        &.category-select {
            max-width: 250px;
        }
    }

    .status-select {
        min-width: 120px;
    }

    /* Description section */
    .description-section {
        margin-bottom: 1rem;
    }

    .section-title {
        color: var(--color-primary);
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .description-content {
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius);
        background: var(--color-main-background);
        overflow: hidden;
    }

    .editor-container {
        width: 100%;

        &.rich-text-container {
            min-height: 250px;
        }
    }

    .rich-text-editor,
    .text-area-editor {
        width: 100%;
        border: none;
        min-height: 200px;
    }

    .rich-text-editor {
        :deep(.ProseMirror) {
            min-height: 200px;
            padding: 12px;
        }
    }

    .text-area-editor {
        resize: vertical;
        padding: 12px;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .title-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .counters {
            width: 100%;
            justify-content: space-around;
        }

        .metadata-grid {
            grid-template-columns: 1fr;
        }

        .metadata-item {
            flex-wrap: wrap;
        }

        .select-field.location-select,
        .select-field.category-select {
            max-width: 100%;
        }
    }
</style>
