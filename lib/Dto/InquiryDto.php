<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Dto;

use JsonSerializable;
use OCA\Agora\Entity\InquiryType;
use OCA\Agora\Model\User;

class InquiryDto implements JsonSerializable
{
    public function __construct(
        public string $title,
        public string $type,
        public string $ownedGroup,
        public ?string $description = '',
        public ?int $parentId = 0,
        public ?int $locationId = 0,
        public ?int $categoryId = 0,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->title)) {
            throw new \InvalidArgumentException("Title cannot be empty");
        }

    }

    public static function fromRequestData(array $data): self
    {
        return new self(
            $data['title'],
            $data['type'],
            $data['description'] ?? '',
            $data['parentId'] ?? 0,
            $data['locationId'] ?? 0,
            $data['categoryId'] ?? 0,
            $data['ownedGroup'] ?? '',
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type->value,
            'description' => $this->description,
            'parentId' => $this->parentId,
            'locationId' => $this->locationId,
            'categoryId' => $this->categoryId,
            'ownedGroup' => $this->ownedGroup,
        ];
    }

    public function toServiceArray(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'ownedGroup' => $this->ownedGroup,
            'location_id' => $this->locationId,
            'category_id' => $this->categoryId,
        ];
    }
}
