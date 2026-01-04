<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\Category;
use OCA\Agora\Db\CategoryMapper;
use OCA\Agora\Db\InquiryFamily;
use OCA\Agora\Db\InquiryFamilyMapper;
use OCA\Agora\Db\InquiryGroupType;
use OCA\Agora\Db\InquiryGroupTypeMapper;
use OCA\Agora\Db\InquiryOptionType;
use OCA\Agora\Db\InquiryOptionTypeMapper;
use OCA\Agora\Db\InquiryStatus;
use OCA\Agora\Db\InquiryStatusMapper;
use OCA\Agora\Db\InquiryType;
use OCA\Agora\Db\InquiryTypeMapper;
use OCA\Agora\Db\Location;
use OCA\Agora\Db\LocationMapper;
use Psr\Log\LoggerInterface;

class TemplateLoader
{
	public function __construct(
		private CategoryMapper $categoryMapper,
		private InquiryFamilyMapper $inquiryFamilyMapper,
		private InquiryGroupTypeMapper $inquiryGroupTypeMapper,
		private InquiryOptionTypeMapper $inquiryOptionTypeMapper,
		private InquiryStatusMapper $inquiryStatusMapper,
		private InquiryTypeMapper $inquiryTypeMapper,
		private LocationMapper $locationMapper,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Load a template from a JSON file with a specific language
	 *
	 * @param string $templatePath Path to the JSON template file
	 * @param string $language Language code to extract from multi-language fields (e.g., 'en', 'fr', 'de')
	 * @return array Messages about the loading process
	 */
	public function loadTemplate(string $templatePath, string $language = 'en'): array
	{
		$messages = [];

		// Validate file exists
		if (!file_exists($templatePath)) {
			$messages[] = "Error: Template file not found: {$templatePath}";
			return $messages;
		}

		// Read and parse JSON
		$jsonContent = file_get_contents($templatePath);
		$template = json_decode($jsonContent, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$messages[] = 'Error: Invalid JSON in template file: ' . json_last_error_msg();
			return $messages;
		}

		// Validate template structure
		if (!isset($template['template_info'])) {
			$messages[] = 'Error: Template missing required "template_info" section';
			return $messages;
		}

		// Check if the requested language is available
		$availableLanguages = $template['template_info']['available_languages'] ?? [];
		if (!empty($availableLanguages) && !in_array($language, $availableLanguages)) {
			$messages[] = "Warning: Language '{$language}' not listed in available_languages. Attempting to load anyway...";
		}

		$messages[] = 'Loading template: ' . ($template['template_info']['name'] ?? 'unknown');
		$messages[] = 'Description: ' . ($template['template_info']['description'] ?? 'No description');
		$messages[] = "Language: {$language}";

		// Load each section
		try {
			if (isset($template['inquiry_families'])) {
				$messages = array_merge($messages, $this->loadInquiryFamilies($template['inquiry_families'], $language));
			}

			if (isset($template['inquiry_types'])) {
				$messages = array_merge($messages, $this->loadInquiryTypes($template['inquiry_types'], $language));
			}

			if (isset($template['inquiry_statuses'])) {
				$messages = array_merge($messages, $this->loadInquiryStatuses($template['inquiry_statuses'], $language));
			}

			if (isset($template['option_types'])) {
				$messages = array_merge($messages, $this->loadOptionTypes($template['option_types'], $language));
			}

			if (isset($template['inquiry_group_types'])) {
				$messages = array_merge($messages, $this->loadInquiryGroupTypes($template['inquiry_group_types'], $language));
			}

			if (isset($template['categories'])) {
				$messages = array_merge($messages, $this->loadCategories($template['categories'], $language));
			}

			if (isset($template['locations'])) {
				$messages = array_merge($messages, $this->loadLocations($template['locations'], $language));
			}

			$messages[] = 'Template loaded successfully!';
		} catch (\Exception $e) {
			$messages[] = 'Error loading template: ' . $e->getMessage();
			$this->logger->error('Template loading failed', ['exception' => $e]);
		}

		return $messages;
	}

	/**
	 * Load inquiry families from template
	 */
	private function loadInquiryFamilies(array $families, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry families...';

		foreach ($families as $familyData) {
			try {
				$family = new InquiryFamily();
				$family->setFamilyType($familyData['family_type']);
				$family->setLabel($this->extractText($familyData['label'] ?? '', $language));
				$family->setDescription($this->extractText($familyData['description'] ?? '', $language));
				$family->setIcon($familyData['icon'] ?? '');
				$family->setSortOrder($familyData['sort_order'] ?? 0);
				$family->setCreated(time());

				$this->inquiryFamilyMapper->insert($family);
				$messages[] = "  - Created family: {$familyData['family_type']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating family {$familyData['family_type']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load inquiry types from template
	 */
	private function loadInquiryTypes(array $types, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry types...';

		foreach ($types as $typeData) {
			try {
				$type = new InquiryType();
				$type->setInquiryType($typeData['inquiry_type']);
				$type->setFamily($typeData['family'] ?? '');
				$type->setIcon($typeData['icon'] ?? '');
				$type->setLabel($this->extractText($typeData['label'] ?? '', $language));
				$type->setDescription($this->extractText($typeData['description'] ?? '', $language));
				$type->setIsRoot($typeData['is_root'] ?? false);

				// Handle allowed_response (could be array or null)
				if (isset($typeData['allowed_response']) && is_array($typeData['allowed_response'])) {
					$type->setAllowedResponse($typeData['allowed_response']);
				} else {
					$type->setAllowedResponse(null);
				}

				// Handle allowed_transformation
				if (isset($typeData['allowed_transformation']) && is_array($typeData['allowed_transformation'])) {
					$type->setAllowedTransformation($typeData['allowed_transformation']);
				} else {
					$type->setAllowedTransformation(null);
				}

				// Handle fields if present
				if (isset($typeData['fields']) && is_array($typeData['fields'])) {
					$type->setFields($typeData['fields']);
				}

				$type->setCreated(time());

				$this->inquiryTypeMapper->insert($type);
				$messages[] = "  - Created type: {$typeData['inquiry_type']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating type {$typeData['inquiry_type']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load inquiry statuses from template
	 */
	private function loadInquiryStatuses(array $statuses, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry statuses...';

		foreach ($statuses as $statusData) {
			try {
				$status = new InquiryStatus();
				$status->setInquiryType($statusData['inquiry_type']);
				$status->setStatusKey($statusData['status_key']);
				$status->setLabel($this->extractText($statusData['label'] ?? '', $language));
				$status->setDescription($this->extractText($statusData['description'] ?? '', $language));
				$status->setIsFinal($statusData['is_final'] ?? false);
				$status->setIcon($statusData['icon'] ?? '');
				$status->setSortOrder($statusData['sort_order'] ?? 0);
				$status->setCreated(time());

				$this->inquiryStatusMapper->insert($status);
				$messages[] = "  - Created status: {$statusData['status_key']} for {$statusData['inquiry_type']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating status {$statusData['status_key']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load option types from template
	 */
	private function loadOptionTypes(array $optionTypes, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading option types...';

		foreach ($optionTypes as $optionTypeData) {
			try {
				$optionType = new InquiryOptionType();
				$optionType->setFamily($optionTypeData['family'] ?? '');
				$optionType->setOptionType($optionTypeData['option_type']);
				$optionType->setIcon($optionTypeData['icon'] ?? '');
				$optionType->setLabel($this->extractText($optionTypeData['label'] ?? '', $language));
				$optionType->setDescription($this->extractText($optionTypeData['description'] ?? '', $language));

				// Handle allowed_response
				if (isset($optionTypeData['allowed_response']) && is_array($optionTypeData['allowed_response'])) {
					$optionType->setAllowedResponse($optionTypeData['allowed_response']);
				} else {
					$optionType->setAllowedResponse(null);
				}

				// Handle fields if present
				if (isset($optionTypeData['fields']) && is_array($optionTypeData['fields'])) {
					$optionType->setFields($optionTypeData['fields']);
				}

				$optionType->setCreated(time());

				$this->inquiryOptionTypeMapper->insert($optionType);
				$messages[] = "  - Created option type: {$optionTypeData['option_type']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating option type {$optionTypeData['option_type']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load inquiry group types from template
	 */
	private function loadInquiryGroupTypes(array $groupTypes, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry group types...';

		foreach ($groupTypes as $groupTypeData) {
			try {
				$groupType = new InquiryGroupType();
				$groupType->setGroupType($groupTypeData['group_type']);
				$groupType->setLabel($this->extractText($groupTypeData['label'] ?? '', $language));
				$groupType->setDescription($this->extractText($groupTypeData['description'] ?? '', $language));
				$groupType->setIcon($groupTypeData['icon'] ?? '');
				$groupType->setSortOrder($groupTypeData['sort_order'] ?? 0);
				$groupType->setCreated(time());

				$this->inquiryGroupTypeMapper->insert($groupType);
				$messages[] = "  - Created group type: {$groupTypeData['group_type']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating group type {$groupTypeData['group_type']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load categories from template
	 */
	private function loadCategories(array $categories, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading categories...';

		foreach ($categories as $categoryData) {
			try {
				$category = new Category();
				$categoryName = $this->extractText($categoryData['label'] ?? $categoryData['category_key'] ?? '', $language);
				$category->setName($categoryName);
				$category->setParentId($categoryData['parent_id'] ?? 0);

				$this->categoryMapper->insert($category);
				$messages[] = "  - Created category: {$categoryData['category_key']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating category {$categoryData['category_key']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Load locations from template
	 */
	private function loadLocations(array $locations, string $language): array
	{
		$messages = [];
		$messages[] = 'Loading locations...';

		foreach ($locations as $locationData) {
			try {
				$location = new Location();
				$locationName = $this->extractText($locationData['label'] ?? $locationData['location_key'] ?? '', $language);
				$location->setName($locationName);
				$location->setParentId($locationData['parent_id'] ?? 0);

				$this->locationMapper->insert($location);
				$messages[] = "  - Created location: {$locationData['location_key']}";
			} catch (\Exception $e) {
				$messages[] = "  - Error creating location {$locationData['location_key']}: " . $e->getMessage();
			}
		}

		return $messages;
	}

	/**
	 * Extract text from a multi-language field or return as-is if it's a plain string
	 *
	 * @param mixed $field The field value (can be string or array with language keys)
	 * @param string $language The language code to extract
	 * @return string The extracted text
	 */
	private function extractText($field, string $language): string
	{
		// If it's an array, extract the language-specific text
		if (is_array($field)) {
			// Try to get the requested language
			if (isset($field[$language])) {
				return $field[$language];
			}

			// Fallback to English if available
			if (isset($field['en'])) {
				return $field['en'];
			}

			// Fallback to the first available language
			if (!empty($field)) {
				return reset($field);
			}

			return '';
		}

		// If it's already a string, return as-is
		return (string)$field;
	}
}
