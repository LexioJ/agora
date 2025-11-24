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
 * @method         string getType()
 * @method         void setType(string $value)
 * @method         string getGroupType()
 * @method         void setGroupType(string $value)
 * @method         string getFamily()
 * @method         void setFamily(string $value)
 * @method         string getLabel()
 * @method         void setLabel(string $value)
 * @method         ?string getDescription()
 * @method         void setDescription(?string $value)
 * @method         ?array getFields()
 * @method         void setFields(?array $value)
 * @method         ?array getAllowedInquiryTypes()
 * @method         void setAllowedInquiryTypes(?array $value)
 * @method         int getCreated()
 * @method         void setCreated(int $value)
 */

class InquiryGroupType extends EntityWithUser implements JsonSerializable
{
    public const TABLE = 'agora_inq_group_type';

    // schema columns
    public $id = null;
    protected string $groupType = '';
    protected string $label = '';
    protected string $icon = '';
    protected string $family = 'deliberative';
    protected ?string $description = null;
    protected ?array $fields = null;
    protected ?array $allowedInquiryTypes = null;
    protected int $created = 0;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('created', 'integer');
        $this->addType('description', 'string');
        $this->addType('groupType', 'string');
        $this->addType('fields', 'json');
        $this->addType('family', 'string');
        $this->addType('allowedInquiryTypes', 'json');
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
            'groupType' => $this->getGroupType(),
            'label' => $this->getLabel(),
            'family' => $this->getFamily(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'fields' => $this->getFields(),
            'allowedInquiryTypes' => $this->getAllowedInquiryTypes(),
            'created' => $this->getCreated(),
        ];
    }
}
