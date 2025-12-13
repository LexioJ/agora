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
import type { InquiryGroupType } from '../stores/inquiryGroups.types.ts'

import { ConfigBox, RadioGroupDiv, InputDiv } from '../Base/index.ts'
import { InquiryGeneralIcons } from '../../utils/icons.ts'

import { useInquiryGroupStore } from '../../stores/inquiryGroup.ts'
import { useSessionStore } from '../../stores/session.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'
import {
  getAvailableInquiryGroupTypesForCreation,
  getInquiryGroupTypeOptions,
  getInquiryGroupTypeData,
} from '../../helpers/modules/InquiryHelper.ts'

// Define props
interface Props {
  inquiryGroupType?: InquiryGroupType | null
  responseType?: string | null
  selectedMode?: string
  availableGroups?: string[]
  parentInquiryId?: string | number | null
  defaultTitle?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  inquiryGroupType: null,
  responseType: null,
  selectedMode: null,
  availableGroups: () => [],
  parentInquiryId: null,
  defaultTitle: null
})


const emit = defineEmits<{
  (e: 'close'): void
  (e: 'added', inquiry: { id: number; title: string }): void
  (e: 'update:selected-groups', groups: string[]): void
}>()

const inquiryGroupStore = useInquiryGroupStore()
const sessionStore = useSessionStore()

const inquiryTitle = ref('')
const inquiryId = ref<number | null>(null)
const adding = ref(false)
const accessType = ref<'user' | 'groups'>('user')
const selectedGroup = ref<string | null>(null)

// Get inquiry types from app settings
const inquiryGroupTypes = computed((): InquiryGroupType[] => {
  const types = sessionStore.appSettings.inquiryGroupTypeTab || []
  return types.filter(type => type.is_root === true)
})

// Filter out official and suggestion types for creation
const availableInquiryGroupTypes = computed(() => getAvailableInquiryGroupTypesForCreation(inquiryGroupTypes.value))

// Inquiry type options for radio group
const inquiryGroupTypeOptions = computed(() => getInquiryGroupTypeOptions(availableInquiryGroupTypes.value))

// Selected inquiry type (for selector display)
const localInquiryGroupType = ref(availableInquiryGroupTypes.value[0]?.inquiry_type || '')

// Final selected type (priority to props)
const selectedType = computed(() => {
  if (props.inquiryGroupType) {
    return props.inquiryGroupType.group_type
  }
  if (props.responseType) {
    return props.responseType
  }
  return localInquiryGroupType.value
})

// Data for display
const currentInquiryGroupTypeData = computed(() => getInquiryGroupTypeData(selectedType.value, inquiryGroupTypes.value))

// Check if type is predefined (don't show selector)
const hasPredefinedType = computed(() => !!(props.inquiryGroupType || props.responseType))

const selectGroup = (group: string | null) => {
  selectedGroup.value = group
  emit('update:selected-groups', group ? [group] : [])
}

// Update local inquiry type
const updateLocalInquiryGroupType = (newType: string) => {
  localInquiryGroupType.value = newType
}

// Watch to pre-fill type when prop changes
watch(() => props.inquiryGroupType, (newType) => {
  if (newType && newType.inquiry_type) {
    localInquiryGroupType.value = newType.inquiry_type
  }
}, { immediate: true })

// Watch to pre-fill title
watch(() => props.defaultTitle, (newTitle) => {
  if (newTitle) {
    inquiryTitle.value = newTitle
  }
}, { immediate: true })

const titleIsEmpty = computed(() => inquiryTitle.value.trim() === '')
const disableAddButton = computed(() => titleIsEmpty.value || adding.value)

interface InquiryGroupData {
  type: string
  title: string
  parentId?: string | number | null
  ownedGroup?: string
  description?: string
}

async function addGroupInquiry() {
  try {
    adding.value = true
    // Prepare inquiry data with proper typing
    const inquiryData: InquiryGroupData = {
      type: selectedType.value,
      title: inquiryTitle.value.trim(),
    }

    if (props.parentInquiryId) {
      inquiryData.parentId = props.parentInquiryId
    }
    
    // Add groups if groups access is selected
    if (accessType.value === 'groups' && selectedGroup.value) {
      inquiryData.ownedGroup = selectedGroup.value
    }
   
    console.log(" BEFORE ADD ")
    // Add the inquiry
    const inquiry = await inquiryGroupStore.add(inquiryData)
    
    console.log(" AFTER ADD ",inquiry)

    if (inquiry) {
      inquiryId.value = inquiry.id
      showSuccess(
        t('agora', '"{inquiryTitle}" group has been added', {
          inquiryTitle: inquiry.title,
        })
      )
      emit('added', {
        id: inquiry.id,
        title: inquiry.title,
      })
      resetInquiry()
    }
  } catch  {
    showError(
      t('agora', 'Error while creating group Inquiry "{inquiryTitle}"', {
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
  <div class="dialog-overlay" @click="emit('close')">
    <!-- Dialog container -->
    <div class="create-dialog" @click.stop>
      <!-- Access Configuration -->
      <ConfigBox
        v-if="availableGroups.length > 0"
        :name="t('agora', 'Access Settings')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.AccountGroup" />
        </template>
        <div class="access-settings">
          <NcRadioGroup
            :model-value="accessType"
            class="access-radio-group"
            :description="t('agora', 'Choose who is opening this inquiry')"
            @update:model-value="accessType = $event"
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
                :model-value="selectedGroup"
                :description="t('agora', 'Choose which of your groups can access this inquiry')"
                @update:model-value="selectGroup($event)"
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
          <Component :is="InquiryGeneralIcons.Bullhorn" />
        </template>
        <InputDiv
          :model-value="inquiryTitle"
          focus
          type="text"
          :placeholder="t('agora', 'Enter title')"
          :helper-text="t('agora', 'Choose a meaningful title for your inquiry')"
          :label="t('agora', 'Enter title')"
          @update:model-value="inquiryTitle = $event"
          @submit="addGroupInquiry"
        />
      </ConfigBox>

      <!-- Inquiry Type Selector -->
      <ConfigBox
        v-if="!hasPredefinedType"
        :name="t('agora', 'Inquiry type')"
        :label="t('agora', 'Inquiry type')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.Check" />
        </template>
        <RadioGroupDiv
          :model-value="localInquiryGroupType"
          :options="inquiryGroupTypeOptions"
          @update:model-value="updateLocalInquiryGroupType($event)"
        />
      </ConfigBox>

      <!-- Selected Type Display -->
      <ConfigBox
        v-else
        :name="t('agora', 'Inquiry  group type')"
        :label="t('agora', 'Inquiry group type')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.Check" />
        </template>
        <div class="selected-type">
          <strong>{{ currentInquiryGroupTypeData?.label }}</strong>
          <p v-if="currentInquiryGroupTypeData?.description" class="type-description">
            {{ currentInquiryGroupTypeData.description }}
          </p>
        </div>
      </ConfigBox>

      <!-- Buttons -->
      <div class="create-buttons">
        <NcButton @click="emit('close')">
          {{ t('agora', 'Cancel') }}
        </NcButton>
        <NcButton
          :disabled="disableAddButton"
          :variant="'primary'"
          @click="addGroupInquiry"
        >
          {{ adding ? t('agora', 'Creating...') : t('agora', 'Create group Inquiry') }}
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
