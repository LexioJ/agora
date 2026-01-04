<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Command\Db;

use OCA\Agora\Command\Command;
use OCA\Agora\Service\TemplateCatalog;
use OCA\Agora\Service\TemplateLoader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @psalm-api
 */
class LoadTemplate extends Command
{
	protected string $name = parent::NAME_PREFIX . 'db:load-template';
	protected string $description = 'Interactive wizard to load configuration data from a JSON template';
	protected array $operationHints = [
		'This command provides an interactive wizard to load templates.',
		'',
		'The wizard will guide you through:',
		' 1. Template validation and structure check',
		' 2. Language selection (if multiple available)',
		' 3. Preview of what will be imported',
		' 4. Confirmation before import',
		' 5. Detailed import results',
		'',
		'Templates contain embedded multi-language translations.',
		'You select ONE language to import into the database.',
		'',
		'Usage:',
		'  occ agora:db:load-template --list',
		'  occ agora:db:load-template --default',
		'  occ agora:db:load-template /path/to/template.json',
		'  occ agora:db:load-template --default --language=fr --yes',
		'',
		'Options:',
		'  --list             List all available templates',
		'  --language=<code>  Skip language selection',
		'  --yes, -y          Skip confirmation prompts',
		'',
		'NOTE: Run \'occ agora:db:clean-instance\' first if you want to',
		'replace existing configuration data.',
	];

	public function __construct(
		private TemplateLoader $templateLoader,
		private TemplateCatalog $templateCatalog,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		parent::configure();

		$this->addArgument(
			'template',
			InputArgument::OPTIONAL,
			'Path to the JSON template file'
		);

		$this->addOption(
			'default',
			'd',
			InputOption::VALUE_NONE,
			'Load the default citizen participation template'
		);

		$this->addOption(
			'language',
			'l',
			InputOption::VALUE_REQUIRED,
			'Language code to import (e.g., en, fr, de). If not specified, you will be prompted.'
		);

		$this->addOption(
			'yes',
			'y',
			InputOption::VALUE_NONE,
			'Skip confirmation prompts and proceed automatically'
		);

		$this->addOption(
			'list',
			null,
			InputOption::VALUE_NONE,
			'List all available templates in the catalog'
		);
	}

	protected function runCommands(): int
	{
		$templatePath = $this->input->getArgument('template');
		$useDefault = $this->input->getOption('default');
		$language = $this->input->getOption('language');
		$skipConfirmation = $this->input->getOption('yes');
		$listTemplates = $this->input->getOption('list');

		// If --list option is provided, show catalog and exit
		if ($listTemplates) {
			$this->displayTemplateCatalog();
			return 0;
		}

		$this->printSectionHeader('STEP 1: Template Loading');

		// Determine template path
		if ($useDefault) {
			$templatePath = __DIR__ . '/../../Templates/default_citizen_participation.json';
			$this->printInfo('📄 Using default citizen participation template');
		} elseif (empty($templatePath)) {
			$this->printError('Error: No template file specified.');
			$this->printInfo('Usage: occ agora:db:load-template <template-file>');
			$this->printInfo('   or: occ agora:db:load-template --default');
			return 1;
		}

		// Validate file exists
		if (!file_exists($templatePath)) {
			$this->printError("Error: Template file not found: {$templatePath}");
			return 1;
		}

		$this->printInfo("📂 Template file: {$templatePath}");
		$this->printNewLine();

		// STEP 2: Validate template structure
		$this->printSectionHeader('STEP 2: Template Validation');

		$validation = $this->validateTemplate($templatePath);
		if (!$validation['valid']) {
			$this->printError('❌ Template validation failed!');
			foreach ($validation['errors'] as $error) {
				$this->printError("   • {$error}");
			}
			return 1;
		}

		$template = $validation['template'];
		$this->printInfo('✅ Template structure is valid');
		$this->printInfo("   Name: {$template['template_info']['name']}");
		$this->printInfo("   Version: {$template['template_info']['version']}");
		$this->printInfo("   Author: {$template['template_info']['author']}");
		$this->printNewLine();

		// STEP 3: Language selection
		$this->printSectionHeader('STEP 3: Language Selection');

		$availableLanguages = $template['template_info']['available_languages'] ?? [];

		if (empty($language)) {
			if (empty($availableLanguages)) {
				$this->printError('❌ Template does not specify available_languages.');
				$this->printInfo('Please specify a language using --language=<code>');
				return 1;
			}

			// Prompt for language selection
			$helper = $this->getHelper('question');
			$question = new ChoiceQuestion(
				'🌐 Please select a language to import:',
				$availableLanguages,
				0 // default to first language
			);
			$question->setErrorMessage('Language %s is invalid.');

			$language = $helper->ask($this->input, $this->output, $question);
			$this->printNewLine();
		}

		// Validate language is available
		if (!empty($availableLanguages) && !in_array($language, $availableLanguages)) {
			$this->printError("❌ Language '{$language}' is not available in this template.");
			$this->printInfo('Available languages: ' . implode(', ', $availableLanguages));
			return 1;
		}

		$this->printInfo("✅ Selected language: {$language}");
		$this->printNewLine();

		// STEP 4: Preview what will be imported
		$this->printSectionHeader('STEP 4: Import Preview');

		$summary = $this->generateImportSummary($template, $language);
		$this->displayImportSummary($summary);
		$this->printNewLine();

		// STEP 5: Confirmation
		if (!$skipConfirmation) {
			$this->printSectionHeader('STEP 5: Confirmation');

			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion(
				'🚀 Proceed with import? (yes/no) [yes]: ',
				true
			);

			if (!$helper->ask($this->input, $this->output, $question)) {
				$this->printComment('Import cancelled by user.');
				return 0;
			}
			$this->printNewLine();
		}

		// STEP 6: Import
		$this->printSectionHeader('STEP 6: Importing Configuration');
		$this->printInfo('⏳ Loading template data...');
		$this->printNewLine();

		$messages = $this->templateLoader->loadTemplate($templatePath, $language);

		// STEP 7: Display results
		$this->printSectionHeader('STEP 7: Import Results');

		$results = $this->parseImportResults($messages);
		$this->displayImportResults($results);

		// Check if there were any errors
		if ($results['error_count'] > 0) {
			$this->printNewLine();
			$this->printError('❌ Import completed with errors!');
			$this->printInfo("   Successfully created: {$results['success_count']} items");
			$this->printInfo("   Failed: {$results['error_count']} items");
			return 1;
		}

		$this->printNewLine();
		$this->printInfo('✅ Template import completed successfully!');
		$this->printInfo("   Total items created: {$results['success_count']}");
		$this->printInfo("   Language: {$language}");
		return 0;
	}

	/**
	 * Validate template structure and content
	 */
	private function validateTemplate(string $templatePath): array
	{
		$errors = [];

		// Read and parse JSON
		$jsonContent = file_get_contents($templatePath);
		$template = json_decode($jsonContent, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return [
				'valid' => false,
				'errors' => ['Invalid JSON: ' . json_last_error_msg()],
				'template' => null,
			];
		}

		// Check required sections
		if (!isset($template['template_info'])) {
			$errors[] = 'Missing required section: template_info';
		} else {
			// Validate template_info fields
			if (!isset($template['template_info']['name'])) {
				$errors[] = 'template_info missing required field: name';
			}
			if (!isset($template['template_info']['version'])) {
				$errors[] = 'template_info missing required field: version';
			}
			if (!isset($template['template_info']['available_languages'])) {
				$errors[] = 'template_info missing required field: available_languages';
			} elseif (!is_array($template['template_info']['available_languages']) || empty($template['template_info']['available_languages'])) {
				$errors[] = 'available_languages must be a non-empty array';
			}
		}

		// Check that at least one content section exists
		$contentSections = ['inquiry_families', 'inquiry_types', 'inquiry_statuses', 'option_types', 'inquiry_group_types', 'categories', 'locations'];
		$hasContent = false;
		foreach ($contentSections as $section) {
			if (isset($template[$section]) && !empty($template[$section])) {
				$hasContent = true;
				break;
			}
		}

		if (!$hasContent) {
			$errors[] = 'Template must contain at least one content section (families, types, statuses, etc.)';
		}

		return [
			'valid' => empty($errors),
			'errors' => $errors,
			'template' => $template,
		];
	}

	/**
	 * Generate import summary
	 */
	private function generateImportSummary(array $template, string $language): array
	{
		$summary = [
			'template_name' => $template['template_info']['name'] ?? 'Unknown',
			'template_version' => $template['template_info']['version'] ?? 'Unknown',
			'language' => $language,
			'sections' => [],
		];

		$sections = [
			'inquiry_families' => 'Inquiry Families',
			'inquiry_types' => 'Inquiry Types',
			'inquiry_statuses' => 'Inquiry Statuses',
			'option_types' => 'Option Types',
			'inquiry_group_types' => 'Inquiry Group Types',
			'categories' => 'Categories',
			'locations' => 'Locations',
		];

		foreach ($sections as $key => $label) {
			if (isset($template[$key]) && !empty($template[$key])) {
				$items = [];
				foreach ($template[$key] as $item) {
					$itemLabel = $this->extractPreviewLabel($item, $language);
					$items[] = $itemLabel;
				}
				$summary['sections'][$label] = [
					'count' => count($template[$key]),
					'items' => $items,
				];
			}
		}

		return $summary;
	}

	/**
	 * Extract label for preview
	 */
	private function extractPreviewLabel(array $item, string $language): string
	{
		// Try to find label field
		$labelField = $item['label'] ?? null;

		if (is_array($labelField)) {
			// Multi-language field
			return $labelField[$language] ?? $labelField['en'] ?? reset($labelField) ?? 'Unknown';
		}

		if (is_string($labelField)) {
			return $labelField;
		}

		// Fallback to type/key fields
		return $item['family_type'] ?? $item['inquiry_type'] ?? $item['group_type'] ?? $item['option_type'] ?? $item['status_key'] ?? $item['category_key'] ?? $item['location_key'] ?? 'Unknown';
	}

	/**
	 * Display import summary
	 */
	private function displayImportSummary(array $summary): void
	{
		$this->printInfo("📊 Import Summary");
		$this->printInfo("────────────────────────────────────────────────────────");
		$this->printInfo("Template: {$summary['template_name']} v{$summary['template_version']}");
		$this->printInfo("Language: {$summary['language']}");
		$this->printNewLine();

		$totalItems = 0;
		foreach ($summary['sections'] as $sectionName => $sectionData) {
			$count = $sectionData['count'];
			$totalItems += $count;
			$this->printInfo("📁 {$sectionName}: {$count} items");

			// Show first 3 items as preview
			$previewCount = min(3, count($sectionData['items']));
			for ($i = 0; $i < $previewCount; $i++) {
				$prefix = ($i === $previewCount - 1 && $count > 3) ? '   └─ ' : '   ├─ ';
				$this->printComment($prefix . $sectionData['items'][$i]);
			}
			if ($count > 3) {
				$this->printComment("   └─ ... and " . ($count - 3) . " more");
			}
		}

		$this->printNewLine();
		$this->printInfo("────────────────────────────────────────────────────────");
		$this->printInfo("Total items to import: {$totalItems}");
	}

	/**
	 * Parse import results from messages
	 */
	private function parseImportResults(array $messages): array
	{
		$results = [
			'success_count' => 0,
			'error_count' => 0,
			'sections' => [],
			'errors' => [],
		];

		$currentSection = null;

		foreach ($messages as $message) {
			// Detect section headers
			if (str_contains($message, 'Loading inquiry families')) {
				$currentSection = 'Inquiry Families';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading inquiry types')) {
				$currentSection = 'Inquiry Types';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading inquiry statuses')) {
				$currentSection = 'Inquiry Statuses';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading option types')) {
				$currentSection = 'Option Types';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading inquiry group types')) {
				$currentSection = 'Inquiry Group Types';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading categories')) {
				$currentSection = 'Categories';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			} elseif (str_contains($message, 'Loading locations')) {
				$currentSection = 'Locations';
				$results['sections'][$currentSection] = ['created' => [], 'errors' => []];
			}

			// Parse creation messages
			if (str_starts_with($message, '  - Created ')) {
				$results['success_count']++;
				if ($currentSection) {
					$results['sections'][$currentSection]['created'][] = trim(substr($message, 12));
				}
			}

			// Parse error messages
			if (str_starts_with($message, '  - Error ')) {
				$results['error_count']++;
				$errorMsg = trim(substr($message, 10));
				$results['errors'][] = $errorMsg;
				if ($currentSection) {
					$results['sections'][$currentSection]['errors'][] = $errorMsg;
				}
			}
		}

		return $results;
	}

	/**
	 * Display import results
	 */
	private function displayImportResults(array $results): void
	{
		// Display successful creations
		if ($results['success_count'] > 0) {
			$this->printInfo("✅ Successfully Created ({$results['success_count']} items)");
			foreach ($results['sections'] as $sectionName => $sectionData) {
				if (!empty($sectionData['created'])) {
					$count = count($sectionData['created']);
					$this->printInfo("   • {$sectionName}: {$count} items");
				}
			}
		}

		// Display errors
		if ($results['error_count'] > 0) {
			$this->printNewLine();
			$this->printError("❌ Failed to Process ({$results['error_count']} items)");
			foreach ($results['errors'] as $error) {
				$this->printError("   • {$error}");
			}
		}
	}

	/**
	 * Print a section header
	 */
	private function printSectionHeader(string $title): void
	{
		$this->printNewLine();
		$this->printInfo("┌────────────────────────────────────────────────────────────────────┐");
		$this->printInfo("│  " . str_pad($title, 66) . "│");
		$this->printInfo("└────────────────────────────────────────────────────────────────────┘");
		$this->printNewLine();
	}

	/**
	 * Display the template catalog
	 */
	private function displayTemplateCatalog(): void
	{
		$this->printSectionHeader('TEMPLATE CATALOG');

		$templates = $this->templateCatalog->getAllTemplates();

		if (empty($templates)) {
			$this->printError('No templates found in the catalog.');
			return;
		}

		$this->printInfo("📚 Available Templates: " . count($templates));
		$this->printNewLine();

		foreach ($templates as $template) {
			$this->printInfo("┌─────────────────────────────────────────────────────────");
			$this->printInfo("│ 📄 " . $template['name']);
			$this->printInfo("├─────────────────────────────────────────────────────────");
			$this->printInfo("│   Version:     {$template['version']}");
			$this->printInfo("│   Author:      {$template['author']}");
			$this->printInfo("│   Use Case:    {$template['use_case']}");
			$this->printInfo("│   File:        {$template['filename']}");
			$this->printNewLine();

			// Description
			if (!empty($template['description'])) {
				$description = wordwrap($template['description'], 60);
				$lines = explode("\n", $description);
				$this->printInfo("│   Description:");
				foreach ($lines as $line) {
					$this->printComment("│     " . $line);
				}
				$this->printNewLine();
			}

			// Languages
			if (!empty($template['available_languages'])) {
				$languages = implode(', ', $template['available_languages']);
				$this->printInfo("│   🌐 Languages: {$languages}");
				$this->printNewLine();
			}

			// Content summary
			$this->printInfo("│   📊 Content Summary:");
			if ($template['counts']['inquiry_families'] > 0) {
				$this->printComment("│      • {$template['counts']['inquiry_families']} Inquiry Families");
			}
			if ($template['counts']['inquiry_types'] > 0) {
				$this->printComment("│      • {$template['counts']['inquiry_types']} Inquiry Types");
			}
			if ($template['counts']['inquiry_statuses'] > 0) {
				$this->printComment("│      • {$template['counts']['inquiry_statuses']} Inquiry Statuses");
			}
			if ($template['counts']['option_types'] > 0) {
				$this->printComment("│      • {$template['counts']['option_types']} Option Types");
			}
			if ($template['counts']['inquiry_group_types'] > 0) {
				$this->printComment("│      • {$template['counts']['inquiry_group_types']} Inquiry Group Types");
			}
			if ($template['counts']['categories'] > 0) {
				$this->printComment("│      • {$template['counts']['categories']} Categories");
			}
			if ($template['counts']['locations'] > 0) {
				$this->printComment("│      • {$template['counts']['locations']} Locations");
			}
			$this->printInfo("│      ───────────────────────────");
			$this->printInfo("│      Total: {$template['total_items']} items");

			$this->printInfo("└─────────────────────────────────────────────────────────");
			$this->printNewLine();
		}

		$this->printNewLine();
		$this->printInfo("💡 To load a template, use:");
		$this->printInfo("   occ agora:db:load-template <template-file> --language=<code>");
		$this->printInfo("   occ agora:db:load-template --default --language=en");
	}
}
