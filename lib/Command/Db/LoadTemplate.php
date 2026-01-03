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

/**
 * @psalm-api
 */
class LoadTemplate extends Command
{
	protected string $name = parent::NAME_PREFIX . 'db:load-template';
	protected string $description = 'Load configuration data from a JSON template';
	protected array $operationHints = [
		'This command loads configuration data from a JSON template file.',
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
		'Template files use i18n keys that reference Transifex translations.',
		'',
		'Usage:',
		' - Specify a template file path, or',
		' - Use --default to load the built-in citizen participation template',
		'',
		'Example:',
		'  occ agora:db:load-template /path/to/template.json',
		'  occ agora:db:load-template --default',
		'',
		'NOTE: Make sure to run \'occ agora:db:clean-instance\' first if you',
		'want to replace existing configuration data.',
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
	}

	protected function runCommands(): int
	{
		$templatePath = $this->input->getArgument('template');
		$useDefault = $this->input->getOption('default');

		if ($useDefault) {
			// Use the built-in default template
			$templatePath = __DIR__ . '/../../Templates/default_citizen_participation.json';
			$this->printInfo('Using default citizen participation template');
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

		$this->printNewLine();
		$this->printInfo("Loading template from: {$templatePath}");
		$this->printNewLine();

		// Load the template
		$messages = $this->templateLoader->loadTemplate($templatePath);

		// Print all messages
		foreach ($messages as $message) {
			if (str_starts_with($message, 'Error:')) {
				$this->printError($message);
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
		return 0;
	}
}
