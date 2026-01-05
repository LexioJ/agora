<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField, NcTextArea } from '@nextcloud/vue'
import { useTemplateWizardStore } from '../../../stores/templateWizard'

const wizardStore = useTemplateWizardStore()

const editableData = computed(() => wizardStore.editableData)

// Section state management
const expandedSections = ref<Record<string, boolean>>({
	inquiry_families: true,
	inquiry_types: false,
	inquiry_statuses: false,
	option_types: false,
	inquiry_group_types: false,
	categories: false,
	locations: false,
})

const editingItem = ref<{ section: string; index: number } | null>(null)
const editingItemData = ref<any>(null)

interface Section {
	key: string
	label: string
	icon: string
	count: number
	itemLabelKey: string
	itemTypeKey: string
}

const sections = computed((): Section[] => {
	if (!editableData.value) return []

	return [
		{
			key: 'inquiry_families',
			label: t('agora', 'Inquiry Families'),
			icon: '📁',
			count: editableData.value.inquiry_families?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'family_type',
		},
		{
			key: 'inquiry_types',
			label: t('agora', 'Inquiry Types'),
			icon: '📋',
			count: editableData.value.inquiry_types?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'inquiry_type',
		},
		{
			key: 'inquiry_statuses',
			label: t('agora', 'Inquiry Statuses'),
			icon: '📊',
			count: editableData.value.inquiry_statuses?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'status_key',
		},
		{
			key: 'option_types',
			label: t('agora', 'Option Types'),
			icon: '🎯',
			count: editableData.value.option_types?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'option_type',
		},
		{
			key: 'inquiry_group_types',
			label: t('agora', 'Inquiry Group Types'),
			icon: '👥',
			count: editableData.value.inquiry_group_types?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'group_type',
		},
		{
			key: 'categories',
			label: t('agora', 'Categories'),
			icon: '🏷️',
			count: editableData.value.categories?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'category_key',
		},
		{
			key: 'locations',
			label: t('agora', 'Locations'),
			icon: '📍',
			count: editableData.value.locations?.length || 0,
			itemLabelKey: 'label',
			itemTypeKey: 'location_key',
		},
	].filter(section => section.count > 0)
})

const toggleSection = (key: string) => {
	expandedSections.value[key] = !expandedSections.value[key]
}

const startEdit = (section: string, index: number) => {
	const item = editableData.value?.[section]?.[index]
	if (item) {
		editingItem.value = { section, index }
		editingItemData.value = JSON.parse(JSON.stringify(item))
	}
}

const cancelEdit = () => {
	editingItem.value = null
	editingItemData.value = null
}

const saveEdit = () => {
	if (editingItem.value && editingItemData.value) {
		wizardStore.updateEditableItem(
			editingItem.value.section,
			editingItem.value.index,
			editingItemData.value
		)
		editingItem.value = null
		editingItemData.value = null
	}
}

const removeItem = (section: string, index: number) => {
	if (confirm(t('agora', 'Are you sure you want to remove this item?'))) {
		wizardStore.removeEditableItem(section, index)
	}
}

const isEditing = (section: string, index: number) => {
	return editingItem.value?.section === section && editingItem.value?.index === index
}

const getItemLabel = (item: any, section: Section) => {
	return item[section.itemLabelKey] || item[section.itemTypeKey] || t('agora', 'Unnamed')
}

const getItemType = (item: any, section: Section) => {
	return item[section.itemTypeKey] || ''
}

const totalItems = computed(() => {
	if (!editableData.value) return 0
	return sections.value.reduce((sum, section) => sum + section.count, 0)
})

// Watch for editable data changes to initialize editing
watch(() => editableData.value, () => {
	if (editableData.value) {
		// Expand first section by default
		if (sections.value.length > 0) {
			expandedSections.value[sections.value[0].key] = true
		}
	}
})
</script>

<template>
	<div class="preview-step">
		<div class="preview-header">
			<h2>{{ t('agora', 'Preview & Customize Template') }}</h2>
			<p class="subtitle">
				{{ t('agora', 'Review and customize the template before import') }}
			</p>
		</div>

		<div v-if="!editableData" class="loading-state">
			<p>{{ t('agora', 'Preparing template data...') }}</p>
		</div>

		<div v-else class="preview-content">
			<!-- Summary Card -->
			<div class="summary-card">
				<h3>{{ t('agora', 'Content Summary') }}</h3>
				<div class="summary-stats">
					<div class="stat-item">
						<span class="stat-icon">📦</span>
						<span class="stat-value">{{ totalItems }}</span>
						<span class="stat-label">{{ t('agora', 'Total Items') }}</span>
					</div>
					<div class="stat-item">
						<span class="stat-icon">🌐</span>
						<span class="stat-value">{{ wizardStore.selectedLanguage }}</span>
						<span class="stat-label">{{ t('agora', 'Language') }}</span>
					</div>
				</div>
			</div>

			<!-- Sections -->
			<div class="sections-container">
				<div
					v-for="section in sections"
					:key="section.key"
					class="section-block">
					<div
						class="section-header"
						@click="toggleSection(section.key)">
						<div class="section-title">
							<span class="section-icon">{{ section.icon }}</span>
							<h3>{{ section.label }}</h3>
							<span class="section-count">({{ section.count }})</span>
						</div>
						<span class="expand-icon">
							{{ expandedSections[section.key] ? '▼' : '▶' }}
						</span>
					</div>

					<div v-if="expandedSections[section.key]" class="section-content">
						<div
							v-for="(item, index) in editableData[section.key]"
							:key="index"
							class="item-row">
							<!-- View Mode -->
							<div v-if="!isEditing(section.key, index)" class="item-view">
								<div class="item-info">
									<div class="item-label">{{ getItemLabel(item, section) }}</div>
									<div class="item-type">{{ getItemType(item, section) }}</div>
									<div v-if="item.description" class="item-description">
										{{ item.description }}
									</div>
								</div>
								<div class="item-actions">
									<NcButton
										type="tertiary"
										@click="startEdit(section.key, index)">
										{{ t('agora', 'Edit') }}
									</NcButton>
									<NcButton
										type="error"
										@click="removeItem(section.key, index)">
										{{ t('agora', 'Remove') }}
									</NcButton>
								</div>
							</div>

							<!-- Edit Mode -->
							<div v-else class="item-edit">
								<div class="edit-form">
									<NcTextField
										v-model="editingItemData[section.itemTypeKey]"
										:label="t('agora', 'Type Key')"
										:disabled="true"
										class="edit-field" />

									<NcTextField
										v-model="editingItemData[section.itemLabelKey]"
										:label="t('agora', 'Label')"
										class="edit-field" />

									<NcTextArea
										v-if="editingItemData.description !== undefined"
										v-model="editingItemData.description"
										:label="t('agora', 'Description')"
										class="edit-field" />
								</div>
								<div class="edit-actions">
									<NcButton
										type="primary"
										@click="saveEdit">
										{{ t('agora', 'Save') }}
									</NcButton>
									<NcButton
										type="tertiary"
										@click="cancelEdit">
										{{ t('agora', 'Cancel') }}
									</NcButton>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.preview-step {
	padding: 20px;
	max-width: 900px;
	margin: 0 auto;
}

.preview-header {
	text-align: center;
	margin-bottom: 30px;

	h2 {
		font-size: 24px;
		font-weight: 600;
		margin-bottom: 8px;
	}

	.subtitle {
		color: var(--color-text-maxcontrast);
		font-size: 14px;
	}
}

.loading-state {
	text-align: center;
	padding: 40px;
	color: var(--color-text-maxcontrast);
}

.summary-card {
	background: var(--color-primary-element-light);
	border-radius: var(--border-radius-large);
	padding: 20px;
	margin-bottom: 24px;

	h3 {
		font-size: 16px;
		font-weight: 600;
		margin-bottom: 16px;
		color: var(--color-primary-element);
	}
}

.summary-stats {
	display: flex;
	gap: 32px;
	justify-content: center;
}

.stat-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
}

.stat-icon {
	font-size: 32px;
}

.stat-value {
	font-size: 24px;
	font-weight: 700;
	color: var(--color-primary-element);
}

.stat-label {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	text-transform: uppercase;
}

.sections-container {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.section-block {
	background: var(--color-main-background);
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow: hidden;
}

.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16px 20px;
	cursor: pointer;
	background: var(--color-background-hover);
	transition: background 0.2s ease;

	&:hover {
		background: var(--color-background-dark);
	}
}

.section-title {
	display: flex;
	align-items: center;
	gap: 12px;

	h3 {
		font-size: 16px;
		font-weight: 600;
		margin: 0;
	}
}

.section-icon {
	font-size: 20px;
}

.section-count {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
}

.expand-icon {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}

.section-content {
	padding: 12px;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.item-row {
	background: var(--color-background-hover);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 12px 16px;
}

.item-view {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 16px;
}

.item-info {
	flex: 1;
}

.item-label {
	font-size: 15px;
	font-weight: 600;
	margin-bottom: 4px;
}

.item-type {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	font-family: monospace;
	margin-bottom: 4px;
}

.item-description {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin-top: 8px;
}

.item-actions {
	display: flex;
	gap: 8px;
}

.item-edit {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.edit-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.edit-field {
	width: 100%;
}

.edit-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
}
</style>
