<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\InquiryFamily;
use OCA\Agora\Db\InquiryFamilyMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class InquiryFamilyService
{
    public function __construct(
        private InquiryFamilyMapper $inquiryFamilyMapper
    ) {
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function find(int $id): InquiryFamily
    {
        return $this->inquiryFamilyMapper->find($id);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByInquiryType(string $inquiryType): InquiryFamily
    {
        return $this->inquiryFamilyMapper->findByInquiryType($inquiryType);
    }

    public function findAll(): array
    {
        return $this->inquiryFamilyMapper->findAll();
    }

    public function findAllSorted(): array
    {
        return $this->inquiryFamilyMapper->findAllSorted();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->inquiryFamilyMapper->findBySearchTerm($searchTerm);
    }

    public function inquiryTypeExists(string $inquiryType): bool
    {
        return $this->inquiryFamilyMapper->inquiryTypeExists($inquiryType);
    }

    public function getMaxSortOrder(): int
    {
        return $this->inquiryFamilyMapper->getMaxSortOrder();
    }

    public function create(
        string $inquiryType,
        string $label,
        ?string $description = null,
        string $icon = '',
        ?int $sortOrder = null
    ): InquiryFamily {
        if ($this->inquiryFamilyMapper->inquiryTypeExists($inquiryType)) {
            throw new \InvalidArgumentException('Inquiry type already exists');
        }

        $inquiryFamily = new InquiryFamily();
        $inquiryFamily->setInquiry($inquiryType);
        $inquiryFamily->setLabel($label);
        $inquiryFamily->setDescription($description);
        $inquiryFamily->setIcon($icon);
        
        if ($sortOrder === null) {
            $sortOrder = $this->getMaxSortOrder() + 1;
        }
        $inquiryFamily->setSortOrder($sortOrder);
        
        $inquiryFamily->setCreated(time());

        return $this->inquiryFamilyMapper->insert($inquiryFamily);
    }

    public function update(
        int $id,
        string $inquiryType,
        string $label,
        ?string $description = null,
        string $icon = '',
        ?int $sortOrder = null
    ): InquiryFamily {
        $inquiryFamily = $this->find($id);
        
        // Check if inquiry type already exists for other records
        if ($inquiryFamily->getInquiryType() !== $inquiryType && 
            $this->inquiryFamilyMapper->inquiryTypeExists($inquiryType)) {
            throw new \InvalidArgumentException('Inquiry type already exists');
        }

        $inquiryFamily->setInquiryType($inquiryType);
        $inquiryFamily->setLabel($label);
        $inquiryFamily->setDescription($description);
        $inquiryFamily->setIcon($icon);
        
        if ($sortOrder !== null) {
            $inquiryFamily->setSortOrder($sortOrder);
        }

        return $this->inquiryFamilyMapper->update($inquiryFamily);
    }

    public function updateSortOrders(array $sortOrders): void
    {
        $this->inquiryFamilyMapper->updateSortOrders($sortOrders);
    }

    public function delete(int $id): InquiryFamily
    {
        $inquiryFamily = $this->find($id);
        return $this->inquiryFamilyMapper->delete($inquiryFamily);
    }

    public function deleteByInquiryType(string $inquiryType): InquiryFamily
    {
        $inquiryFamily = $this->findByInquiryType($inquiryType);
        return $this->inquiryFamilyMapper->delete($inquiryFamily);
    }
}
