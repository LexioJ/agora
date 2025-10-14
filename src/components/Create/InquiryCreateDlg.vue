<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud Contributors
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'

import { ConfigBox, RadioGroupDiv, InputDiv } from '../Base/index.ts'
import { InquiryGeneralIcons } from '../../utils/icons.ts'

import { useInquiryStore } from '../../stores/inquiry.ts'
import { useSessionStore } from '../../stores/session.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'
import {
  getAvailableInquiryTypesForCreation,
  getInquiryTypeOptions,
  getInquiryTypeData,
  type InquiryType
} from '../../helpers/modules/InquiryHelper.ts'

// Define props
interface Props {
  inquiryType?: InquiryType | null
  selectedGroups?: string[]
  availableGroups?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  inquiryType: null,
  selectedGroups: () => [],
  availableGroups: () => []
})

const selectedGroup = ref<string | null>(null)

const emit = defineEmits<{
  (e: 'close'): void
  (
    e: 'added',
    inquiry: {
      id: number
      title: string
    }
  ): void
  (e: 'update:selected-groups', groups: string[]): void
}>()

const inquiryStore = useInquiryStore()
const sessionStore = useSessionStore()

const inquiryTitle = ref('')
const inquiryId = ref<number | null>(null)
const adding = ref(false)
const accessType = ref<'user' | 'groups'>('user') // Default to user

// Get inquiry types from app settings
const inquiryTypes = computed(() => {
  return sessionStore.appSettings.inquiryTypeTab || []
})

// Filter out official and suggestion types for creation
const availableInquiryTypes = computed(() => {
  return getAvailableInquiryTypesForCreation(inquiryTypes.value)
})

// Inquiry type options for radio group
const inquiryTypeOptions = computed(() => {
  return getInquiryTypeOptions(availableInquiryTypes.value)
})

// Selected inquiry type
const inquiryType = ref(availableInquiryTypes.value[0]?.inquiry_type || '')

// Get current inquiry type data (icon, label, description)
const currentInquiryTypeData = computed(() => {
  return getInquiryTypeData(inquiryType.value, inquiryTypes.value)
})

// Get selected inquiry type data from props
const selectedInquiryTypeData = computed(() => {
  if (!props.inquiryType) return null
  return getInquiryTypeData(props.inquiryType.inquiry_type, inquiryTypes.value)
})

// Check if a group is selected
const isGroupSelected = (group: string) => {
  return selectedGroup.value === group
}

const selectGroup = (group: string) => {
  selectedGroup.value = group
  emit('update:selected-groups', group ? [group] : [])
}

// Watch to pre-fill type when prop changes
watch(() => props.inquiryType, (newType) => {
  if (newType && newType.inquiry_type) {
    console.log('Pre-filling inquiry type:', newType.inquiry_type)
    inquiryType.value = newType.inquiry_type
  }
}, { immediate: true })

const titleIsEmpty = computed(() => inquiryTitle.value === '')
const disableAddButton = computed(() => titleIsEmpty.value || adding.value)

async function addInquiry() {
  try {
    adding.value = true
    
    // Prepare inquiry data
    const inquiryData: any = {
      type: inquiryType.value,
      title: inquiryTitle.value,
    }

    // Add groups if groups access is selected
    if (accessType.value === 'groups' && selectedGroup.value) {
      inquiryData.ownedGroup = selectedGroup.value
    }
    console.log(" ADD GROUPPPPPS :", selectedGroup.value)
    console.log(" ADD TYPE :", inquiryType.value)
    console.log(" ADD TITLE :", inquiryTitle.value)
    
    // Add the inquiry
    const inquiry = await inquiryStore.add(inquiryData)

    if (inquiry) {
      inquiryId.value = inquiry.id

      showSuccess(
        t('agora', '"{inquiryTitle}" has been added', {
          inquiryTitle: inquiry.title,
        })
      )
      emit('added', {
        id: inquiry.id,
        title: inquiry.title,
      })
      resetInquiry()
    }
  } catch {
    showError(
      t('agora', 'Error while creating Inquiry "{inquiryTitle}"', {
        inquiryTitle: inquiryTitle.value,
      })
    )
  } finally {
    adding.value = false
  }
}

function resetInquiry() {
  inquiryId.value = null
  inquiryTitle.value = ''
  accessType.value = 'user'
  selectedGroup.value = null
  emit('update:selected-groups', [])
}
</script>

<template>
  <!-- Overlay pour le fond flou -->
  <div class="dialog-overlay" @click="emit('close')">
    <!-- Dialog container -->
    <div class="create-dialog" @click.stop>
      <!-- Access Configuration -->
      <ConfigBox
        :name="t('agora', 'Access Settings')"
        v-if="availableGroups.length > 0"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.accountgroup" />
        </template>
        <div class="access-settings">
          <NcRadioGroup
            v-model="accessType"
            class="access-radio-group"
            :description="t('agora', 'Choose who is opening this inquiry')"
          >
            <NcCheckboxRadioSwitch value="user">
              {{ t('agora', 'Only me (personal inquiry)') }}
            </NcCheckboxRadioSwitch>

            <NcCheckboxRadioSwitch value="groups">
              {{ t('agora', 'Open with this group') }}
            </NcCheckboxRadioSwitch>
          </NcRadioGroup>

          <!-- Group Selection -->
          <div v-if="accessType === 'groups'" class="groups-selection">
            <h4 class="groups-title">
              {{ t('agora', 'Select groups') }}
            </h4>
            <div class="groups-list">
              <NcRadioGroup
                v-model="selectedGroup"
                @update:value="selectGroup"
                :description="t('agora', 'Choose which of your groups can access this inquiry')"
              >
                <div
                  v-for="group in availableGroups"
                  :key="group"
                  class="group-item"
                >
                  <NcCheckboxRadioSwitch
                    :value="group"
                    type="radio"
                    name="group-selection"
                  >
                    {{ group }}
                  </NcCheckboxRadioSwitch>
                </div>
              </NcRadioGroup>
            </div>
          </div>
        </div>
      </ConfigBox>

      <!-- Title -->
      <ConfigBox :name="t('agora', 'Title')">
        <template #icon>
          <Component :is="InquiryGeneralIcons.bullhorn" />
        </template>
        <InputDiv
          v-model="inquiryTitle"
          focus
          type="text"
          :placeholder="t('agora', 'Enter title')"
          :helper-text="t('agora', 'Choose a meaningful title for your inquiry')"
          :label="t('agora', 'Enter title')"
          @submit="addInquiry"
        />
      </ConfigBox>

      <!-- Inquiry Type -->
      <ConfigBox
        :name="t('agora', 'Inquiry type')"
        :label="t('agora', 'Inquiry type')"
        v-if="!props.inquiryType"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.check" />
        </template>
        <RadioGroupDiv v-model="inquiryType" :options="inquiryTypeOptions" />
      </ConfigBox>

      <!-- Selected Type Display -->
      <ConfigBox
        v-else
        :name="t('agora', 'Inquiry type')"
        :label="t('agora', 'Inquiry type')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.check" />
        </template>
        <div class="selected-type">
          <strong>{{ selectedInquiryTypeData?.label }}</strong>
          <p class="type-description" v-if="selectedInquiryTypeData?.description">
            {{ selectedInquiryTypeData.description }}
          </p>
        </div>
      </ConfigBox>

      <!-- Buttons -->
      <div class="create-buttons">
        <NcButton @click="emit('close')">
          <template #default>
            {{ t('agora', 'Cancel') }}
          </template>
        </NcButton>
        <NcButton
          :disabled="disableAddButton"
          :variant="'primary'"
          @click="addInquiry"
        >
          <template #default>
            {{ adding ? t('agora', 'Creating...') : t('agora', 'Create Inquiry') }}
          </template>
        </NcButton>
      </div>
    </div>
  </div>
</template>

<style lang="css" scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.create-dialog {
  background-color: var(--color-main-background);
  padding: 20px;
  max-width: 400px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  margin: 20px;
}

.create-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.selected-type {
  padding: 8px 0;
}

.type-description {
  color: var(--color-text-lighter);
  font-size: 0.9em;
  margin-top: 4px;
}

.access-settings {
  padding: 8px 0;
}

.access-description {
  color: var(--color-text-lighter);
  margin-bottom: 16px;
  font-size: 0.95em;
}

.access-radio-group {
  margin-bottom: 16px;
}

.groups-selection {
  margin-top: 16px;
  padding: 16px;
  background: var(--color-background-dark);
  border-radius: 8px;
}

.groups-title {
  margin: 0 0 8px 0;
  font-size: 1em;
  font-weight: 600;
}

.groups-description {
  color: var(--color-text-lighter);
  font-size: 0.9em;
  margin: 0 0 12px 0;
}

.groups-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.group-item {
  display: flex;
  align-items: center;
  padding: 4px 0;
}
</style>
