/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { Logger } from '../helpers/index.ts'

export interface TemplateMetadata {
	path: string
	filename: string
	name: string
	version: string
	description: string
	author: string
	use_case: string
	available_languages: string[]
	counts: {
		inquiry_families: number
		inquiry_types: number
		inquiry_statuses: number
		option_types: number
		inquiry_group_types: number
		categories: number
		locations: number
	}
	total_items: number
}

export interface TemplateContent {
	template_info: {
		name: string
		version: string
		description?: string
		author?: string
		use_case?: string
		available_languages: string[]
	}
	inquiry_families?: unknown[]
	inquiry_types?: unknown[]
	inquiry_statuses?: unknown[]
	categories?: unknown[]
	locations?: unknown[]
	option_types?: unknown[]
	inquiry_group_types?: unknown[]
}

export interface Template extends TemplateMetadata {
	content?: TemplateContent
}

export type WizardStep =
	| 'use-case'
	| 'template-selection'
	| 'language'
	| 'preview'
	| 'customization'
	| 'summary'
	| 'importing'
	| 'results'

export type UseCase =
	| 'citizen_participation'
	| 'enterprise'
	| 'education'
	| 'custom'

export interface ImportResult {
	success: string[]
	skipped: string[]
	failed: string[]
}

export interface WizardState {
	// Wizard flow
	isOpen: boolean
	currentStep: WizardStep
	steps: WizardStep[]

	// Template catalog
	templates: Template[]
	loadingTemplates: boolean

	// User selections
	selectedUseCase: UseCase | null
	selectedTemplate: Template | null
	selectedLanguage: string | null
	customTemplate: TemplateContent | null

	// Import process
	importing: boolean
	importResult: ImportResult | null
	importError: string | null

	// Database state
	isDatabaseEmpty: boolean | null
}

export const useTemplateWizardStore = defineStore('templateWizard', {
	state: (): WizardState => ({
		isOpen: false,
		currentStep: 'use-case',
		steps: ['use-case', 'template-selection', 'language', 'preview', 'summary', 'importing', 'results'],

		templates: [],
		loadingTemplates: false,

		selectedUseCase: null,
		selectedTemplate: null,
		selectedLanguage: null,
		customTemplate: null,

		importing: false,
		importResult: null,
		importError: null,

		isDatabaseEmpty: null,
	}),

	getters: {
		currentStepIndex: (state): number => state.steps.indexOf(state.currentStep),

		canGoNext: (state): boolean => {
			switch (state.currentStep) {
				case 'use-case':
					return state.selectedUseCase !== null
				case 'template-selection':
					return state.selectedTemplate !== null || state.customTemplate !== null
				case 'language':
					return state.selectedLanguage !== null
				case 'preview':
					return true
				case 'summary':
					return true
				default:
					return false
			}
		},

		canGoPrevious: (state): boolean => {
			return state.currentStepIndex > 0 && !state.importing
		},

		availableTemplates: (state): Template[] => {
			if (!state.selectedUseCase || state.selectedUseCase === 'custom') {
				return state.templates
			}
			return state.templates.filter(t => t.use_case === state.selectedUseCase)
		},

		availableLanguages: (state): string[] => {
			if (state.selectedTemplate?.content) {
				return state.selectedTemplate.content.template_info.available_languages || []
			}
			if (state.customTemplate) {
				return state.customTemplate.template_info.available_languages || []
			}
			return []
		},
	},

	actions: {
		async loadTemplates() {
			this.loadingTemplates = true
			try {
				const url = generateOcsUrl('/apps/agora/api/v1.0/templates')
				const response = await axios.get(url)
				this.templates = response.data.ocs.data
				Logger.info('Loaded templates:', this.templates)
			} catch (error) {
				Logger.error('Failed to load templates:', error)
				throw error
			} finally {
				this.loadingTemplates = false
			}
		},

		async loadTemplateDetails(identifier: string) {
			try {
				const url = generateOcsUrl(`/apps/agora/api/v1.0/templates/${identifier}`)
				const response = await axios.get(url)
				this.selectedTemplate = response.data.ocs.data
				Logger.info('Loaded template details:', this.selectedTemplate)
			} catch (error) {
				Logger.error('Failed to load template details:', error)
				throw error
			}
		},

		async checkDatabaseEmpty() {
			try {
				const url = generateOcsUrl('/apps/agora/api/v1.0/templates/check-empty')
				const response = await axios.get(url)
				this.isDatabaseEmpty = response.data.ocs.data.empty
				Logger.info('Database empty check:', this.isDatabaseEmpty)
			} catch (error) {
				Logger.error('Failed to check database:', error)
				throw error
			}
		},

		async importTemplate() {
			if (!this.selectedTemplate && !this.customTemplate) {
				throw new Error('No template selected')
			}
			if (!this.selectedLanguage) {
				throw new Error('No language selected')
			}

			this.importing = true
			this.importError = null
			this.currentStep = 'importing'

			try {
				const url = generateOcsUrl('/apps/agora/api/v1.0/templates/import')
				const templatePath = this.selectedTemplate?.path || ''

				const response = await axios.post(url, {
					templatePath,
					language: this.selectedLanguage,
				})

				this.importResult = response.data.ocs.data.results
				this.currentStep = 'results'
				Logger.info('Import completed:', this.importResult)
			} catch (error) {
				this.importError = error instanceof Error ? error.message : 'Unknown error'
				Logger.error('Import failed:', error)
				throw error
			} finally {
				this.importing = false
			}
		},

		async validateTemplate(template: TemplateContent) {
			try {
				const url = generateOcsUrl('/apps/agora/api/v1.0/templates/validate')
				const response = await axios.post(url, { template })
				return response.data.ocs.data
			} catch (error) {
				Logger.error('Template validation failed:', error)
				throw error
			}
		},

		openWizard() {
			this.isOpen = true
			this.currentStep = 'use-case'
			this.loadTemplates()
			this.checkDatabaseEmpty()
		},

		closeWizard() {
			this.isOpen = false
			this.reset()
		},

		nextStep() {
			const currentIndex = this.currentStepIndex
			if (currentIndex < this.steps.length - 1) {
				this.currentStep = this.steps[currentIndex + 1]
			}
		},

		previousStep() {
			const currentIndex = this.currentStepIndex
			if (currentIndex > 0) {
				this.currentStep = this.steps[currentIndex - 1]
			}
		},

		goToStep(step: WizardStep) {
			this.currentStep = step
		},

		selectUseCase(useCase: UseCase) {
			this.selectedUseCase = useCase
		},

		selectTemplate(template: Template) {
			this.selectedTemplate = template
			this.customTemplate = null

			// Load full template details if not already loaded
			if (!template.content) {
				this.loadTemplateDetails(template.name)
			}
		},

		selectLanguage(language: string) {
			this.selectedLanguage = language
		},

		uploadCustomTemplate(template: TemplateContent) {
			this.customTemplate = template
			this.selectedTemplate = null
		},

		reset() {
			this.currentStep = 'use-case'
			this.selectedUseCase = null
			this.selectedTemplate = null
			this.selectedLanguage = null
			this.customTemplate = null
			this.importing = false
			this.importResult = null
			this.importError = null
		},
	},
})
