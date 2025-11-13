<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref, onMounted, watch, computed } from 'vue'
import { 
  NcButton, 
  NcSelect, 
  NcModal, 
  NcTextField,
  NcNoteCard 
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { useInquiryLinksStore } from '../../stores/inquiryLinks'
import { useAttachmentsStore } from '../../stores/attachments'
import { useInquiryStore } from '../../stores/inquiry'
import { InquiryGeneralIcons } from '../../utils/icons.ts'

// Props
const props = defineProps<{
  open: boolean
  availableApps?: string[]
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const inquiryLinksStore = useInquiryLinksStore()
const attachmentsStore = useAttachmentsStore()
const inquiryStore = useInquiryStore()

// State
const isCreating = ref(false)
const selectedResourceId = ref<string>('')
const selectedResourceType = ref<'files' | 'forms' | 'deck' | 'cospend' | 'collectives'>('files')
const resources = ref<any[]>([])
const isLoading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])

// Form data
const resourceTitle = ref(inquiryStore.title ? `${inquiryStore.title}` : '')
const resourceDescription = ref(inquiryStore.description || '')

// Resource types configuration
const resourceTypes = [
  {
    id: 'files',
    name: t('agora', 'Files'),
    icon: InquiryGeneralIcons.Document,
    description: t('agora', 'Upload and share files'),
  },
  {
    id: 'forms',
    name: t('agora', 'Forms'),
    icon: InquiryGeneralIcons.Form,
    description: t('agora', 'Create surveys and collect responses'),
  },
  {
    id: 'polls', 
    name: t('agora', 'Polls'),
    icon: InquiryGeneralIcons.Poll,
    description: t('agora', 'Create simple polls and votes'),
  },
  {
    id: 'deck',
    name: t('agora', 'Deck Cards'),
    icon: InquiryGeneralIcons.Deck,
    description: t('agora', 'Create task cards in Deck'),
  },
  {
    id: 'cospend',
    name: t('agora', 'Cospend Expenses'),
    icon: InquiryGeneralIcons.Money,
    description: t('agora', 'Track shared expenses'),
  },
  {
    id: 'collectives',
    name: t('agora', 'Collectives'),
    icon: InquiryGeneralIcons.Collectives,
    description: t('agora', 'Link to collective pages'),
  }
]

// Filter resource types based on available apps
const availableResourceTypes = computed(() => {
  if (!props.availableApps || props.availableApps.length === 0) {
    return resourceTypes
  }
  
  return resourceTypes.filter(type => {
    // Files are always available
    if (type.id === 'files') return true
    
    // Check if app is available
    const appMapping: Record<string, string> = {
      forms: 'forms',
      polls: 'polls', 
      deck: 'deck',
      cospend: 'cospend',
      collectives: 'collectives'
    }
    
    const appId = appMapping[type.id]
    return appId ? props.availableApps?.includes(appId) : true
  })
})

// Computed
const resourceOptions = computed(() => resources.value.map(resource => ({
    id: resource.id.toString(),
    label: resource.title || resource.name || resource.filename,
    name: resource.title || resource.name || resource.filename,
    description: resource.description || t('agora', 'No description'),
  })))

const currentResourceType = computed(() => availableResourceTypes.value.find(type => type.id === selectedResourceType.value))

const hasResources = computed(() => resources.value.length > 0)
const canCreateResource = computed(() => {
  if (selectedResourceType.value === 'files') {
    return selectedFiles.value.length > 0
  }
  return resourceTitle.value.trim().length > 0
})
const canLinkResource = computed(() => selectedResourceId.value !== '')
const canSubmit = computed(() => canCreateResource.value || canLinkResource.value)
const isFileType = computed(() => selectedResourceType.value === 'files')

// Helper functions
const getResourceName = (type: any): string => type?.name || 'resource'

const getResourceNameLower = (type: any): string => type?.name?.toLowerCase() || 'resource'

// Lifecycle
onMounted(async () => {
  if (props.open) {
    await loadResources()
  }
})

// Watch for modal open
watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    await loadResources()
    // Reset form when opening
    selectedResourceId.value = ''
    selectedFiles.value = []
    resourceTitle.value = inquiryStore.title ? `${inquiryStore.title}` : ''
    resourceDescription.value = inquiryStore.description || ''
  }
})

// Watch for resource type changes
watch(selectedResourceType, async () => {
  await loadResources()
  selectedResourceId.value = ''
  selectedFiles.value = []
})

const loadResources = async () => {
  try {
    isLoading.value = true
    resources.value = []
    
    const resourceType = currentResourceType.value
    if (!resourceType) return

    if (resourceType.id === 'forms') {
      if (inquiryLinksStore.getOwnedForms && inquiryLinksStore.getSharedForms) {
        const [ownedResources, sharedResources] = await Promise.all([
          inquiryLinksStore.getOwnedForms().catch(() => []),
          inquiryLinksStore.getSharedForms().catch(() => [])
        ])
        resources.value = [...ownedResources, ...sharedResources]
      }
    } else if (resourceType.id === 'files') {
      // For files, load existing attachments
      resources.value = attachmentsStore.attachments
        .filter(att => att.fileId !== inquiryStore.coverId)
        .map(att => ({
          id: att.id,
          filename: att.name,
          name: att.name,
          size: att.size,
          url: att.url
        }))
    } else {
      console.log(`Loading ${resourceType.id} not implemented yet`)
    }
  } catch (error) {
    console.error('Error loading resources:', error)
    showError(t('agora', 'Failed to load resources'))
    resources.value = []
  } finally {
    isLoading.value = false
  }
}

// File handling methods
const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (!files) return

  selectedFiles.value = Array.from(files)
  target.value = ''
}

const removeSelectedFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const unitIndex = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, unitIndex)).toFixed(2))} ${sizes[unitIndex]}`
}

const createNewResource = async () => {
  if (!canCreateResource.value) {
    showError(t('agora', 'Title is required'))
    return
  }

  try {
    isCreating.value = true
    const resourceType = currentResourceType.value
    if (!resourceType) throw new Error('No resource type selected')

    let newResource

    switch (resourceType.id) {
      case 'forms':
        if (!inquiryLinksStore.createFormViaAPI) {
          throw new Error('createFormViaAPI method not available')
        }
        
        newResource = await inquiryLinksStore.createFormViaAPI({
          title: resourceTitle.value,
          description: resourceDescription.value
        })

        if (inquiryStore.expires && inquiryLinksStore.updateFormViaAPI) {
          await inquiryLinksStore.updateFormViaAPI(newResource.id, {
            expires: inquiryStore.expires,
            showExpiration: true,
          })
        }

        // Create the link for forms
        await inquiryLinksStore.create({
          inquiryId: inquiryStore.id,
          targetApp: resourceType.id,
          targetType: 'form',
          targetId: newResource.id.toString(),
          sortOrder: 0
        })
        break

      case 'files':
        // FIX: Upload files and update attachments store
        if (selectedFiles.value.length === 0) {
          throw new Error('No files selected')
        }

        for (const file of selectedFiles.value) {
          const response = await attachmentsStore.upload(inquiryStore.id, file, false)
          newResource = response
          
          // Update attachments store reactively - push to array
          attachmentsStore.attachments.push({
            id: response.id,
            name: response.name,
            size: response.size,
            url: response.url,
            fileId: response.id
          })
        }
        break

      case 'polls':
        throw new Error('Polls not implemented yet')
      case 'deck':
        throw new Error('Deck cards not implemented yet')
      case 'cospend':
        throw new Error('Cospend expenses not implemented yet')
      case 'collectives':
        throw new Error('Collectives not implemented yet')
      default:
        throw new Error(`Unknown resource type: ${resourceType.id}`)
    }

    const successMessage = resourceType.id === 'files' 
      ? t('agora', 'Files uploaded successfully')
      : t('agora', '{resource} created and linked successfully', { resource: resourceType.name })

    showSuccess(successMessage)
    closeModal()
    
  } catch (error) {
    console.error('Error creating resource:', error)
    showError(
      t('agora', 'Failed to create {resource}', 
        { resource: currentResourceType.value?.name || 'resource' })
    )
  } finally {
    isCreating.value = false
  }
}

const linkExistingResource = async () => {
  if (!canLinkResource.value) {
    showError(t('agora', 'Please select a resource'))
    return
  }

  try {
    isCreating.value = true
    const resourceType = currentResourceType.value
    if (!resourceType) throw new Error('No resource type selected')

    await inquiryLinksStore.create({
      inquiryId: inquiryStore.id,
      targetApp: resourceType.id,
      targetType: resourceType.id === 'forms' ? 'form' : resourceType.id,
      targetId: selectedResourceId.value,
      sortOrder: 0
    })

    showSuccess(
      t('agora', '{resource} linked successfully', 
        { resource: resourceType.name })
    )
    closeModal()
    
  } catch (error) {
    console.error('Error linking resource:', error)
    showError(
      t('agora', 'Failed to link {resource}', 
        { resource: currentResourceType.value?.name || 'resource' })
    )
  } finally {
    isCreating.value = false
  }
}

const closeModal = () => {
  emit('update:open', false)
  isCreating.value = false
  selectedResourceId.value = ''
  selectedResourceType.value = 'files'
  selectedFiles.value = []
}

const handleSubmit = () => {
  if (canLinkResource.value) {
    linkExistingResource()
  } else if (canCreateResource.value) {
    createNewResource()
  }
}

// Handle select change
const handleSelectChange = (selected: any) => {
  if (selected) {
    selectedResourceId.value = selected.id
    resourceTitle.value = ''
    resourceDescription.value = ''
    selectedFiles.value = []
  } else {
    selectedResourceId.value = ''
  }
}

// Handle title input - clear selection when user starts typing
const handleTitleInput = () => {
  if (selectedResourceId.value) {
    selectedResourceId.value = ''
  }
}
</script>

<template>
  <NcModal
    v-if="open"
    :name="t('agora', 'Add Resource to Inquiry')"
    size="large"
    @close="closeModal"
  >
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ t('agora', 'Add Resource to Inquiry') }}</h2>
        <p class="modal-description">
          {{ t('agora', 'Link an existing resource or create a new one for this inquiry') }}
        </p>
      </div>

      <div class="modal-body">
        <!-- Resource Type Selection -->
        <div class="resource-type-section">
          <h3 class="section-title">{{ t('agora', 'Resource Type') }}</h3>
          <div class="resource-type-grid">
            <div
              v-for="type in availableResourceTypes"
              :key="type.id"
              class="resource-type-card"
              :class="{ active: selectedResourceType === type.id }"
              @click="selectedResourceType = type.id"
            >
              <component :is="type.icon" :size="24" class="type-icon" />
              <div class="type-info">
                <h4 class="type-name">{{ type.name }}</h4>
                <p class="type-description">{{ type.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Link Existing Resource Section -->
        <div v-if="!isFileType" class="form-section">
          <div class="section-header">
            <component :is="InquiryGeneralIcons.Link" :size="20" class="section-icon" />
            <h3>{{ t('agora', 'Link Existing {resource}', { resource: currentResourceType?.name }) }}</h3>
          </div>
          
          <NcNoteCard 
            v-if="!hasResources && !isLoading" 
            type="info" 
            class="info-card"
          >
            {{ t('agora', 'No {resource} found. You can create a new one instead.', { resource: getResourceNameLower(currentResourceType) }) }}
          </NcNoteCard>

          <div v-if="isLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <span>{{ t('agora', 'Loading your {resource} …', { resource: getResourceNameLower(currentResourceType) }) }}</span>
          </div>

          <div v-else-if="hasResources" class="select-wrapper">
            <label class="select-label">{{ t('agora', 'Choose a {resource} to link', { resource: getResourceNameLower(currentResourceType) }) }}</label>
            <NcSelect
              v-model="selectedResourceId"
              :options="resourceOptions"
              :clearable="true"
              :placeholder="t('agora', 'Select a {resource} …', { resource: getResourceNameLower(currentResourceType) })"
              :disabled="isCreating"
              @update:model-value="handleSelectChange"
            />
            <p class="select-hint">
              {{ t('agora', 'Select a {resource} you own or have access to', { resource: getResourceNameLower(currentResourceType) }) }}
            </p>
          </div>
        </div>

        <!-- Separator -->
        <div v-if="!isFileType" class="separator">
          <span class="separator-text">{{ t('agora', 'OR') }}</span>
        </div>

        <!-- Create New Resource Section -->
        <div class="form-section">
          <div class="section-header">
            <component :is="InquiryGeneralIcons.Plus" :size="20" class="section-icon" />
            <h3>
              <span v-if="isFileType">{{ t('agora', 'Upload Files') }}</span>
              <span v-else>{{ t('agora', 'Create New {resource}', { resource: getResourceName(currentResourceType) }) }}</span>
            </h3>
          </div>

          <div class="create-resource-fields">
            <!-- File Upload Section -->
            <div v-if="isFileType" class="file-upload-section">
              <input
                ref="fileInput"
                type="file"
                multiple
                class="hidden"
                accept="*/*"
                @change="handleFileSelect"
              />
              <NcButton
                type="secondary"
                class="file-select-button"
                @click="triggerFileInput"
              >
                <template #icon>
                  <component :is="InquiryGeneralIcons.Attachment" :size="20" />
                </template>
                {{ t('agora', 'Select Files') }}
              </NcButton>

              <!-- Selected Files List -->
              <div v-if="selectedFiles.length > 0" class="selected-files">
                <h4 class="files-title">{{ t('agora', 'Selected Files') }}</h4>
                <div class="file-list">
                  <div
                    v-for="(file, index) in selectedFiles"
                    :key="index"
                    class="file-item"
                  >
                    <div class="file-info">
                      <component :is="InquiryGeneralIcons.Document" :size="16" class="file-icon" />
                      <span class="file-name">{{ file.name }}</span>
                      <span class="file-size">{{ formatFileSize(file.size) }}</span>
                    </div>
                    <NcButton
                      type="error"
                      :aria-label="t('agora', 'Remove file')"
                      @click="removeSelectedFile(index)"
                    >
                      <template #icon>
                        <component :is="InquiryGeneralIcons.Delete" :size="16" />
                      </template>
                    </NcButton>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Fields for other resource types -->
            <div v-else>
              <NcTextField
                v-model="resourceTitle"
                :label="t('agora', '{resource} Title', { resource: getResourceName(currentResourceType) })"
                :placeholder="t('agora', 'Enter {resource} title', { resource: getResourceNameLower(currentResourceType) })"
                :disabled="isCreating"
                :required="true"
                class="form-field"
                @update:model-value="handleTitleInput"
              />
              
              <NcTextField
                v-model="resourceDescription"
                :label="t('agora', 'Description')"
                :placeholder="t('agora', 'Enter description (optional)')"
                :disabled="isCreating"
                type="textarea"
                class="form-field"
              />

              <NcNoteCard v-if="inquiryStore.expires && selectedResourceType === 'forms'" type="info" class="info-card">
                {{ t('agora', 'This form will inherit the inquiry expiration date.') }}
              </NcNoteCard>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="modal-actions">
        <NcButton 
          :disabled="isCreating"
          class="cancel-button"
          @click="closeModal"
        >
          {{ t('agora', 'Cancel') }}
        </NcButton>
        <NcButton
          type="primary"
          :disabled="isCreating || !canSubmit"
          :loading="isCreating"
          class="submit-button"
          @click="handleSubmit"
        >
          <template #icon>
            <component :is="InquiryGeneralIcons.Plus" :size="20" />
          </template>
          <span v-if="isCreating">{{ t('agora', 'Creating …') }}</span>
          <span v-else-if="isFileType">{{ t('agora', 'Upload Files') }}</span>
          <span v-else>{{ t('agora', 'Add {resource}', { resource: getResourceName(currentResourceType) }) }}</span>
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<style scoped lang="scss">
.modal-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  
  .modal-header {
    padding: calc(var(--default-grid-baseline) * 2);
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
    
    h2 {
      margin: 0 0 calc(var(--default-grid-baseline) / 2) 0;
      font-size: 1.5em;
      font-weight: 600;
      color: var(--color-text);
    }
    
    .modal-description {
      margin: 0;
      color: var(--color-text-lighter);
      font-size: 0.9em;
    }
  }
  
  .modal-body {
    padding: calc(var(--default-grid-baseline) * 2);
    flex: 1;
    overflow-y: auto;
  }

  .resource-type-section {
    margin-bottom: calc(var(--default-grid-baseline) * 2);

    .section-title {
      margin: 0 0 var(--default-grid-baseline) 0;
      font-size: 1.1em;
      font-weight: 600;
      color: var(--color-text);
    }

    .resource-type-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--default-grid-baseline);
    }

    .resource-type-card {
      display: flex;
      align-items: flex-start;
      gap: calc(var(--default-grid-baseline) / 2);
      padding: var(--default-grid-baseline);
      border: 2px solid var(--color-border);
      border-radius: var(--border-radius);
      cursor: pointer;
      transition: all 0.2s ease;

      &:hover {
        border-color: var(--color-primary-element);
        background-color: var(--color-background-hover);
      }

      &.active {
        border-color: var(--color-primary-element);
        background-color: var(--color-primary-element-light);
      }

      .type-icon {
        flex-shrink: 0;
        color: var(--color-primary-element);
      }

      .type-info {
        flex: 1;

        .type-name {
          margin: 0 0 2px 0;
          font-size: 0.9em;
          font-weight: 600;
          color: var(--color-text);
        }

        .type-description {
          margin: 0;
          font-size: 0.8em;
          color: var(--color-text-lighter);
          line-height: 1.3;
        }
      }
    }
  }
  
  .form-section {
    margin-bottom: calc(var(--default-grid-baseline) * 2);
    
    .section-header {
      display: flex;
      align-items: center;
      gap: calc(var(--default-grid-baseline) / 2);
      margin-bottom: var(--default-grid-baseline);
      
      h3 {
        margin: 0;
        font-size: 1.1em;
        font-weight: 600;
        color: var(--color-text);
      }
      
      .section-icon {
        color: var(--color-primary-element);
      }
    }
    
    .info-card {
      margin: var(--default-grid-baseline) 0;
    }
    
    .loading-state {
      display: flex;
      align-items: center;
      gap: calc(var(--default-grid-baseline) / 2);
      padding: var(--default-grid-baseline);
      color: var(--color-text-lighter);
      font-style: italic;
      
      .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid var(--color-border);
        border-top: 2px solid var(--color-primary-element);
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
    }
    
    .select-wrapper {
      .select-label {
        display: block;
        margin-bottom: calc(var(--default-grid-baseline) / 2);
        font-weight: 500;
        color: var(--color-text);
      }
      
      .select-hint {
        margin: calc(var(--default-grid-baseline) / 2) 0 0 0;
        font-size: 0.85em;
        color: var(--color-text-lighter);
      }
    }
    
    .create-resource-fields {
      .form-field {
        margin-bottom: var(--default-grid-baseline);
      }

      .file-upload-section {
        .hidden {
          display: none;
        }

        .file-select-button {
          margin-bottom: var(--default-grid-baseline);
        }

        .selected-files {
          .files-title {
            margin: 0 0 calc(var(--default-grid-baseline) / 2) 0;
            font-size: 1em;
            font-weight: 600;
            color: var(--color-text);
          }

          .file-list {
            display: flex;
            flex-direction: column;
            gap: calc(var(--default-grid-baseline) / 2);
          }

          .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: calc(var(--default-grid-baseline) / 2);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius);
            background-color: var(--color-background-dark);

            .file-info {
              display: flex;
              align-items: center;
              gap: calc(var(--default-grid-baseline) / 2);
              flex: 1;

              .file-icon {
                color: var(--color-text-lighter);
              }

              .file-name {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
              }

              .file-size {
                color: var(--color-text-lighter);
                font-size: 0.85em;
              }
            }
          }
        }
      }
    }
  }
  
  .separator {
    display: flex;
    align-items: center;
    margin: calc(var(--default-grid-baseline) * 2) 0;
    
    &::before,
    &::after {
      content: '';
      flex: 1;
      height: 1px;
      background-color: var(--color-border);
    }
    
    .separator-text {
      padding: 0 var(--default-grid-baseline);
      color: var(--color-text-lighter);
      font-weight: 600;
      font-size: 0.9em;
      text-transform: uppercase;
    }
  }

  .modal-actions {
    display: flex;
    gap: var(--default-grid-baseline);
    justify-content: flex-end;
    padding: calc(var(--default-grid-baseline) * 2);
    border-top: 1px solid var(--color-border);
    flex-shrink: 0;
    
    .cancel-button,
    .submit-button {
      min-width: 100px;
    }
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
