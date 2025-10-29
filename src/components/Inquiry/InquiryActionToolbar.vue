<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { watch, ref, computed, nextTick } from 'vue' 
import { AccessType } from '../../stores/inquiry'
import { useInquiriesStore } from '../../stores/inquiries.ts'
import { t } from '@nextcloud/l10n'
import { showSuccess, showError } from '@nextcloud/dialogs'
import {
  getAvailableResponseTypes,
  getAvailableTransformTypes,
  getInquiryTypeData,
  isInquiryFinalStatus
} from '../../helpers/modules/InquiryHelper.ts'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import InquiryItemActions from './InquiryItemActions.vue'
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import {
  canViewToggle,
  createPermissionContextForContent,
  ContentType,
  shouldShowResponseActions,
  shouldShowTransformationActions,
  // getFilteredResponseTypes,
  // getFilteredTransformTypes,
  canCreateResponseType,
  canCreateTransformationType
} from '../../utils/permissions.ts'

// Props
const props = defineProps<{
  inquiryStore: any
  sessionStore: any
  isSaving?: boolean
  isReadonlyDescription?: boolean
}>()

const inquiriesStore = useInquiriesStore()


// Emits
const emit = defineEmits<{
  save: []
  allowedResponse: [responseType: string]
  allowedTransformation: [transformType: string]
}>()

// Context for permissions
const context = computed(() => {
  const ctx = createPermissionContextForContent(
    ContentType.Inquiry,
    props.inquiryStore.owner.id,
    props.inquiryStore.configuration.access === 'public',
    props.inquiryStore.status.isLocked,
    props.inquiryStore.status.isExpired,
    props.inquiryStore.status.deletionDate > 0,
    props.inquiryStore.status.isArchived,
    props.inquiryStore.inquiryGroups.length > 0,
    props.inquiryStore.inquiryGroups,
    props.inquiryStore.type,
    props.inquiryStore.family, 
    props.inquiryStore.configuration.access as AccessType,
    isInquiryFinalStatus(props.inquiryStore,props.sessionStore.appSettings),
    props.inquiryStore.status.moderationStatus 
  )
  return ctx
})

const selectedStatus = ref(props.inquiryStore.status.moderationStatus || 'pending')

const statusOptions = [
  { id: 'pending', label: t('agora', 'Pending'), value: 'pending' },
  { id: 'accepted', label: t('agora', 'Accepted'), value: 'accepted' },
  { id: 'rejected', label: t('agora', 'Rejected'), value: 'rejected' },
]

const inquiryAccess =  computed({
  get: () => props.inquiryStore.configuration.access === 'moderate',
  set: async (value) => {
    if (value) {
	try {
	if (props.sessionStore.appSettings.currentUser.isOfficial && props.sessionStore.appSettings.officialBypassModeration) {
	   await setModerationStatus("accepted")
	} else if (!props.sessionStore.appSettings.allowModeration) { 
	    await setModerationStatus("accepted")
	    }
	 else {
	    await setModerationStatus("pending")
	    }
    } catch (error) {
	console.error('Failed to toggle moderation:', error)
	}
  }
  }
})

watch(() => props.inquiryStore.status.moderationStatus, (newStatus) => {
  if (newStatus) {
    selectedStatus.value = newStatus
  }
})

const resolveTypeData = (inquiryType: string, types: any[]) => {
  const typeInfo = types.find(t => t.inquiry_type === inquiryType)
  return getInquiryTypeData(inquiryType, types, typeInfo?.label || inquiryType)
}

const setModerationStatus = async (status: string) => {
  try {
  if (status === 'accepted') {
    await props.inquiryStore.submitInquiry("submit_for_accepted")
    inquiriesStore.submitInquiry(props.inquiryStore.id,"submit_for_accepted")
    showSuccess(t('agora','Inquiry accepted'))
  } else if (status === 'rejected') {
    await props.inquiryStore.submitInquiry("submit_for_rejected")
    inquiriesStore.submitInquiry(props.inquiryStore.id,"submit_for_rejected")
    showSuccess(t('agora','Inquiry rejected'))
  } else if (status === 'pending') {
      await props.inquiryStore.submitInquiry("submit_for_moderate")
      inquiriesStore.submitInquiry(props.inquiryStore.id,"submit_for_moderate")
      showSuccess(t('agora','Inquiry submitted for moderation'))
  }
    await nextTick()
  } catch (error) {
    showError(t('agora','Failed to submit inquiry for moderation'))
  }

}

// First get the raw available types
const rawResponseTypes = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return getAvailableResponseTypes(props.inquiryStore.type, inquiryTypes)
})

const rawTransformTypes = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return getAvailableTransformTypes(props.inquiryStore.type, inquiryTypes)
})

// Check if menus should be shown (using permissions)
const showActionsMenu = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return shouldShowResponseActions(props.inquiryStore.type, inquiryTypes, context.value)
})

const showTransformActionsMenu = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return shouldShowTransformationActions(props.inquiryStore.type, inquiryTypes, context.value)
})

//  Get filtered types (applying permissions)
const availableResponseTypes = computed(() => rawResponseTypes.value.filter(type =>
    canCreateResponseType(type.inquiry_type, context.value)
  ))

const availableTransformTypes = computed(() => rawTransformTypes.value.filter(type =>
    canCreateTransformationType(type.inquiry_type, context.value)
  ))

//  Enriched types for display (using InquiryHelper)
const enrichedResponseTypes = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return availableResponseTypes.value.map(responseType => {
    const typeData = getInquiryTypeData(responseType.inquiry_type, inquiryTypes)
    return {
      ...responseType,
      icon: typeData.icon,
      label: typeData.label
    }
  })
})

const enrichedTransformTypes = computed(() => {
  const inquiryTypes = props.sessionStore.appSettings.inquiryTypeTab || []
  return availableTransformTypes.value.map(transformType => {
    const typeData = getInquiryTypeData(transformType.inquiry_type, inquiryTypes)
    return {
      ...transformType,
      icon: typeData.icon,
      label: typeData.label
    }
  })
})

const getStatusLabel = (status: string) => {
  const option = statusOptions.find(opt => opt.value === status)
  return option ? option.label : status
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'rejected':
      return 'color: var(--color-error)'
    case 'accepted':
      return 'color: var(--color-success)'
    case 'pending':
      return 'color: var(--color-warning)'
    default:
      return ''
  }
}


const canEditInquiry = computed(() => !props.isReadonlyDescription)

// Check if save button should be shown
const showSaveButton = computed(() => canEditInquiry.value)

// Handle save
const handleSave = () => {
  emit('save')
}

// Handle allowed response
const handleAllowedResponse = (responseType: string) => {
  emit('allowedResponse', responseType)
}

// Handle allowed transformation
const handleAllowedTransformation = (transformType: string) => {
  emit('allowedTransformation', transformType)
}
</script>

<template>
	<div class="inquiry-action-toolbar">
		<div class="left-actions">
			<!-- Save and Response buttons -->
			<div class="primary-actions">
				<NcButton
						v-if="showSaveButton"
						:disabled="isSaving"
						type="primary"
						class="save-button"
						@click.prevent="handleSave"
						>
						<template #icon>
							<component :is="InquiryGeneralIcons.Save" :size="20" />
						</template>
				{{ t('agora', 'Save') }}
				<span v-if="isSaving" class="loading-icon"></span>
				</NcButton>

				<!-- Allowed Response types menu -->
				<NcActions
						v-if="showActionsMenu && enrichedResponseTypes.length > 0"
						menu-name="Allowed Response"
						class="response-actions"
						>
						<template #icon>
							<component :is="InquiryGeneralIcons.Reply" :size="20" />
						</template>
				<NcActionButton
						v-for="responseType in enrichedResponseTypes"
						:key="responseType.inquiry_type"
						@click="handleAllowedResponse(responseType.inquiry_type)"
						>
						<template #icon>
							<component
									:is="responseType.icon"
									v-if="responseType.icon"
									:size="20"
									/>
							<span v-else>📄</span>
						</template>
						{{ responseType.label }}
				</NcActionButton>
				</NcActions>

				<!-- Allowed Transformation Type -->
				<NcActions
						v-if="showTransformActionsMenu && enrichedTransformTypes.length > 0"
						menu-name="Allowed Transformation"
						class="transform-actions"
						>
						<template #icon>
							<component :is="InquiryGeneralIcons.Transform" :size="20" />
						</template>
				<NcActionButton
						v-for="transformType in enrichedTransformTypes"
						:key="transformType.inquiry_type"
						@click="handleAllowedTransformation(transformType.inquiry_type)"
						>
						<template #icon>
							<component
									:is="transformType.icon"
									v-if="transformType.icon"
									:size="20"
									/>
							<span v-else>🔄</span>
						</template>
						{{ transformType.label }}
				</NcActionButton>
				</NcActions>
			</div>
		</div>

		<!-- Right: Access switch and item actions -->
		<div class="right-actions">
			<div v-if="inquiryStore.configuration.access === 'private' && inquiryStore.status.moderationStatus !== 'rejected'" class="access-control">
				<label class="control-label">{{ t('agora', 'Submit to moderation') }}</label>
				<NcCheckboxRadioSwitch
						v-model="inquiryAccess"
						type="switch"
						/>

			</div>
			<div v-if="sessionStore.currentUser.isModerator">
				<div v-if="inquiryStore.configuration.access === 'moderate' && inquiryStore.status.moderationStatus === 'pending'" class="access-control">
					<label class="control-label">{{ t('agora', 'Moderate') }}</label>
					<NcSelect
							v-model="selectedStatus"
							:options="statusOptions"
							:clearable="false"
							:multiple="false"
							class="status-select"
							@update:model-value="(selected) => setModerationStatus(selected.value)"
							/>
				</div>
			</div>
			<div class="access-control">
				<label class="status-label" :style="getStatusColor(inquiryStore.status.moderationStatus)">
					{{ getStatusLabel(inquiryStore.status.moderationStatus) }}
				</label>
			</div>
			<div
					v-if="canViewToggle(context)"
					class="item-actions"
					>
					<InquiryItemActions :key="`actions-${inquiryStore.id}`" :inquiry="inquiryStore" />
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.inquiry-action-toolbar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 1rem;
	width: 100%;
	margin-bottom: 1rem;
	padding: 1rem 1.5rem;
	background: var(--color-background-dark);
	border-radius: var(--border-radius-large);
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.left-actions {
	display: flex;
	gap: 1rem;
	align-items: center;
	flex: 1;
	justify-content: flex-start;
}

.right-actions {
	display: flex;
	gap: 1rem;
	align-items: center;
	justify-content: flex-end;
}

						  .access-control {
							  display: flex;
							  align-items: center;
							  gap: 0.75rem;

							  .control-label {
								  font-weight: 600;
								  color: var(--color-text-lighter);
								  white-space: nowrap;
								  margin: 0;
								  font-size: 0.875rem;
							  }
						  }
						  .status-label {
							  font-weight: bold;
							  padding: 4px 8px;
							  border-radius: 4px;
							  background: var(--color-background-darker);
						  }
						  .status-select {
							  width: 160px !important;
							  min-width: 120px !important;

							  :deep(.nc-select__input) {
								  font-size: 0.8rem !important;
								  padding: 4px 8px !important;
							  }

							  :deep(.nc-select__toggle) {
								  min-height: 32px !important;
							  }
						  }

						  .primary-actions {
							  display: flex;
							  gap: 0.5rem;
							  align-items: center;
						  }

						  .save-button {
							  background-color: var(--color-primary);
							  color: white;
							  border: none;
							  padding: 8px 16px;
							  border-radius: 20px;
							  font-weight: 500;
							  min-width: 100px;
							  white-space: nowrap;
						  }

						  .response-actions,
						  .transform-actions {
							  margin: 0 0.25rem;
						  }

						  .loading-icon {
							  display: inline-block;
							  width: 16px;
							  height: 16px;
							  border: 2px solid transparent;
							  border-top: 2px solid currentColor;
							  border-radius: 50%;
							  animation: spin 1s linear infinite;
							  margin-right: 8px;
						  }

						  @keyframes spin {
							  from {
								  transform: rotate(0deg);
							  }
							  to {
								  transform: rotate(360deg);
							  }
						  }

						  /* Mobile responsive */
						  @media (max-width: 768px) {
							  .inquiry-action-toolbar {
								  flex-direction: column;
								  gap: 1rem;
								  padding: 1rem;
							  }

							  .left-actions {
								  flex-direction: column;
								  width: 100%;
							  }

							  .primary-actions {
								  flex-wrap: wrap;
								  justify-content: center;
								  width: 100%;
							  }

							  .right-actions {
								  width: 100%;
								  justify-content: center;
								  flex-wrap: wrap;
							  }

							  .access-control {
								  justify-content: center;
								  width: 100%;
							  }

							  .status-select {
								  width: 100px !important;
								  min-width: 100px !important;
							  }
						  }
</style>
