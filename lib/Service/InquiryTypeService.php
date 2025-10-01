<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\InquiryType;
use OCA\Agora\Db\InquiryTypeMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class InquiryTypeService
{
    public function __construct(
        private InquiryTypeMapper $inquiryTypeMapper
    ) {
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function find(int $id): InquiryType
    {
        return $this->inquiryTypeMapper->find($id);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByType(string $type): InquiryType
    {
        return $this->inquiryTypeMapper->findByType($type);
    }

    public function findAll(): array
    {
        return $this->inquiryTypeMapper->findAll();
    }

    public function findByFamily(string $family): array
    {
        return $this->inquiryTypeMapper->findByFamily($family);
    }

    public function findByInquiryType(string $inquiryType): array
    {
        return $this->inquiryTypeMapper->findByInquiryType($inquiryType);
    }

    public function delete(int $id): InquiryType
    {
	    $inquiryType = $this->find($id);
	    return $this->inquiryTypeMapper->delete($inquiryType);
    }

    public function create(
	    string $type,
	    string $family,
	    string $icon,
	    string $label,
	    ?string $description = null,
	    ?array $fields = null,
	    ?array $allowedResponse = null
    ): InquiryType {
	    $inquiryType = new InquiryType();
	    $inquiryType->setType($type);
	    $inquiryType->setInquiryType($inquiryType);
	    $inquiryType->setFamily($family);
	    $inquiryType->setLabel($label);
	    $inquiryType->setIcon($icon);
	    $inquiryType->setDescription($description);
	    $inquiryType->setFields($fields);
	    $inquiryType->setAllowedResponse($allowedResponse);
	    $inquiryType->setCreated(time());

	    return $this->inquiryTypeMapper->insert($inquiryType);
    }

    public function update(
	    int $id,
	    string $type,
	    string $family,
	    string $icon,
	    string $label,
	    ?string $description = null,
	    ?array $fields = null,
	    ?array $allowedResponse = null
    ): InquiryType {
	    $inquiryType = $this->find($id);
	    $inquiryType->setType($type);
	    $inquiryType->setInquiryType($inquiryType);
	    $inquiryType->setFamily($family);
	    $inquiryType->setIcon($icon);
	    $inquiryType->setLabel($label);
	    $inquiryType->setDescription($description);
	    $inquiryType->setFields($fields);
	    $inquiryType->setAllowedResponse($allowedResponse);

	    return $this->inquiryTypeMapper->update($inquiryType);
    }
}
