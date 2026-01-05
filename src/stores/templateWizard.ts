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

	// Editable template data (Step 4 customization)
	editableData: TemplateContent | null

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

		editableData: null,

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
					return state.editableData !== null
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
			if (!state.selectedUseCase) {
				return state.templates
			}

			// Custom shows templates that don't match other use cases
			if (state.selectedUseCase === 'custom') {
				return state.templates.filter(t => {
					const useCase = t.use_case || ''
					return !useCase.startsWith('citizen_') &&
						   !useCase.startsWith('default_') &&
						   !useCase.startsWith('enterprise_') &&
						   !useCase.startsWith('business_') &&
						   !useCase.startsWith('education_') &&
						   !useCase.startsWith('research_')
				})
			}

			// Prefix-based matching for main use cases
			if (state.selectedUseCase === 'citizen_participation') {
				return state.templates.filter(t => {
					const useCase = t.use_case || ''
					return useCase.startsWith('citizen_') || useCase.startsWith('default_')
				})
			}

			if (state.selectedUseCase === 'enterprise') {
				return state.templates.filter(t => {
					const useCase = t.use_case || ''
					return useCase.startsWith('enterprise_') || useCase.startsWith('business_')
				})
			}

			if (state.selectedUseCase === 'education') {
				return state.templates.filter(t => {
					const useCase = t.use_case || ''
					return useCase.startsWith('education_') || useCase.startsWith('research_')
				})
			}

			return state.templates
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
			if (!this.editableData) {
				throw new Error('No editable data available')
			}
			if (!this.selectedLanguage) {
				throw new Error('No language selected')
			}

			this.importing = true
			this.importError = null
			this.currentStep = 'importing'

			try {
				const url = generateOcsUrl('/apps/agora/api/v1.0/templates/import-data')

				const response = await axios.post(url, {
					templateData: this.editableData,
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
				const nextStep = this.steps[currentIndex + 1]

				// Prepare editable data when entering preview step
				if (nextStep === 'preview' && this.selectedLanguage) {
					this.prepareEditableData()
				}

				this.currentStep = nextStep
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

		prepareEditableData() {
			const template = this.selectedTemplate?.content || this.customTemplate
			if (!template || !this.selectedLanguage) {
				Logger.error('Cannot prepare editable data: missing template or language')
				return
			}

			// Deep clone the template to avoid modifying the original
			const clonedTemplate = JSON.parse(JSON.stringify(template))

			// Extract text for selected language
			this.editableData = this.extractLanguageText(clonedTemplate, this.selectedLanguage)
			Logger.info('Prepared editable data:', this.editableData)
		},

		extractLanguageText(template: TemplateContent, language: string): TemplateContent {
			const extracted = { ...template }

			const sections = [
				'inquiry_families',
				'inquiry_types',
				'inquiry_statuses',
				'option_types',
				'inquiry_group_types',
				'categories',
				'locations',
			]

			sections.forEach(section => {
				if (extracted[section] && Array.isArray(extracted[section])) {
					extracted[section] = extracted[section].map((item: any) => {
						const extractedItem = { ...item }

						// Extract multi-language fields
						Object.keys(extractedItem).forEach(key => {
							const value = extractedItem[key]
							if (value && typeof value === 'object' && !Array.isArray(value)) {
								// Check if this is a multi-language object
								if (value[language] !== undefined) {
									extractedItem[key] = value[language]
								} else if (value.en !== undefined) {
									// Fallback to English
									extractedItem[key] = value.en
								}
							}
						})

						return extractedItem
					})
				}
			})

			return extracted
		},

		updateEditableItem(section: string, index: number, updatedItem: any) {
			if (!this.editableData || !this.editableData[section]) {
				return
			}
			this.editableData[section][index] = updatedItem
		},

		removeEditableItem(section: string, index: number) {
			if (!this.editableData || !this.editableData[section]) {
				return
			}
			this.editableData[section].splice(index, 1)
		},

		addEditableItem(section: string, item: any) {
			if (!this.editableData) {
				return
			}
			if (!this.editableData[section]) {
				this.editableData[section] = []
			}
			this.editableData[section].push(item)
		},

		reset() {
			this.currentStep = 'use-case'
			this.selectedUseCase = null
			this.selectedTemplate = null
			this.selectedLanguage = null
			this.customTemplate = null
			this.editableData = null
			this.importing = false
			this.importResult = null
			this.importError = null
		},
	},
})
