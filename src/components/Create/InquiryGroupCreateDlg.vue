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
  getAllowedResponseGroupTypes,
} from '../../helpers/modules/InquiryHelper.ts'

// Define props
interface Props {
  inquiryGroupType?: InquiryGroupType | null
  parentGroupId?: string | number | null
  availableGroups?: string[]
  defaultTitle?: string | null
  isSubGroup?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  inquiryGroupType: null,
  parentGroupId: null,
  availableGroups: () => [],
  defaultTitle: null,
  isSubGroup: false
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
const selectedNextcloudGroup = ref<string | null>(null)

const localInquiryGroupType = ref<string>('')

// Get all inquiry types from app settings
const allInquiryGroupTypes = computed((): InquiryGroupType[] => {
  const types = sessionStore.appSettings.inquiryGroupTypeTab || []
  return types
})

const allowedResponseGroupTypes = computed(() => {
  if (props.isSubGroup && props.parentGroupId && !props.inquiryGroupType) {
    const parentGroupType = '' 
    
    if (parentGroupType) {
      return getAllowedResponseGroupTypes(allInquiryGroupTypes.value, parentGroupType)
    }
  }
  return []
})

const availableInquiryGroupTypes = computed(() => {
  if (props.inquiryGroupType && !props.parentGroupId && !props.isSubGroup) {
    return [props.inquiryGroupType]
  }
  
  if (props.inquiryGroupType && props.parentGroupId && !props.isSubGroup) {
    return [props.inquiryGroupType]
  }
  
  if (props.parentGroupId && props.isSubGroup && !props.inquiryGroupType) {
    if (allowedResponseGroupTypes.value.length > 0) {
      return getAvailableInquiryGroupTypesForCreation(allowedResponseGroupTypes.value)
    }
    const rootTypes = allInquiryGroupTypes.value.filter(type => type.is_root === true)
    return getAvailableInquiryGroupTypesForCreation(rootTypes)
  }
  
  const rootTypes = allInquiryGroupTypes.value.filter(type => type.is_root === true)
  return getAvailableInquiryGroupTypesForCreation(rootTypes)
})

const inquiryGroupTypeOptions = computed(() => 
  getInquiryGroupTypeOptions(availableInquiryGroupTypes.value)
)

const selectedInquiryGroupType = computed(() => {
  console.log(" PROPS GROUP TYPE ",props.inquiryGroupType)
  console.log(" localInq GROUP TYPE ",localInquiryGroupType)
  console.log(" Available GROUP TYPE ",availableInquiryGroupTypes.value[0]?.group_type)

  if (props.inquiryGroupType) {
    return props.inquiryGroupType
  }
  
  return localInquiryGroupType.value || availableInquiryGroupTypes.value[0]?.group_type
})

// Data for display
const currentInquiryGroupTypeData = computed(() => 
  getInquiryGroupTypeData(selectedInquiryGroupType.value, allInquiryGroupTypes.value)
)

const creationMode = computed(() => {
  console.log(" PROPS INQUIRY GORUOP",props.inquiryGroupType)
  console.log(" PARENT.ID",props?.parentGroupId)
  console.log("IS SUB GROUP",props?.isSubGroup)

  if (props.inquiryGroupType && !props.parentGroupId && !props.isSubGroup) {
    return 'direct-creation'
  }
  
  if (props.inquiryGroupType && props.parentGroupId && !props.isSubGroup) {
    return 'child-creation'
  }
  
  if (props.parentGroupId && props.isSubGroup) {
    return 'subgroup-creation'
  }
  
  return 'free-creation'
})

const showInquiryGroupTypeSelector = computed(() => {
  return creationMode.value === 'subgroup-creation' && availableInquiryGroupTypes.value.length > 1
})

const dialogTitle = computed(() => {
  switch (creationMode.value) {
    case 'direct-creation':
      return t('agora', 'Create {type} Group', { 
        type: props.inquiryGroupType?.label || '' 
      })
    case 'child-creation':
      return t('agora', 'Create {type} Child Group', {
        type: props.inquiryGroupType?.label || ''
      })
    case 'subgroup-creation':
      return t('agora', 'Add sub group')
    default:
      return t('agora', 'Create New Group')
  }
})

const contextDescription = computed(() => {
  switch (creationMode.value) {
    case 'direct-creation':
      return t('agora', 'Creating a new {type} group', {
        type: props.inquiryGroupType?.label || ''
      })
    case 'child-creation':
      return t('agora', 'Creating a {type} group as a child of the parent group', {
        type: props.inquiryGroupType?.label || ''
      })
    case 'subgroup-creation':
      return t('agora', 'Add an allowed response group to the parent group')
    default:
      return t('agora', 'Create a new inquiry group')
  }
})

const selectNextcloudGroup = (group: string | null) => {
  selectedNextcloudGroup.value = group
  emit('update:selected-groups', group ? [group] : [])
}

// Update local inquiry type
const updateLocalInquiryGroupType = (newType: string) => {
  localInquiryGroupType.value = newType
}

// Watch to pre-fill title
watch(() => props.defaultTitle, (newTitle) => {
  if (newTitle) {
    inquiryTitle.value = newTitle
  }
}, { immediate: true })

// Watch available inquiry group types
watch(availableInquiryGroupTypes, (newTypes) => {
  if (newTypes.length > 0 && !localInquiryGroupType.value) {
    localInquiryGroupType.value = newTypes[0].group_type
  }
}, { immediate: true })

const titleIsEmpty = computed(() => inquiryTitle.value.trim() === '')
const disableAddButton = computed(() => titleIsEmpty.value || adding.value)

interface InquiryGroupData {
  type: string
  title: string
  parentId?:  number | null
  ownedGroup?: string
  description?: string
}

async function addGroupInquiry() {
  try {
    adding.value = true
    
    // Validate required fields
    if (!selectedInquiryGroupType.value) {
      showError(t('agora', 'Please select a group type'))
      return
    }
    
    if (titleIsEmpty.value) {
      showError(t('agora', 'Please enter a title'))
      return
    }
    let typeSelected = selectedInquiryGroupType

    console.log("SLECT INQUIRY GROUP TYPE:", selectedInquiryGroupType.value)
    console.log("LOCAL SLECT INQUIRY GROUP TYPE:", localInquiryGroupType.value)
    if (localInquiryGroupType.value) typeSelected = localInquiryGroupType.value
    
    console.log("APRES SLECT INQUIRY GROUP TYPE:", selectedInquiryGroupType.value)
    // Prepare inquiry data
    const inquiryData: InquiryGroupData = {
      type: typeSelected,
      title: inquiryTitle.value.trim(),
    }

    // Add parent ID if provided
    if (props.parentGroupId) {
      inquiryData.parentId = props.parentGroupId
    }
    
    // Add Nextcloud groups if groups access is selected
    if (accessType.value === 'groups' && selectedNextcloudGroup.value) {
      inquiryData.ownedGroup = selectedNextcloudGroup.value
    }
   
    console.log("Creating inquiry group with data:", inquiryData)
    console.log("Creation mode:", creationMode.value)
    
    // Add the inquiry group
    const inquiry = await inquiryGroupStore.add(inquiryData)
    
    console.log("Inquiry group created:", inquiry)

    if (inquiry) {
      inquiryId.value = inquiry.id
      
      let successMessage = t('agora', '"{inquiryTitle}" group has been created', {
        inquiryTitle: inquiry.title,
      })
      
      if (creationMode.value === 'subgroup-creation') {
        successMessage = t('agora', 'Allowed response "{inquiryTitle}" has been added', {
          inquiryTitle: inquiry.title,
        })
      } else if (creationMode.value === 'child-creation') {
        successMessage = t('agora', 'Child group "{inquiryTitle}" has been created', {
          inquiryTitle: inquiry.title,
        })
      }
      
      showSuccess(successMessage)
      
      emit('added', {
        id: inquiry.id,
        title: inquiry.title,
      })
      resetInquiry()
    }
  } catch (error) {
    console.error('Error creating inquiry group:', error)
    showError(
      t('agora', 'Error while creating group "{inquiryTitle}"', {
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
  selectedNextcloudGroup.value = null
  emit('update:selected-groups', [])
}
</script>

<template>
  <div class="dialog-overlay" @click="emit('close')">
    <!-- Dialog container -->
    <div class="create-dialog" @click.stop>
      <!-- Dialog Header -->
      <div class="dialog-header">
        <h3>{{ dialogTitle }}</h3>
        <div v-if="creationMode === 'subgroup-creation'" class="mode-badge response">
          {{ t('agora', 'SubGroup') }}
        </div>
        <div v-else-if="creationMode === 'child-creation'" class="mode-badge child">
          {{ t('agora', 'Child Group') }}
        </div>
        <div v-else-if="creationMode === 'direct-creation'" class="mode-badge creation">
          {{ t('agora', 'New Group') }}
        </div>
      </div>

      <!-- Context description -->
      <div class="context-description">
        <p>{{ contextDescription }}</p>
      </div>

      <!-- Access Configuration - Groups Nextcloud -->
      <ConfigBox
        v-if="props.availableGroups && props.availableGroups.length > 0"
        :name="t('agora', 'Access Settings')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.AccountGroup" />
        </template>
        <div class="access-settings">
          <NcRadioGroup
            :model-value="accessType"
            class="access-radio-group"
            :description="t('agora', 'Choose who can access this group')"
            @update:model-value="accessType = $event"
          >
            <NcCheckboxRadioSwitch value="user">
              {{ t('agora', 'Only me (personal group)') }}
            </NcCheckboxRadioSwitch>

            <NcCheckboxRadioSwitch value="groups">
              {{ t('agora', 'Share with Nextcloud group') }}
            </NcCheckboxRadioSwitch>
          </NcRadioGroup>

          <!-- Nextcloud Group Selection -->
          <div v-if="accessType === 'groups'" class="nextcloud-groups-selection">
            <h4 class="groups-title">
              {{ t('agora', 'Select Nextcloud group') }}
            </h4>
            <div class="groups-list">
              <NcRadioGroup
                :model-value="selectedNextcloudGroup"
                :description="t('agora', 'Choose which Nextcloud group can access this inquiry group')"
                @update:model-value="selectNextcloudGroup($event)"
              >
                <div
                  v-for="group in props.availableGroups"
                  :key="group"
                  class="group-item"
                >
                  <NcCheckboxRadioSwitch
                    :value="group"
                    type="radio"
                    name="nextcloud-group-selection"
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
          :placeholder="t('agora', 'Enter group title')"
          :helper-text="t('agora', 'Choose a meaningful title for your group')"
          :label="t('agora', 'Group Title')"
          @update:model-value="inquiryTitle = $event"
          @submit="addGroupInquiry"
        />
      </ConfigBox>

      <ConfigBox
        v-if="showInquiryGroupTypeSelector"
        :name="t('agora', 'Group Type')"
        :label="t('agora', 'Select group type')"
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

      <ConfigBox
        v-else-if="currentInquiryGroupTypeData"
        :name="t('agora', 'Group Type')"
        :label="t('agora', 'Group type')"
      >
        <template #icon>
          <Component :is="InquiryGeneralIcons.Check" />
        </template>
        <div class="selected-type">
          <strong>{{ currentInquiryGroupTypeData.label }}</strong>
          <p v-if="currentInquiryGroupTypeData.description" class="type-description">
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
          {{ adding ? t('agora', 'Creating...') : 
            creationMode === 'subgroup-creation' ? 
            t('agora', 'Add Allowed Response') : 
            t('agora', 'Create Group') }}
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
  padding: 24px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  margin: 20px;
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--color-border);
}

.dialog-header h3 {
  margin: 0;
  font-size: 1.2em;
  color: var(--color-main-text);
}

.mode-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8em;
  font-weight: 600;
}

.mode-badge.response {
  background: var(--color-warning);
  color: var(--color-warning-text);
}

.mode-badge.child {
  background: var(--color-info);
  color: var(--color-info-text);
}

.mode-badge.creation {
  background: var(--color-success);
  color: var(--color-success-text);
}

.context-description {
  margin-bottom: 20px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: 6px;
  border-left: 3px solid var(--color-primary);
}

.context-description p {
  margin: 0;
  color: var(--color-text-lighter);
  font-size: 0.9em;
  line-height: 1.4;
}

.create-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.selected-type {
  padding: 12px 0;
}

.type-description {
  color: var(--color-text-lighter);
  font-size: 0.9em;
  margin-top: 8px;
  line-height: 1.4;
}

.access-settings {
  padding: 12px 0;
}

.access-description {
  color: var(--color-text-lighter);
  margin-bottom: 16px;
  font-size: 0.95em;
}

.access-radio-group {
  margin-bottom: 20px;
}

.nextcloud-groups-selection {
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
