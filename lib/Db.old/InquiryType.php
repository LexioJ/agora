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
 * @method         string getInquiryType()
 * @method         void setInquiryType(string $value)
 * @method         string getFamily()
 * @method         void setFamily(string $value)
 * @method         string getLabel()
 * @method         void setLabel(string $value)
 * @method         ?string getDescription()
 * @method         void setDescription(?string $value)
 * @method         ?array getFields()
 * @method         void setFields(?array $value)
 * @method         ?array getAllowedResponse()
 * @method         void setAllowedResponse(?array $value)
 * @method         ?array getAllowedTransformation()
 * @method         void setAllowedTransformation(?array $value)
 * @method         int getCreated()
 * @method         void setCreated(int $value)
 * @method         int getIsOption()
 * @method         void setIsOption(int $value)
 */

class InquiryType extends EntityWithUser implements JsonSerializable
{
    public const TABLE = 'agora_inq_type';

    // schema columns
    public $id = null;
    protected string $type = '';
    protected string $inquiryType = '';
    protected string $family = 'deliberative';
    protected string $label = '';
    protected string $icon = '';
    protected ?string $description = null;
    protected ?array $fields = null;
    protected ?array $allowedResponse = null;
    protected ?array $allowedTransformation = null;
    protected int $created = 0;
    protected int $isOption = 0;

    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('created', 'integer');
        $this->addType('isOption', 'integer');
        $this->addType('icon', 'string');
        $this->addType('family', 'string');
        $this->addType('description', 'string');
        $this->addType('fields', 'json');
        $this->addType('allowedResponse', 'json');
        $this->addType('allowedTransformation', 'json');
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
            'type' => $this->getType(),
            'inquiry_type' => $this->getInquiryType(),
            'family' => $this->getFamily(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'fields' => $this->getFields(),
            'allowed_response' => $this->getAllowedResponse(),
            'allowed_transformation' => $this->getAllowedTransformation(),
            'created' => $this->getCreated(),
            'isOption' => $this->getIsOption(),
        ];
    }
}
