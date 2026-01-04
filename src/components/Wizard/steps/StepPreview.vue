<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { useTemplateWizardStore } from '../../../stores/templateWizard'

const wizardStore = useTemplateWizardStore()

const template = computed(() => wizardStore.selectedTemplate)
const language = computed(() => wizardStore.selectedLanguage)
</script>

<template>
	<div class="preview-step">
		<div class="preview-header">
			<h2>{{ t('agora', 'Preview Template') }}</h2>
			<p class="subtitle">
				{{ t('agora', 'Review what will be imported') }}
			</p>
		</div>

		<div v-if="template" class="preview-content">
			<div class="preview-section">
				<h3>{{ t('agora', 'Template') }}</h3>
				<p><strong>{{ template.name }}</strong> ({{ t('agora', 'Version') }} {{ template.version }})</p>
				<p>{{ template.description }}</p>
			</div>

			<div class="preview-section">
				<h3>{{ t('agora', 'Language') }}</h3>
				<p><strong>{{ language }}</strong></p>
			</div>

			<div class="preview-section">
				<h3>{{ t('agora', 'Content Summary') }}</h3>
				<ul class="content-list">
					<li>{{ template.counts.inquiry_families }} {{ t('agora', 'Inquiry Families') }}</li>
					<li>{{ template.counts.inquiry_types }} {{ t('agora', 'Inquiry Types') }}</li>
					<li>{{ template.counts.inquiry_statuses }} {{ t('agora', 'Inquiry Statuses') }}</li>
					<li>{{ template.counts.categories }} {{ t('agora', 'Categories') }}</li>
					<li v-if="template.counts.locations > 0">{{ template.counts.locations }} {{ t('agora', 'Locations') }}</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<style scoped lang="scss">
.preview-step {
	padding: 20px;
	max-width: 800px;
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

.preview-section {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 20px;
	margin-bottom: 16px;

	h3 {
		font-size: 16px;
		font-weight: 600;
		margin-bottom: 12px;
		color: var(--color-primary-element);
	}
}

.content-list {
	list-style: none;
	padding: 0;
	margin: 0;

	li {
		padding: 8px 0;
		border-bottom: 1px solid var(--color-border-dark);

		&:last-child {
			border-bottom: none;
		}
	}
}
</style>
