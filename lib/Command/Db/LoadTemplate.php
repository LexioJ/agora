<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Command\Db;

use OCA\Agora\Command\Command;
use OCA\Agora\Service\TemplateLoader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @psalm-api
 */
class LoadTemplate extends Command
{
	protected string $name = parent::NAME_PREFIX . 'db:load-template';
	protected string $description = 'Load configuration data from a JSON template with language selection';
	protected array $operationHints = [
		'This command loads configuration data from a JSON template file.',
		'',
		'Templates contain embedded multi-language translations.',
		'You must select ONE language to import into the database.',
		'',
		'Templates define:',
		' - Inquiry families (deliberative, legislative, etc.)',
		' - Inquiry types (proposals, debates, petitions, etc.)',
		' - Inquiry statuses',
		' - Option types',
		' - Group types',
		' - Categories',
		' - Locations',
		'',
		'Usage:',
		' - Specify a template file path and language, or',
		' - Use --default to load the built-in template',
		'',
		'Examples:',
		'  occ agora:db:load-template --default --language=en',
		'  occ agora:db:load-template /path/to/template.json --language=fr',
		'  occ agora:db:load-template --default  (will prompt for language)',
		'',
		'NOTE: Run \'occ agora:db:clean-instance\' first if you want to',
		'replace existing configuration data.',
	];

	public function __construct(
		private TemplateLoader $templateLoader,
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
	}

	protected function runCommands(): int
	{
		$templatePath = $this->input->getArgument('template');
		$useDefault = $this->input->getOption('default');
		$language = $this->input->getOption('language');

		// Determine template path
		if ($useDefault) {
			$templatePath = __DIR__ . '/../../Templates/default_citizen_participation.json';
			$this->printInfo('Using default citizen participation template');
		} elseif (empty($templatePath)) {
			$this->printError('Error: No template file specified.');
			$this->printInfo('Usage: occ agora:db:load-template <template-file> --language=<code>');
			$this->printInfo('   or: occ agora:db:load-template --default --language=<code>');
			return 1;
		}

		// Validate file exists
		if (!file_exists($templatePath)) {
			$this->printError("Error: Template file not found: {$templatePath}");
			return 1;
		}

		// Read template to get available languages
		$templateContent = file_get_contents($templatePath);
		$template = json_decode($templateContent, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->printError('Error: Invalid JSON in template file: ' . json_last_error_msg());
			return 1;
		}

		$availableLanguages = $template['template_info']['available_languages'] ?? [];

		// If language not specified, prompt for it
		if (empty($language)) {
			if (empty($availableLanguages)) {
				$this->printError('Error: Template does not specify available_languages.');
				$this->printInfo('Please specify a language using --language=<code>');
				return 1;
			}

			// Prompt for language selection
			$helper = $this->getHelper('question');
			$question = new ChoiceQuestion(
				'Please select a language to import:',
				$availableLanguages,
				0 // default to first language
			);
			$question->setErrorMessage('Language %s is invalid.');

			$language = $helper->ask($this->input, $this->output, $question);
			$this->printNewLine();
		}

		// Validate language is available
		if (!empty($availableLanguages) && !in_array($language, $availableLanguages)) {
			$this->printError("Error: Language '{$language}' is not available in this template.");
			$this->printInfo('Available languages: ' . implode(', ', $availableLanguages));
			return 1;
		}

		$this->printNewLine();
		$this->printInfo("Loading template from: {$templatePath}");
		$this->printInfo("Selected language: {$language}");
		$this->printNewLine();

		// Load the template with the selected language
		$messages = $this->templateLoader->loadTemplate($templatePath, $language);

		// Print all messages
		foreach ($messages as $message) {
			if (str_starts_with($message, 'Error:')) {
				$this->printError($message);
			} elseif (str_starts_with($message, 'Warning:')) {
				$this->printComment($message);
			} else {
				$this->printInfo($message);
			}
		}

		$this->printNewLine();

		// Check if there were any errors
		$hasErrors = false;
		foreach ($messages as $message) {
			if (str_starts_with($message, 'Error:')) {
				$hasErrors = true;
				break;
			}
		}

		if ($hasErrors) {
			$this->printError('Template loading completed with errors.');
			return 1;
		}

		$this->printInfo('Template loading completed successfully!');
		$this->printInfo("All configuration data has been imported in {$language}.");
		return 0;
	}
}
