<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { ref, computed, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { useAppSettingsStore } from '../../../stores/appSettings.ts'

const appSettingsStore = useAppSettingsStore()

const editingType = ref(null)
const newType = ref({
  inquiry_type: '',
  label: '',
  family: '',
  icon: '',
  description: '',
  is_option: false,
  fields: '[]',
  allowed_response: '[]',
  allowed_transformation: '[]'
})


const availableIcons = [
  { id: 'vote', label: t('agora', 'Vote') },
  { id: 'comment', label: t('agora', 'Comment') },
  { id: 'poll', label: t('agora', 'Poll') },
  { id: 'survey', label: t('agora', 'Survey') },
  { id: 'discussion', label: t('agora', 'Discussion') },
]

const familyOptions = computed(() => 
  appSettingsStore.inquiryFamilyTab.map(family => ({
    id: family.family_type,
    label: family.label
  }))
)

const addType = async () => {
  if (!newType.value.inquiry_type || !newType.value.label || !newType.value.family) return
  
  await appSettingsStore.addType({
    ...newType.value,
    created: Date.now(),
    fields: JSON.parse(newType.value.fields || '[]'),
    allowed_response: JSON.parse(newType.value.allowed_response || '[]'),
    allowed_transformation: JSON.parse(newType.value.allowed_transformation || '[]')
  })
  
  // Reset form
  newType.value = {
    inquiry_type: '',
    label: '',
    family: '',
    icon: '',
    description: '',
    is_option: false,
    fields: '[]',
    allowed_response: '[]',
    allowed_transformation: '[]'
  }
}

const updateType = async (type) => {
  await appSettingsStore.updateType(type.id, {
    ...type,
    fields: JSON.parse(type.fields || '[]'),
    allowed_response: JSON.parse(type.allowed_response || '[]'),
    allowed_transformation: JSON.parse(type.allowed_transformation || '[]')
  })
  editingType.value = null
}

const deleteType = async (typeId) => {
  if (confirm(t('agora', 'Are you sure you want to delete this inquiry type?'))) {
    await appSettingsStore.deleteType(typeId)
  }
}
</script>

<template>
  <div class="inquiry-types">
    <h2>{{ t('agora', 'Inquiry Types') }}</h2>
    <p class="description">
      {{ t('agora', 'Manage different types of inquiries and their configurations') }}
    </p>

    <!-- Liste des types existants -->
    <div class="types-list">
      <div v-for="type in appSettingsStore.inquiryTypeTab" :key="type.id" class="type-item">
        <div class="type-content">
          <div class="type-icon">
            <span class="icon">{{ type.icon }}</span>
          </div>
          <div class="type-info">
            <h4>{{ type.label }}</h4>
            <p class="type-key">{{ type.inquiry_type }}</p>
            <p class="type-family">
              {{ t('agora', 'Family:') }} 
              {{ appSettingsStore.inquiryFamilyTab.find(f => f.family_type === type.family)?.label || type.family }}
            </p>
            <p v-if="type.description" class="type-description">
              {{ type.description }}
            </p>
            <div v-if="type.is_option" class="type-badge option">
              {{ t('agora', 'Option') }}
            </div>
          </div>
        </div>
        <div class="type-actions">
          <NcButton @click="editingType = { ...type, fields: JSON.stringify(type.fields || []), allowed_response: JSON.stringify(type.allowed_response || []), allowed_transformation: JSON.stringify(type.allowed_transformation || []) }">
            {{ t('agora', 'Edit') }}
          </NcButton>
          <NcButton @click="deleteType(type.id)">
            {{ t('agora', 'Delete') }}
          </NcButton>
        </div>
      </div>
    </div>

    <!-- Formulaire d'ajout -->
    <div class="add-type-form">
      <h3>{{ t('agora', 'Add New Inquiry Type') }}</h3>
      <div class="form-grid">
        <NcInputField
          v-model="newType.inquiry_type"
          :label="t('agora', 'Type Key')"
          :placeholder="t('agora', 'e.g., petition, survey, poll')"
          required
        />
        
        <NcInputField
          v-model="newType.label"
          :label="t('agora', 'Display Label')"
          :placeholder="t('agora', 'e.g., Public Petition, Survey')"
          required
        />
        
        <NcSelect
          v-model="newType.family"
          :options="familyOptions"
          :label="t('agora', 'Family')"
          required
        />
        
        <NcSelect
          v-model="newType.icon"
          :options="availableIcons"
          :label="t('agora', 'Icon')"
        />
        
        <NcInputField
          v-model="newType.description"
          :label="t('agora', 'Description')"
          type="textarea"
        />
        
        <div class="checkbox-field">
          <NcCheckboxRadioSwitch v-model="newType.is_option" type="switch">
            {{ t('agora', 'Is Option Type') }}
          </NcCheckboxRadioSwitch>
          <p class="field-description">
            {{ t('agora', 'Option types are used as responses or transformations for other types') }}
          </p>
        </div>

        <NcInputField
          v-model="newType.fields"
          :label="t('agora', 'Fields Configuration (JSON)')"
          type="textarea"
          :placeholder='`e.g., ["title", "description", "deadline"]`'
        />
        
        <NcInputField
          v-model="newType.allowed_response"
          :label="t('agora', 'Allowed Responses (JSON)')"
          type="textarea"
          :placeholder='`e.g., ["vote_yes_no", "comment"]`'
        />
        
        <NcInputField
          v-model="newType.allowed_transformation"
          :label="t('agora', 'Allowed Transformations (JSON)')"
          type="textarea"
          :placeholder='`e.g., ["official_proposal"]`'
        />
        
        <NcButton 
          type="primary" 
          :disabled="!newType.inquiry_type || !newType.label || !newType.family"
          @click="addType"
        >
          {{ t('agora', 'Add Type') }}
        </NcButton>
      </div>
    </div>

    <!-- Modal d'édition -->
    <div v-if="editingType" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ t('agora', 'Edit Inquiry Type') }}</h3>
        <div class="form-grid">
          <NcInputField
            v-model="editingType.inquiry_type"
            :label="t('agora', 'Type Key')"
            required
          />
          <NcInputField
            v-model="editingType.label"
            :label="t('agora', 'Display Label')"
            required
          />
          <NcSelect
            v-model="editingType.family"
            :options="familyOptions"
            :label="t('agora', 'Family')"
            required
          />
          <NcSelect
            v-model="editingType.icon"
            :options="availableIcons"
            :label="t('agora', 'Icon')"
          />
          <NcInputField
            v-model="editingType.description"
            :label="t('agora', 'Description')"
            type="textarea"
          />
          <div class="checkbox-field">
            <NcCheckboxRadioSwitch v-model="editingType.is_option" type="switch">
              {{ t('agora', 'Is Option Type') }}
            </NcCheckboxRadioSwitch>
          </div>
          <NcInputField
            v-model="editingType.fields"
            :label="t('agora', 'Fields Configuration (JSON)')"
            type="textarea"
          />
          <NcInputField
            v-model="editingType.allowed_response"
            :label="t('agora', 'Allowed Responses (JSON)')"
            type="textarea"
          />
          <NcInputField
            v-model="editingType.allowed_transformation"
            :label="t('agora', 'Allowed Transformations (JSON)')"
            type="textarea"
          />
          <div class="modal-actions">
            <NcButton @click="editingType = null">
              {{ t('agora', 'Cancel') }}
            </NcButton>
            <NcButton 
              type="primary" 
              @click="updateType(editingType)"
            >
              {{ t('agora', 'Save') }}
            </NcButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.inquiry-types {
  padding: 20px;
}

.types-list {
  margin-bottom: 30px;
}

.type-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  margin-bottom: 10px;
  background: var(--color-background-dark);
  border-radius: 8px;
}

.type-content {
  display: flex;
  align-items: center;
  gap: 15px;
  flex: 1;
}

.type-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary);
  color: white;
  border-radius: 8px;
  font-size: 20px;
}

.type-info h4 {
  margin: 0 0 5px 0;
}

.type-key {
  margin: 0;
  font-family: monospace;
  color: var(--color-text-lighter);
  font-size: 0.9em;
}

.type-family {
  margin: 5px 0;
  color: var(--color-text-lighter);
  font-size: 0.9em;
}

.type-description {
  margin: 5px 0 0 0;
  color: var(--color-text-lighter);
}

.type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: bold;
  margin-top: 5px;
}

.type-badge.option {
  background: var(--color-warning);
  color: white;
}

.type-actions {
  display: flex;
  gap: 10px;
}

.add-type-form {
  padding: 20px;
  background: var(--color-background-dark);
  border-radius: 8px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.checkbox-field {
  grid-column: span 2;
}

.field-description {
  margin: 5px 0 0 0;
  font-size: 0.9em;
  color: var(--color-text-lighter);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: var(--color-main-background);
  padding: 30px;
  border-radius: 12px;
  width: 600px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-actions {
  grid-column: span 2;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
</style>
