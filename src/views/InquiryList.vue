<!--
	- SPDX-FileCopyrightText: 2018 Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { watch, ref, computed, onMounted } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { useRoute, useRouter } from 'vue-router'
import { showError } from '@nextcloud/dialogs'
import { t, n } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionButtonGroup from '@nextcloud/vue/components/NcActionButtonGroup'

import { HeaderBar, IntersectionObserver } from '../components/Base/index.ts'
import { AgoraAppIcon } from '../components/AppIcons/index.ts'
import InquiryItem from '../components/Inquiry/InquiryItem.vue'
import InquiryFilter from '../components/Inquiry/InquiryFilter.vue'
import { FilterType, useInquiriesStore } from '../stores/inquiries.ts'
import InquiryListSort from '../components/Inquiry/InquiryListSort.vue'
import InquiryItemActions from '../components/Inquiry/InquiryItemActions.vue'
import ActionAddInquiry from '../components/Actions/modules/ActionAddInquiry.vue'
import { usePreferencesStore } from '../stores/preferences.ts'
import { useSessionStore } from '../stores/session.ts'
import ActionToggleSidebar from '../components/Actions/modules/ActionToggleSidebar.vue'
import { useInquiryGroupsStore } from '../stores/inquiryGroups.ts'
import LoadingOverlay from '../components/Base/modules/LoadingOverlay.vue'
import { InquiryGeneralIcons } from '../utils/icons.ts'

const inquiriesStore = useInquiriesStore()
const inquiryGroupsStore = useInquiryGroupsStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()
const route = useRoute()
const router = useRouter()

const familyId = computed(() => route.params.familyId as string)

const selectedFamily = ref<string | null>(familyId.value)


const viewMode = ref('table-view')
const showViewSubmenu = ref(false)

const getCurrentViewIcon = computed(() => {
    return viewMode.value === 'list-view'
        ? InquiryGeneralIcons.viewlistoutline
        : InquiryGeneralIcons.table
})

const setViewMode = (mode) => {
    viewMode.value = mode
    showViewSubmenu.value = false
}

const toggleViewSubmenu = () => {
    showViewSubmenu.value = !showViewSubmenu.value
}

function navigateToCreateMode() {
  const mode = viewMode.value || 'create'
  
  if (selectedFamily.value) {
    router.push({ 
      name: 'family-inquiries', 
      params: { familyId: selectedFamily.value },
    })
  } else {
    router.push({ 
      name: 'menu', 
      params: { familyId: selectedFamily.value },
    })
  }
}

watch(
  () => route.params.familyId,
  (newFamilyId) => {
    selectedFamily.value = newFamilyId as string || null
  }
)


const title = computed(() => {
  if (route.name === 'group') {
    return (
      inquiryGroupsStore.currentInquiryGroup?.titleExt ||
      inquiryGroupsStore.currentInquiryGroup?.name ||
      ''
    )
  }
  return inquiriesStore.categories[route.params.type as FilterType].titleExt
})

const showMore = computed(
  () =>
    inquiriesStore.chunkedList.length < inquiriesStore.inquiriesFilteredSorted.length &&
    inquiriesStore.meta.status !== 'loading'
)

const countLoadedInquiries = computed(() =>
  Math.min(inquiriesStore.chunkedList.length, inquiriesStore.inquiriesFilteredSorted.length)
)

const infoLoaded = computed(() =>
  n(
    'agora',
    '{loadedInquiries} of {countInquiries} inquiry loaded.',
    '{loadedInquiries} of {countInquiries} inquiries loaded.',
    inquiriesStore.inquiriesFilteredSorted.length,
    {
      loadedInquiries: countLoadedInquiries.value,
      countInquiries: inquiriesStore.inquiriesFilteredSorted.length,
    }
  )
)

const description = computed(() => {
  if (route.name === 'group') {
    return inquiryGroupsStore.currentInquiryGroup?.description || ''
  }

  return inquiriesStore.categories[route.params.type as FilterType].description
})

const emptyInquiryListnoInquiries = computed(
  () => inquiriesStore.inquiriesFilteredSorted.length < 1
)

const isGridView = computed(() => preferencesStore.user.defaultViewInquiry === 'table-view')

const loadingOverlayProps = {
  name: t('agora', 'Loading overview'),
  teleportTo: '#content-vue',
  loadingTexts: [
    t('agora', 'Fetching inquiries…'),
    t('agora', 'Checking access…'),
    t('agora', 'Almost ready…'),
    t('agora', 'Do not go away…'),
    t('agora', 'Please be patient…'),
  ],
}

const emptyContentProps = computed(() => ({
  name: t('agora', 'No inquiries found for this category'),
  description: t('agora', 'Add one or change category!'),
}))

/**
 *
 */
async function loadMore() {
  try {
    inquiriesStore.addChunk()
  } catch {
    showError(t('agora', 'Error loading more inquiries'))
  }
}

onMounted(() => {
  inquiriesStore.load(false)
})
</script>

<template>
	<NcAppContent class="inquiry-list">
	<HeaderBar>
	<template #title>
		{{ title }}
	</template>
	{{ description }}
	<template #right>
		<!-- Mode Buttons -->
		<!-- Mode Buttons -->
		<NcActionButtonGroup name="Main modes" class="main-mode-group">
		<!-- Create Mode -->
		<NcActionButton
				:class="['main-mode', { active: viewMode === 'create', 'mode-active': viewMode === 'create' }]"
				@click="viewMode = 'create'"
				:aria-label="t('agora', 'Create mode')"
				:title="t('agora', 'Switch to create mode')"
				>
				<template #icon>
					<component :is="InquiryGeneralIcons.add" size="20" />
				</template>
		{{ t('agora', 'Create') }}
		</NcActionButton>

		<!-- View Mode with Dropdown -->
		<NcActionButton
				:class="['main-mode', 'has-submodes', { active: viewMode !== 'create', 'mode-active': viewMode !== 'create' }]"
				:aria-label="t('agora', 'View mode')"
				:title="t('agora', 'Switch to view mode')"
				ref="viewModeButton"
				>
				<template #icon>
					<component :is="getCurrentViewIcon" size="20" />
				</template>
		{{ t('agora', 'View') }}

		<template #extra>
			<NcActionButton
					type="tertiary"
					:aria-label="t('agora', 'Change view style')"
					@click.stop="toggleViewSubmenu"
					>
					<template #icon>
						<component :is="InquiryGeneralIcons.chevrondown" size="16" />
					</template>
			</NcActionButton>
		</template>
		</NcActionButton>
		</NcActionButtonGroup>

		<NcActionMenu
				v-if="showViewSubmenu"
				:boundary="viewportBoundary"
				@close="showViewSubmenu = false"
				>
				<NcActionRadio
						:value="'table-view'"
						:checked="viewMode === 'table-view'"
						@change="setViewMode('table-view')"
						:name="'view-style'"
						>
						<template #icon>
							<component :is="InquiryGeneralIcons.table" size="20" />
						</template>
				{{ t('agora', 'Grid view') }}
				</NcActionRadio>

		<NcActionRadio
				:value="'list-view'"
				:checked="viewMode === 'list-view'"
				@change="setViewMode('list-view')"
				:name="'view-style'"
				>
				<template #icon>
					<component :is="InquiryGeneralIcons.viewlistoutline" size="20" />
				</template>
		{{ t('agora', 'List view') }}
		</NcActionRadio>
		</NcActionMenu>



	</template>
	</HeaderBar>
	<InquiryFilter />

	<div class="area__main">
		<TransitionGroup
				v-if="!emptyInquiryListnoInquiries"
				tag="div"
				name="list"
				:class="[
					'inquiry-list__container',
					isGridView ? 'inquiry-list__grid' : 'inquiry-list__list',
					]"
				>
				<InquiryItem
						v-for="inquiry in inquiriesStore.chunkedList"
						:key="inquiry.id"
						:inquiry="inquiry"
						:grid-view="isGridView"
						>
						<template #actions>
							<InquiryItemActions
									v-if="inquiry.permissions.edit || sessionStore.appPermissions.inquiryCreation"
									:key="`actions-${inquiry.id}`"
									:inquiry="inquiry"
									/>
						</template>
				</InquiryItem>
		</TransitionGroup>

		<IntersectionObserver
				v-if="showMore"
				key="observer"
				class="observer_section"
				@visible="loadMore"
				>
				<div class="clickable_load_more" @click="loadMore">
					{{ infoLoaded }}
					{{ t('agora', 'Click here to load more') }}
				</div>
		</IntersectionObserver>

		<NcEmptyContent v-if="emptyInquiryListnoInquiries" v-bind="emptyContentProps">
		<template #icon>
			<AgoraAppIcon />
		</template>
		</NcEmptyContent>
	</div>
	<LoadingOverlay :show="inquiriesStore.meta.status === 'loading'" v-bind="loadingOverlayProps" />
	</NcAppContent>
</template>

<style lang="scss" scoped>
.inquiry-list {
	.area__main {
		width: 100%;
	}
}

// Unified View Controls
	.view-controls {
	display: flex;
	align-items: center;
	gap: 8px;
	background: var(--color-background-dark);
	border-radius: 10px;
	padding: 6px;
	border: 1px solid var(--color-border);
	margin-right: 12px;
}

			  // Create Mode Button - Compact
				  .create-mode-button {
				  display: flex;
				  align-items: center;
				  gap: 6px;
				  padding: 8px 12px;
				  margin: 0;
				  border: none;
				  border-radius: 6px;
				  background: transparent;
				  color: var(--color-text-lighter);
				  transition: all 0.2s ease;
				  min-height: auto;
				  height: 36px;

				  &:hover {
					  background: var(--color-background-hover);
					  color: var(--color-main-text);
				  }

				  &:active,
				  &:focus {
					  background: var(--color-primary-element);
					  color: var(--color-primary-text);
				  }

				  // Override NcActionButton styles
					  :deep(.action-button) {
					  padding: 0;
					  margin: 0;
					  gap: 6px;
					  background: transparent !important;
					  border: none !important;
				  }

				  :deep(.action-button__icon) {
					  margin: 0 !important;
					  width: 20px;
					  height: 20px;
					  color: inherit;
				  }
			  }

			  .button-label {
				  font-size: 13px;
				  font-weight: 600;
				  white-space: nowrap;
			  }

			  // Compact View Mode Group
				  .view-mode-group {
				  display: flex;
				  align-items: center;
				  gap: 2px;
				  margin: 0;
				  background: transparent;
				  border: none;
				  border-radius: 6px;
				  overflow: hidden;
			  }

			  .view-mode-button {
				  display: flex;
				  align-items: center;
				  gap: 6px;
				  padding: 8px 12px;
				  margin: 0;
				  border: none;
				  border-radius: 4px;
				  background: transparent;
				  color: var(--color-text-lighter);
				  transition: all 0.2s ease;
				  min-height: auto;
				  height: 36px;

				  &:hover {
					  background: var(--color-background-hover);
					  color: var(--color-main-text);
				  }

				  &.active {
					  background: var(--color-primary-element);
					  color: var(--color-primary-text);
				  }

				  // Override NcActionButton styles
					  :deep(.action-button) {
					  padding: 0;
					  margin: 0;
					  gap: 6px;
					  background: transparent !important;
					  border: none !important;
					  color: inherit !important;
				  }

				  :deep(.action-button__icon) {
					  margin: 0 !important;
					  width: 20px;
					  height: 20px;
					  color: inherit;
				  }

				  :deep(.action-button__input) {
					  display: none; // Hide the radio input
				  }
			  }

			  .view-mode-label {
				  font-size: 13px;
				  font-weight: 600;
				  white-space: nowrap;
			  }

			  // Header right area alignment
				  :deep(.header-bar__right) {
				  display: flex;
				  align-items: center;
				  gap: 12px;
			  }

			  .inquiry-list__container {
				  width: 100%;
				  padding-bottom: 14px;
				  box-sizing: border-box;
			  }

			  .inquiry-list__list {
				  display: flex;
				  flex-direction: column;
				  gap: 12px;
			  }

			  .inquiry-list__grid {
				  display: grid;
				  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
				  gap: 20px;
				  padding: 16px;
				  width: 100%;
				  box-sizing: border-box;
				  align-items: stretch;

				  .inquiry-item {
					  border: 1px solid var(--color-border);
					  border-radius: 12px;
					  padding: 10px;
					  background-color: var(--color-main-background);
					  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
					  transition: all 0.2s ease;
					  height: 100%;
					  min-height: 80px;
					  display: flex;
					  flex-direction: column;

					  &:hover {
						  transform: translateY(-2px);
						  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
					  }

					  .inquiry-item__header {
						  margin-bottom: 16px;
						  flex-grow: 1;

						  .inquiry-item__title {
							  font-size: 16px;
							  font-weight: 600;
							  line-height: 1.4;
							  margin-bottom: 12px;
							  color: var(--color-main-text);
							  word-break: break-word;
						  }
					  }

					  .inquiry-item__meta {
						  display: flex;
						  flex-wrap: wrap;
						  gap: 10px;
						  margin-bottom: 16px;
						  font-size: 13px;
						  color: var(--color-text-lighter);

						  .meta-item {
							  display: flex;
							  align-items: center;
							  gap: 6px;

							  .material-design-icon {
								  width: 16px;
								  height: 16px;
								  flex-shrink: 0;
							  }
						  }
					  }

					  .inquiry-item__actions {
						  margin-top: auto;
						  padding-top: 16px;
						  border-top: 1px solid var(--color-border-light);
						  display: flex;
						  justify-content: flex-end;
						  gap: 10px;
					  }
				  }

				  @media (max-width: 1400px) {
					  grid-template-columns: repeat(3, 1fr);
				  }

				  @media (max-width: 1024px) {
					  grid-template-columns: repeat(2, 1fr);
					  gap: 16px;
					  padding: 12px;
				  }

				  @media (max-width: 768px) {
					  grid-template-columns: 1fr;
					  gap: 12px;
					  padding: 8px;

					  .inquiry-item {
						  padding: 16px;
						  min-height: 160px;
					  }
				  }
			  }

			  .observer_section {
				  display: flex;
				  justify-content: center;
				  align-items: center;
				  padding: 20px 0;
				  grid-column: 1 / -1;
				  width: 100%;
			  }

			  .clickable_load_more {
				  cursor: pointer;
				  font-weight: 600;
				  text-align: center;
				  padding: 20px;
				  background-color: var(--color-background-dark);
				  border-radius: 12px;
				  margin: 0 16px;
				  color: var(--color-text-lighter);
				  transition: background-color 0.2s ease;

				  &:hover {
					  background-color: var(--color-background-darker);
					  color: var(--color-main-text);
				  }
			  }

			  .app-content {
				  width: 100%;

				  .app-content-wrapper {
					  width: 100%;
					  max-width: none;
				  }
			  }

			  @media (min-width: 1600px) {
				  .inquiry-list__grid {
					  grid-template-columns: repeat(4, 1fr);
				  }
			  }

			  .view-controls-compact {
				  display: flex;
				  align-items: center;
				  gap: 8px;

				  .main-mode-group {
					  display: flex;
					  gap: 4px;
					  background: var(--color-background-hover);
					  border-radius: 8px;
					  padding: 2px;
				  }

				  .main-mode {
					  display: flex;
					  align-items: center;
					  justify-content: center;
					  width: 36px;
					  height: 36px;
					  border-radius: 6px;
					  background: var(--color-background-dark);
					  color: var(--color-text-lighter);
					  cursor: pointer;
					  transition: all 0.2s ease;

					  &:hover {
						  background: var(--color-background-darker);
						  color: var(--color-main-text);
					  }

					  &.active {
						  background: var(--color-primary-element);
						  color: var(--color-primary-text);
					  }
				  }

				  .sub-mode-group {
					  display: flex;
					  gap: 2px;
					  margin-left: 4px;

					  NcActionButton {
						  width: 28px;
						  height: 28px;
						  background: var(--color-background-dark);
						  color: var(--color-text-lighter);
						  border-radius: 4px;

						  &.active {
							  background: var(--color-primary-element);
							  color: var(--color-primary-text);
						  }

						  &:hover {
							  background: var(--color-background-darker);
						  }

						  :deep(.action-button__icon) {
							  width: 16px;
							  height: 16px;
						  }
					  }
				  }
			  }

			  @media (max-width: 768px) {
				  .main-mode, .sub-mode-group NcActionButton {
					  width: 28px;
					  height: 28px;
				  }
			  }

			  // Responsive adjustments
		  @media (max-width: 768px) {
				  :deep(.header-bar__right) {
					  flex-wrap: wrap;
					  justify-content: flex-end;
					  gap: 8px;
				  }

				  .view-controls {
					  margin-right: 0;
					  order: 1;
				  }

				  .button-label,
				  .view-mode-label {
					  display: none; // Hide labels on mobile
				  }

				  .create-mode-button,
				  .view-mode-button {
					  padding: 8px 10px;
				  }
			  }

			  @media (max-width: 480px) {
				  .view-controls {
					  gap: 4px;
					  padding: 4px;
				  }

				  .create-mode-button,
				  .view-mode-button {
					  padding: 6px 8px;
					  height: 32px;
				  }

				  :deep(.action-button__icon) {
					  width: 18px !important;
					  height: 18px !important;
				  }
			  }
</style>
