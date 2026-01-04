<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { NcLoadingIcon, NcEmptyContent } from '@nextcloud/vue'
import { useTemplateWizardStore, type Template } from '../../../stores/templateWizard'

const wizardStore = useTemplateWizardStore()

const templates = computed(() => wizardStore.availableTemplates)

const isLoading = computed(() => wizardStore.loadingTemplates)

const selectTemplate = (template: Template) => {
	wizardStore.selectTemplate(template)
}

const formatCount = (count: number, label: string) => {
	return `${count} ${label}${count !== 1 ? 's' : ''}`
}
</script>

<template>
	<div class="template-selection">
		<div class="template-header">
			<h2>{{ t('agora', 'Select a Template') }}</h2>
			<p class="subtitle">
				{{ t('agora', 'Choose a pre-configured template or upload your own') }}
			</p>
		</div>

		<NcLoadingIcon v-if="isLoading" :size="64" />

		<NcEmptyContent
			v-else-if="templates.length === 0"
			:name="t('agora', 'No templates available')"
			:description="t('agora', 'Please check your installation or upload a custom template')">
			<template #icon>
				<span class="icon-folder" />
			</template>
		</NcEmptyContent>

		<div v-else class="template-list">
			<div
				v-for="template in templates"
				:key="template.name"
				class="template-card"
				:class="{ selected: wizardStore.selectedTemplate?.name === template.name }"
				@click="selectTemplate(template)">
				<div class="template-card-header">
					<h3 class="template-name">
						{{ template.name }}
					</h3>
					<span class="template-version">v{{ template.version }}</span>
				</div>

				<p class="template-description">{{ template.description }}</p>

				<div class="template-meta">
					<div class="meta-item">
						<span class="meta-label">{{ t('agora', 'Author:') }}</span>
						<span class="meta-value">{{ template.author }}</span>
					</div>
					<div class="meta-item">
						<span class="meta-label">{{ t('agora', 'Languages:') }}</span>
						<span class="meta-value">{{ template.available_languages.join(', ') }}</span>
					</div>
				</div>

				<div class="template-stats">
					<div class="stat-item">
						<span class="stat-value">{{ template.counts.inquiry_families }}</span>
						<span class="stat-label">{{ t('agora', 'Families') }}</span>
					</div>
					<div class="stat-item">
						<span class="stat-value">{{ template.counts.inquiry_types }}</span>
						<span class="stat-label">{{ t('agora', 'Types') }}</span>
					</div>
					<div class="stat-item">
						<span class="stat-value">{{ template.counts.inquiry_statuses }}</span>
						<span class="stat-label">{{ t('agora', 'Statuses') }}</span>
					</div>
					<div class="stat-item">
						<span class="stat-value">{{ template.counts.categories }}</span>
						<span class="stat-label">{{ t('agora', 'Categories') }}</span>
					</div>
				</div>

				<div v-if="wizardStore.selectedTemplate?.name === template.name" class="selected-badge">
					<span class="check-icon">✓</span>
					{{ t('agora', 'Selected') }}
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.template-selection {
	padding: 20px;
}

.template-header {
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

.template-list {
	display: grid;
	gap: 16px;
	max-width: 800px;
	margin: 0 auto;
}

.template-card {
	background: var(--color-main-background);
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 20px;
	cursor: pointer;
	transition: all 0.2s ease;
	position: relative;

	&:hover {
		border-color: var(--color-primary-element);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	&.selected {
		border-color: var(--color-primary-element);
		border-width: 3px;
		box-shadow: 0 4px 16px rgba(0, 122, 255, 0.2);
	}
}

.template-card-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12px;
}

.template-name {
	font-size: 18px;
	font-weight: 600;
	margin: 0;
}

.template-version {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	background: var(--color-background-dark);
	padding: 2px 8px;
	border-radius: var(--border-radius-pill);
}

.template-description {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	margin-bottom: 16px;
}

.template-meta {
	margin-bottom: 16px;
	padding-bottom: 16px;
	border-bottom: 1px solid var(--color-border);
}

.meta-item {
	display: flex;
	align-items: center;
	margin-bottom: 8px;
	font-size: 13px;
}

.meta-label {
	font-weight: 600;
	margin-right: 8px;
	min-width: 80px;
}

.meta-value {
	color: var(--color-text-maxcontrast);
}

.template-stats {
	display: flex;
	gap: 20px;
	justify-content: space-around;
}

.stat-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.stat-value {
	font-size: 24px;
	font-weight: 600;
	color: var(--color-primary-element);
}

.stat-label {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	margin-top: 4px;
}

.selected-badge {
	position: absolute;
	top: 16px;
	right: 16px;
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	padding: 4px 12px;
	border-radius: var(--border-radius-pill);
	font-size: 12px;
	font-weight: 600;
	display: flex;
	align-items: center;
	gap: 4px;

	.check-icon {
		font-size: 14px;
	}
}
</style>
