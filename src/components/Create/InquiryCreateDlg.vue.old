<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud Contributors
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
import { InquiryTypesUI } from '../../helpers/modules/InquiryHelper.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'
import type { InquiryType } from '../../helpers/modules/InquiryHelper.ts'

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

const inquiryTitle = ref('')
const inquiryId = ref<number | null>(null)
const adding = ref(false)
const accessType = ref<'user' | 'groups'>('user') // Default to user

type InquiryTypeKey = keyof typeof InquiryTypesUI
const inquiryType = ref<InquiryTypeKey>('proposal')

// GROUP - Sélection unique
const selectedGroup = ref<string | null>(null)

// Check if a group is selected
const isGroupSelected = (group: string) => {
  return selectedGroup.value === group
}

const selectGroup = (group: string) => {
  selectedGroup.value = group
  // Émettre la sélection unique sous forme de tableau
  emit('update:selected-groups', group ? [group] : [])
}

// Watch to pre-fill type when prop changes
watch(() => props.inquiryType, (newType) => {
  if (newType && newType.inquiry_type) {
    console.log('Pre-filling inquiry type:', newType.inquiry_type)
    inquiryType.value = newType.inquiry_type as InquiryTypeKey
  }
}, { immediate: true })

const inquiryTypeOptions = Object.entries(InquiryTypesUI)
  .filter(([key]) => !['official', 'suggestion'].includes(key))
  .map(([key, value]) => ({
    value: key,
    label: value.label,
  }))

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
      inquiryData.accessGroups = [selectedGroup.value]
    }

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
  <div class="create-dialog">
    <!-- Access Configuration -->
    <ConfigBox
      :name="t('agora', 'Access Settings')"
      v-if="availableGroups.length > 0"
    >
      <template #icon>
        <Component :is="InquiryGeneralIcons.accountgroup" />
      </template>
      <div class="access-settings">
        <p class="access-description">
          {{ t('agora', 'Choose who can access this inquiry') }}
        </p>

        <NcRadioGroup
          v-model="accessType"
          class="access-radio-group"
          :description="t('agora', 'Select the access level for this inquiry')"
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
              :value="selectedGroup"
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
        <strong>{{ props.inquiryType?.label }}</strong>
        <p class="type-description" v-if="props.inquiryType?.description">
          {{ props.inquiryType.description }}
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
</template>

<style lang="css" scoped>
.create-dialog {
  background-color: var(--color-main-background);
  padding: 8px 20px;
  max-width: 600px;
  margin: 0 auto;
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
