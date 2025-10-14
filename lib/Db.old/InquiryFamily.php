<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Db;

use JsonSerializable;

/**
 * @psalm-suppress UnusedProperty
 * @method         int getId()
 * @method         void setId(int $value)
 * @method         string getInquiryType()
 * @method         void setInquiryType(string $value)
 * @method         string getLabel()
 * @method         void setLabel(string $value)
 * @method         ?string getDescription()
 * @method         void setDescription(?string $value)
 * @method         string getIcon()
 * @method         void setIcon(string $value)
 * @method         int getSortOrder()
 * @method         void setSortOrder(int $value)
 * @method         int getCreated()
 * @method         void setCreated(int $value)
 */
class InquiryFamily extends EntityWithUser implements JsonSerializable
{
    public const TABLE = 'agora_inq_families';

    // schema columns
    public $id = null;
    protected string $inquiryType = '';
    protected string $label = '';
    protected ?string $description = null;
    protected string $icon = '';
    protected int $sortOrder = 0;
    protected int $created = 0;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('sortOrder', 'integer');
        $this->addType('created', 'integer');
    }

    /**
     * @return array
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'inquiry_type' => $this->getInquiryType(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'sort_order' => $this->getSortOrder(),
            'created' => $this->getCreated(),
        ];
    }
}
