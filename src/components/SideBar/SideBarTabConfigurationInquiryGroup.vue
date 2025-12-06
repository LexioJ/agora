<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'
import { showSuccess, showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { useInquiryGroupStore } from '../../stores/inquiryGroup.ts'
import { useSessionStore } from '../../stores/session'

// Import icons from InquiryGeneralIcons
import { InquiryGeneralIcons, StatusIcons } from '../../utils/icons.ts'

// Emits
const emit = defineEmits<{
  saved: []
}>()

// Stores
const inquiryGroupStore = useInquiryGroupStore()
const sessionStore = useSessionStore()

// State
const loading = ref(false)
const saving = ref(false)
const imageFileInput = ref<HTMLInputElement | null>(null)
const currentCoverUrl = ref('')

// Form validation
const formErrors = ref({
  title: '',
  titleExt: '',
  description: '',
})

// Computed
const availableGroupStatuses = computed(() => {
  return [
    { id: 'draft', label: t('agora', 'Draft'), icon: StatusIcons.Draft },
    { id: 'active', label: t('agora', 'Active'), icon: StatusIcons.Active },
    { id: 'archived', label: t('agora', 'Archived'), icon: StatusIcons.Archived },
  ]
})

const statusOptions = computed(() => 
  availableGroupStatuses.value.map(status => ({
    id: status.id,
    label: status.label,
  }))
)

const canEditGroup = computed(() => {
  return inquiryGroupStore.currentUserStatus?.isOwner || 
         sessionStore.currentUser.type === 'admin' ||
         inquiryGroupStore.allowEdit
})

// Check if form has changes
const hasChanges = computed(() => {
  return inquiryGroupStore.title !== '' || 
         inquiryGroupStore.titleExt !== '' || 
         inquiryGroupStore.description !== ''
})

// Methods
const loadGroup = async () => {
  if (!inquiryGroupStore.id) return
  
  loading.value = true
  try {
    await inquiryGroupStore.load(inquiryGroupStore.id)
    
    // Load cover image
    if (inquiryGroupStore.coverId) {
      currentCoverUrl.value = getNextcloudPreviewUrl(inquiryGroupStore.coverId)
    }
  } catch (error) {
    console.error('Failed to load group:', error)
    showError(t('agora', 'Failed to load group data'))
  } finally {
    loading.value = false
  }
}

const getNextcloudPreviewUrl = (fileId: number, x: number = 400, y: number = 200) => {
  const baseUrl = window.location.origin
  return `${baseUrl}/index.php/core/preview?fileId=${fileId}&x=${x}&y=${y}&a=1`
}

const triggerImageUpload = () => {
  if (!canEditGroup.value) return
  imageFileInput.value?.click()
}

const handleImageUpload = async (event: Event) => {
  if (!canEditGroup.value) return
  
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    showError(t('agora', 'Please select an image file'))
    return
  }

  const maxSize = 5 * 1024 * 1024 // 5MB
  if (file.size > maxSize) {
    showError(t('agora', 'Image size should be less than 5MB'))
    return
  }

  try {
    saving.value = true
    
    // Here you would call your API to upload the image
    const reader = new FileReader()
    reader.onload = (e) => {
      currentCoverUrl.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
    
    showSuccess(t('agora', 'Cover image uploaded successfully'))
  } catch (error) {
    console.error('Failed to upload image:', error)
    showError(t('agora', 'Failed to upload cover image'))
  } finally {
    saving.value = false
    if (imageFileInput.value) {
      imageFileInput.value.value = ''
    }
  }
}

const validateForm = () => {
  let isValid = true
  formErrors.value = { title: '', titleExt: '', description: '' }

  if (!inquiryGroupStore.title.trim()) {
    formErrors.value.title = t('agora', 'Group title is required')
    isValid = false
  } else if (inquiryGroupStore.title.length > 200) {
    formErrors.value.title = t('agora', 'Title must be less than 200 characters')
    isValid = false
  }

  if (inquiryGroupStore.titleExt.length > 200) {
    formErrors.value.titleExt = t('agora', 'Extended title must be less than 200 characters')
    isValid = false
  }

  if (inquiryGroupStore.description.length > 1000) {
    formErrors.value.description = t('agora', 'Description must be less than 1000 characters')
    isValid = false
  }

  return isValid
}

const saveGroup = async () => {
  if (!canEditGroup.value) {
    showError(t('agora', 'You do not have permission to edit this group'))
    return
  }

  if (!validateForm()) {
    return
  }

  saving.value = true
  try {
    await inquiryGroupStore.update({
      title: inquiryGroupStore.title.trim(),
      titleExt: inquiryGroupStore.titleExt.trim(),
      description: inquiryGroupStore.description.trim(),
      groupStatus: inquiryGroupStore.groupStatus,
      expire: inquiryGroupStore.expire,
    })
    
    showSuccess(t('agora', 'Group settings saved successfully'))
    emit('saved')
  } catch (error) {
    console.error('Failed to save group:', error)
    showError(t('agora', 'Failed to save group settings'))
  } finally {
    saving.value = false
  }
}

const removeCoverImage = async () => {
  if (!canEditGroup.value) return
  
  try {
    currentCoverUrl.value = ''
    inquiryGroupStore.coverId = null
    showSuccess(t('agora', 'Cover image removed'))
  } catch (error) {
    console.error('Failed to remove cover image:', error)
    showError(t('agora', 'Failed to remove cover image'))
  }
}

const formatDate = (timestamp: number | null) => {
  if (!timestamp) return t('agora', 'Never')
  return new Date(timestamp * 1000).toLocaleDateString()
}

const getFormFieldState = (field: keyof typeof formErrors) => {
  return {
    error: !!formErrors.value[field],
    success: !formErrors.value[field] && inquiryGroupStore[field as keyof typeof inquiryGroupStore] ? true : undefined,
    helperText: formErrors.value[field],
  }
}

// Lifecycle
onMounted(() => {
  loadGroup()
})
</script>

<template>
  <div class="inquiry-group-sidebar-settings">
    <!-- Header -->
    <div class="sidebar-header">
      <h2>{{ t('agora', 'Group Configuration') }}</h2>
      <p class="subtitle">{{ inquiryGroupStore.title || t('agora', 'Untitled group') }}</p>
      
      <div v-if="!canEditGroup" class="readonly-notice">
        <component :is="InquiryGeneralIcons.AlertCircle" />
        <span>{{ t('agora', 'Read-only mode') }}</span>
      </div>
    </div>

    <!-- Cover Image -->
    <div class="settings-section">
      <h3 class="section-title">
        <component :is="InquiryGeneralIcons.Image" />
        {{ t('agora', 'Cover Image') }}
      </h3>
      
      <div class="cover-section">
        <input
          ref="imageFileInput"
          type="file"
          class="hidden"
          accept="image/*"
          :aria-label="t('agora', 'Select cover image')"
          @change="handleImageUpload"
          :disabled="!canEditGroup"
        />

        <div 
          v-if="currentCoverUrl" 
          class="cover-preview"
          @click="triggerImageUpload"
          :class="{ 'readonly': !canEditGroup }"
        >
          <img 
            :src="currentCoverUrl" 
            :alt="t('agora', 'Cover image')"
            class="cover-image"
          />
          <div v-if="canEditGroup" class="cover-overlay">
            <NcButton type="primary" class="change-cover-btn">
              <component :is="InquiryGeneralIcons.Edit" />
              {{ t('agora', 'Change') }}
            </NcButton>
            <NcButton 
              type="error" 
              class="remove-cover-btn"
              @click.stop="removeCoverImage"
            >
              {{ t('agora', 'Remove') }}
            </NcButton>
          </div>
        </div>

        <div 
          v-else
          class="cover-placeholder"
          @click="triggerImageUpload"
          :class="{ 'readonly': !canEditGroup }"
        >
          <div class="placeholder-content">
            <component :is="InquiryGeneralIcons.Image" class="placeholder-icon" />
            <NcButton 
              type="primary" 
              class="add-cover-btn"
              :disabled="!canEditGroup"
            >
              {{ t('agora', 'Add cover image') }}
            </NcButton>
            <p class="placeholder-text">{{ t('agora', 'Click to add a cover image') }}</p>
          </div>
        </div>
        
        <p class="cover-help">{{ t('agora', 'Recommended size: 1200×400 pixels') }}</p>
      </div>
    </div>

    <!-- Basic Information -->
    <div class="settings-section">
      <h3 class="section-title">
        <component :is="InquiryGeneralIcons.Title" />
        {{ t('agora', 'Basic Information') }}
      </h3>
      
      <div class="form-fields">
        <!-- Title Field - Full width -->
        <NcTextArea
          v-model="inquiryGroupStore.title"
          :label="t('agora', 'Title') + ' *'"
          :placeholder="t('agora', 'Enter group title')"
          :maxlength="200"
          :disabled="!canEditGroup || saving"
          :error="getFormFieldState('title').error"
          :success="getFormFieldState('title').success"
          :helper-text="getFormFieldState('title').helperText"
          class="form-input full-width"
          @keyup.enter="saveGroup"
        >
          <template #icon>
            <component :is="InquiryGeneralIcons.Text" />
          </template>
        </NcTextArea>
        
        <!-- Extended Title Field - Full width -->
        <NcTextArea
          v-model="inquiryGroupStore.titleExt"
          :label="t('agora', 'Extended Title')"
          :placeholder="t('agora', 'Optional extended title')"
          :maxlength="200"
          :disabled="!canEditGroup || saving"
          :error="getFormFieldState('titleExt').error"
          :success="getFormFieldState('titleExt').success"
          :helper-text="getFormFieldState('titleExt').helperText"
          class="form-input full-width"
          @keyup.enter="saveGroup"
        >
          <template #icon>
            <component :is="InquiryGeneralIcons.Text" />
          </template>
        </NcTextArea>
      </div>
    </div>

    <!-- Description -->
    <div class="settings-section">
      <h3 class="section-title">
        <component :is="InquiryGeneralIcons.Description" />
        {{ t('agora', 'Description') }}
      </h3>
      
      <div class="form-group">
        <NcTextArea
          v-model="inquiryGroupStore.description"
          :placeholder="t('agora', 'Enter group description...')"
          :rows="10"
          :disabled="!canEditGroup || saving"
          class="form-textarea large"
        />
        <div class="character-count">
          {{ inquiryGroupStore.description.length }}/2000
          <span v-if="formErrors.description" class="error-text">
            {{ formErrors.description }}
          </span>
        </div>
      </div>
    </div>

    <!-- Status & Settings -->
    <div class="settings-section">
      <h3 class="section-title">
        <component :is="InquiryGeneralIcons.Status" />
        {{ t('agora', 'Status & Settings') }}
      </h3>
      
      <div class="form-fields">
        <!-- Status Field -->
        <div class="form-group full-width">
          <label class="form-label">
            {{ t('agora', 'Status') }}
          </label>
          <NcSelect
            v-model="inquiryGroupStore.groupStatus"
            :options="statusOptions"
            :clearable="false"
            :disabled="!canEditGroup || saving"
            class="form-select"
          />
          <p class="form-help">{{ t('agora', 'Current status of the group') }}</p>
        </div>
        
        <!-- Expiration Field -->
        <div v-if="inquiryGroupStore.expire" class="form-group full-width">
          <label class="form-label">
            {{ t('agora', 'Expiration') }}
          </label>
          <div class="expiration-info">
            <p>{{ formatDate(inquiryGroupStore.expire) }}</p>
            <NcButton 
              type="secondary" 
              size="small"
              :disabled="!canEditGroup || saving"
              @click="inquiryGroupStore.expire = null"
            >
              {{ t('agora', 'Remove expiration') }}
            </NcButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="canEditGroup" class="settings-actions">
      <NcButton
        type="primary"
        :disabled="saving || !inquiryGroupStore.title.trim()"
        @click="saveGroup"
        class="save-btn"
      >
        <template #icon>
          <component :is="InquiryGeneralIcons.Save" />
        </template>
        {{ saving ? t('agora', 'Saving...') : t('agora', 'Save Changes') }}
      </NcButton>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>{{ t('agora', 'Loading group settings...') }}</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.inquiry-group-sidebar-settings {
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
}

.sidebar-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--color-border);
  
  h2 {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 4px;
  }
  
  .subtitle {
    font-size: 14px;
    color: var(--color-text-lighter);
    margin: 0 0 8px 0;
  }
  
  .readonly-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--color-warning-light);
    border: 1px solid var(--color-warning);
    border-radius: 8px;
    font-size: 13px;
    color: var(--color-warning-text);
    
    svg {
      color: var(--color-warning);
    }
  }
}

.settings-section {
  margin-bottom: 28px;
  
  .section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 16px;
    
    svg {
      color: var(--color-primary-element);
    }
  }
}

.cover-section {
  .cover-preview {
    position: relative;
    width: 100%;
    height: 150px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid var(--color-border);
    
    &:not(.readonly) {
      cursor: pointer;
      
      &:hover {
        .cover-overlay {
          opacity: 1;
        }
      }
    }
    
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
      background: rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      opacity: 0;
      transition: opacity 0.3s ease;
      
      .change-cover-btn,
      .remove-cover-btn {
        padding: 8px 16px;
        font-size: 12px;
      }
    }
  }
  
  .cover-placeholder {
    width: 100%;
    height: 100px;
    border: 2px dashed var(--color-border);
    border-radius: 12px;
    background: var(--color-background-dark);
    transition: all 0.2s ease;
    
    &:not(.readonly) {
      cursor: pointer;
      
      &:hover {
        border-color: var(--color-primary);
        background: var(--color-background-hover);
        
        .placeholder-icon {
          color: var(--color-primary);
        }
      }
    }
    
    .placeholder-content {
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      
      .placeholder-icon {
        color: var(--color-text-lighter);
        margin-bottom: 12px;
        transition: color 0.2s ease;
      }
      
      .add-cover-btn {
        margin-bottom: 8px;
      }
      
      .placeholder-text {
        font-size: 12px;
        color: var(--color-text-lighter);
        margin: 0;
      }
    }
  }
  
  .cover-help {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 8px;
    margin-bottom: 0;
    font-style: italic;
  }
}

.form-fields {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

:deep(.form-input) {
  width: 100%;
  
  &.full-width {
    margin-bottom: 8px;
  }
  
  .input-field__input-wrapper {
    .input-field__icon {
      color: var(--color-primary-element);
    }
    
    .input-field__input {
      &:disabled {
        background: var(--color-background-dark);
        cursor: not-allowed;
      }
    }
    
    &.input-field__input-wrapper--error {
      .input-field__icon {
        color: var(--color-error);
      }
    }
    
    &.input-field__input-wrapper--success {
      .input-field__icon {
        color: var(--color-success);
      }
    }
  }
}

.form-group {
  &.full-width {
    width: 100%;
  }
  
  .form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--color-main-text);
    margin-bottom: 8px;
  }
  
  .form-textarea {
    width: 100%;
    
    &.large {
      min-height: 260px;
    }
    
    &:disabled {
      background: var(--color-background-dark);
      cursor: not-allowed;
    }
  }
  
  .form-help {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 6px;
    margin-bottom: 0;
  }
  
  .character-count {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 6px;
    text-align: right;
    
    .error-text {
      color: var(--color-error);
      margin-left: 8px;
    }
  }
}

.expiration-info {
  padding: 12px;
  background: var(--color-warning-light);
  border: 1px solid var(--color-warning);
  border-radius: 8px;
  
  p {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: var(--color-main-text);
  }
}

.form-select {
  width: 100%;
  
  :deep(.multiselect) {
    &[disabled] {
      opacity: 0.5;
      cursor: not-allowed;
      
      .multiselect__tags {
        background: var(--color-background-dark);
      }
    }
  }
}

.settings-actions {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 2px solid var(--color-border);
  
  .save-btn {
    width: 100%;
  }
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  
  .loading-content {
    text-align: center;
    
    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 3px solid var(--color-border);
      border-top-color: var(--color-primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 16px;
    }
    
    p {
      font-size: 14px;
      color: var(--color-main-text);
      margin: 0;
    }
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.hidden {
  display: none;
}
</style>
