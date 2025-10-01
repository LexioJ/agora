<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\OptionMiscSetting;
use OCA\Agora\Db\OptionMiscSettingMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class OptionMiscSettingService
{
    public function __construct(
        private OptionMiscSettingMapper $optionMiscSettingMapper
    ) {
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function find(int $id): OptionMiscSetting
    {
        return $this->optionMiscSettingMapper->find($id);
    }

    public function findByOptionId(int $optionId): array
    {
        return $this->optionMiscSettingMapper->findByOptionId($optionId);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByOptionIdAndKey(int $optionId, string $key): OptionMiscSetting
    {
        return $this->optionMiscSettingMapper->findByOptionIdAndKey($optionId, $key);
    }

    public function findByKey(string $key): array
    {
        return $this->optionMiscSettingMapper->findByKey($key);
    }

    public function getValue(int $optionId, string $key): ?string
    {
        return $this->optionMiscSettingMapper->getValue($optionId, $key);
    }

    public function setValue(int $optionId, string $key, ?string $value): OptionMiscSetting
    {
        return $this->optionMiscSettingMapper->setValue($optionId, $key, $value);
    }

    public function create(OptionMiscSetting $optionMiscSetting): OptionMiscSetting
    {
        return $this->optionMiscSettingMapper->insert($optionMiscSetting);
    }

    public function update(OptionMiscSetting $optionMiscSetting): OptionMiscSetting
    {
        return $this->optionMiscSettingMapper->update($optionMiscSetting);
    }

    public function delete(int $id): OptionMiscSetting
    {
        $optionMiscSetting = $this->find($id);
        return $this->optionMiscSettingMapper->delete($optionMiscSetting);
    }

    public function deleteByOptionId(int $optionId): int
    {
        return $this->optionMiscSettingMapper->deleteByOptionId($optionId);
    }

    public function deleteByOptionIds(array $optionIds): int
    {
        return $this->optionMiscSettingMapper->deleteByOptionIds($optionIds);
    }

    public function deleteByOptionIdAndKey(int $optionId, string $key): int
    {
        return $this->optionMiscSettingMapper->deleteByOptionIdAndKey($optionId, $key);
    }
}
