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
use OCP\IL10N;
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
		private IL10N $l10n,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Load a template from a JSON file
	 *
	 * @param string $templatePath Path to the JSON template file
	 * @return array Messages about the loading process
	 */
	public function loadTemplate(string $templatePath): array
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

		$messages[] = 'Loading template: ' . ($template['template_info']['name'] ?? 'unknown');
		$messages[] = 'Description: ' . ($template['template_info']['description'] ?? 'No description');

		// Load each section
		try {
			if (isset($template['inquiry_families'])) {
				$messages = array_merge($messages, $this->loadInquiryFamilies($template['inquiry_families']));
			}

			if (isset($template['inquiry_types'])) {
				$messages = array_merge($messages, $this->loadInquiryTypes($template['inquiry_types']));
			}

			if (isset($template['inquiry_statuses'])) {
				$messages = array_merge($messages, $this->loadInquiryStatuses($template['inquiry_statuses']));
			}

			if (isset($template['option_types'])) {
				$messages = array_merge($messages, $this->loadOptionTypes($template['option_types']));
			}

			if (isset($template['inquiry_group_types'])) {
				$messages = array_merge($messages, $this->loadInquiryGroupTypes($template['inquiry_group_types']));
			}

			if (isset($template['categories'])) {
				$messages = array_merge($messages, $this->loadCategories($template['categories']));
			}

			if (isset($template['locations'])) {
				$messages = array_merge($messages, $this->loadLocations($template['locations']));
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
	private function loadInquiryFamilies(array $families): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry families...';

		foreach ($families as $familyData) {
			try {
				$family = new InquiryFamily();
				$family->setFamilyType($familyData['family_type']);
				$family->setLabel($this->resolveI18nKey($familyData['label_key'] ?? '', $familyData['label'] ?? ''));
				$family->setDescription($this->resolveI18nKey($familyData['description_key'] ?? '', $familyData['description'] ?? ''));
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
	private function loadInquiryTypes(array $types): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry types...';

		foreach ($types as $typeData) {
			try {
				$type = new InquiryType();
				$type->setInquiryType($typeData['inquiry_type']);
				$type->setFamily($typeData['family'] ?? '');
				$type->setIcon($typeData['icon'] ?? '');
				$type->setLabel($this->resolveI18nKey($typeData['label_key'] ?? '', $typeData['label'] ?? ''));
				$type->setDescription($this->resolveI18nKey($typeData['description_key'] ?? '', $typeData['description'] ?? ''));
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
	private function loadInquiryStatuses(array $statuses): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry statuses...';

		foreach ($statuses as $statusData) {
			try {
				$status = new InquiryStatus();
				$status->setInquiryType($statusData['inquiry_type']);
				$status->setStatusKey($statusData['status_key']);
				$status->setLabel($this->resolveI18nKey($statusData['label_key'] ?? '', $statusData['label'] ?? ''));
				$status->setDescription($this->resolveI18nKey($statusData['description_key'] ?? '', $statusData['description'] ?? ''));
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
	private function loadOptionTypes(array $optionTypes): array
	{
		$messages = [];
		$messages[] = 'Loading option types...';

		foreach ($optionTypes as $optionTypeData) {
			try {
				$optionType = new InquiryOptionType();
				$optionType->setFamily($optionTypeData['family'] ?? '');
				$optionType->setOptionType($optionTypeData['option_type']);
				$optionType->setIcon($optionTypeData['icon'] ?? '');
				$optionType->setLabel($this->resolveI18nKey($optionTypeData['label_key'] ?? '', $optionTypeData['label'] ?? ''));
				$optionType->setDescription($this->resolveI18nKey($optionTypeData['description_key'] ?? '', $optionTypeData['description'] ?? ''));

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
	private function loadInquiryGroupTypes(array $groupTypes): array
	{
		$messages = [];
		$messages[] = 'Loading inquiry group types...';

		foreach ($groupTypes as $groupTypeData) {
			try {
				$groupType = new InquiryGroupType();
				$groupType->setGroupType($groupTypeData['group_type']);
				$groupType->setLabel($this->resolveI18nKey($groupTypeData['label_key'] ?? '', $groupTypeData['label'] ?? ''));
				$groupType->setDescription($this->resolveI18nKey($groupTypeData['description_key'] ?? '', $groupTypeData['description'] ?? ''));
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
	private function loadCategories(array $categories): array
	{
		$messages = [];
		$messages[] = 'Loading categories...';

		foreach ($categories as $categoryData) {
			try {
				$category = new Category();
				$categoryName = $this->resolveI18nKey($categoryData['label_key'] ?? '', $categoryData['label'] ?? $categoryData['category_key']);
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
	private function loadLocations(array $locations): array
	{
		$messages = [];
		$messages[] = 'Loading locations...';

		foreach ($locations as $locationData) {
			try {
				$location = new Location();
				$locationName = $this->resolveI18nKey($locationData['label_key'] ?? '', $locationData['label'] ?? $locationData['location_key']);
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
	 * Resolve i18n key to translated string, with fallback to default value
	 *
	 * @param string $key The i18n key
	 * @param string $fallback Fallback value if translation not found
	 * @return string The translated string or fallback
	 */
	private function resolveI18nKey(string $key, string $fallback = ''): string
	{
		if (empty($key)) {
			return $fallback;
		}

		// Try to get translation
		$translated = $this->l10n->t($key);

		// If translation is the same as the key, it means no translation was found
		// In that case, use the fallback
		if ($translated === $key && !empty($fallback)) {
			return $fallback;
		}

		return $translated;
	}
}
