<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { t } from '@nextcloud/l10n'
import { ref, computed, onMounted, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useAppSettingsStore } from '../../../stores/appSettings.js'

const props = defineProps(['selectedType'])
const emit = defineEmits(['back-to-types'])

const appSettingsStore = useAppSettingsStore()

const editorOptions = [
  { value: 'wysiwyg', label: t('agora', 'Rich Text Editor') },
  { value: 'textarea', label: t('agora', 'Simple Text Area') },
  { value: 'texteditor', label: t('agora', 'Nextcloud text editor') },
]

// Initialize rights for the selected type
watch(() => props.selectedType, (newType) => {
  if (newType && !appSettingsStore.inquiryTypeRights[newType.inquiry_type]) {
    appSettingsStore.initializeInquiryTypeRights(newType.inquiry_type)
  }
}, { immediate: true })
</script>

<template>
  <div class="type-rights">
    <div class="header">
      <NcButton @click="emit('back-to-types')">
        ← {{ t('agora', 'Back to Types') }}
      </NcButton>
      <h2>
        {{ t('agora', 'Rights for {type}', { type: selectedType?.label }) }}
      </h2>
    </div>

    <div v-if="selectedType" class="settings-container">
      <p class="description">
        {{ t('agora', 'Configure default rights and settings for this inquiry type') }}
      </p>

      <div class="settings-list">
        <div class="setting-item">
          <NcCheckboxRadioSwitch
            v-model="appSettingsStore.inquiryTypeRights[selectedType.inquiry_type].supportInquiry"
            type="switch"
            @update:model-value="appSettingsStore.write()"
          >
            {{ t('agora', 'Allow support') }}
          </NcCheckboxRadioSwitch>
          <p class="setting-description">
            {{ t('agora', 'Allow users to support this inquiry type') }}
          </p>
        </div>

        <div class="setting-item">
          <NcCheckboxRadioSwitch
            v-model="appSettingsStore.inquiryTypeRights[selectedType.inquiry_type].commentInquiry"
            type="switch"
            @update:model-value="appSettingsStore.write()"
          >
            {{ t('agora', 'Allow comments') }}
          </NcCheckboxRadioSwitch>
          <p class="setting-description">
            {{ t('agora', 'Allow users to comment on this inquiry type') }}
          </p>
        </div>

        <div class="setting-item">
          <NcCheckboxRadioSwitch
            v-model="appSettingsStore.inquiryTypeRights[selectedType.inquiry_type].attachFileInquiry"
            type="switch"
            @update:model-value="appSettingsStore.write()"
          >
            {{ t('agora', 'Allow file attachments') }}
          </NcCheckboxRadioSwitch>
          <p class="setting-description">
            {{ t('agora', 'Allow users to attach files to this inquiry type') }}
          </p>
        </div>

        <div class="setting-item">
          <label for="editor-type-select">{{ t('agora', 'Editor type:') }}</label>
          <NcSelect
            id="editor-type-select"
            v-model="appSettingsStore.inquiryTypeRights[selectedType.inquiry_type].editorType"
            :options="editorOptions"
            option-value="value"
            option-label="label"
            class="editor-select"
            @update:model-value="appSettingsStore.write()"
          />
          <p class="setting-description">
            {{ t('agora', 'Select the editor type for this inquiry') }}
          </p>
        </div>
      </div>
    </div>

    <div v-else class="no-selection">
      <p>{{ t('agora', 'No type selected') }}</p>
    </div>
  </div>
</template>

<style scoped>
.type-rights {
  padding: 20px;
}

.header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
}

.header h2 {
  margin: 0;
  color: var(--color-text-light);
}

.description {
  color: var(--color-text-lighter);
  margin-bottom: 25px;
}

.settings-container {
  padding: 20px;
  background-color: var(--color-background-dark);
  border-radius: 8px;
}

.settings-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.setting-item {
  padding: 15px;
  background-color: var(--color-background-darker);
  border-radius: 8px;
}

.setting-item label {
  display: block;
  margin-bottom: 8px;
  font-weight: bold;
}

.editor-select {
  max-width: 250px;
  margin-top: 8px;
}

.setting-description {
  margin: 8px 0 0 0;
  font-size: 0.9em;
  color: var(--color-text-lighter);
  padding-left: 36px;
}

.no-selection {
  text-align: center;
  padding: 40px;
  color: var(--color-text-lighter);
}
</style>
