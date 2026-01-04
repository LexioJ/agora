<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { NcNoteCard } from '@nextcloud/vue'
import { useTemplateWizardStore } from '../../../stores/templateWizard'

const wizardStore = useTemplateWizardStore()

const template = computed(() => wizardStore.selectedTemplate)
const language = computed(() => wizardStore.selectedLanguage)
</script>

<template>
	<div class="summary-step">
		<div class="summary-header">
			<h2>{{ t('agora', 'Review & Confirm') }}</h2>
			<p class="subtitle">
				{{ t('agora', 'Please review your selections before importing') }}
			</p>
		</div>

		<NcNoteCard type="warning" class="warning-note">
			<p>
				{{ t('agora', 'This will create new configuration data in your database. If you want to replace existing data, run the cleanup command first.') }}
			</p>
		</NcNoteCard>

		<div v-if="template" class="summary-content">
			<div class="summary-box">
				<h3>{{ t('agora', 'Import Summary') }}</h3>

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Template:') }}</span>
					<span class="value">{{ template.name }} (v{{ template.version }})</span>
				</div>

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Language:') }}</span>
					<span class="value">{{ language }}</span>
				</div>

				<div class="summary-divider" />

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Families:') }}</span>
					<span class="value">{{ template.counts.inquiry_families }} {{ t('agora', 'items') }}</span>
				</div>

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Inquiry Types:') }}</span>
					<span class="value">{{ template.counts.inquiry_types }} {{ t('agora', 'items') }}</span>
				</div>

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Statuses:') }}</span>
					<span class="value">{{ template.counts.inquiry_statuses }} {{ t('agora', 'items') }}</span>
				</div>

				<div class="summary-item">
					<span class="label">{{ t('agora', 'Categories:') }}</span>
					<span class="value">{{ template.counts.categories }} {{ t('agora', 'items') }}</span>
				</div>

				<div v-if="template.counts.locations > 0" class="summary-item">
					<span class="label">{{ t('agora', 'Locations:') }}</span>
					<span class="value">{{ template.counts.locations }} {{ t('agora', 'items') }}</span>
				</div>

				<div class="summary-divider" />

				<div class="summary-total">
					<span class="label">{{ t('agora', 'Total Items:') }}</span>
					<span class="value">{{ template.total_items }}</span>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.summary-step {
	padding: 20px;
	max-width: 800px;
	margin: 0 auto;
}

.summary-header {
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

.warning-note {
	margin-bottom: 30px;
}

.summary-box {
	background: var(--color-main-background);
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 24px;

	h3 {
		font-size: 18px;
		font-weight: 600;
		margin-bottom: 20px;
		text-align: center;
		color: var(--color-primary-element);
	}
}

.summary-item {
	display: flex;
	justify-content: space-between;
	padding: 12px 0;

	.label {
		font-weight: 600;
		color: var(--color-text-maxcontrast);
	}

	.value {
		font-weight: 500;
	}
}

.summary-divider {
	height: 1px;
	background: var(--color-border);
	margin: 16px 0;
}

.summary-total {
	display: flex;
	justify-content: space-between;
	padding: 16px;
	background: var(--color-primary-element-light);
	border-radius: var(--border-radius);
	font-size: 18px;
	font-weight: 600;

	.value {
		color: var(--color-primary-element);
	}
}
</style>
