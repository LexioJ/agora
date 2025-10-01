<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\InquiryMiscSetting;
use OCA\Agora\Db\InquiryMiscSettingMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class InquiryMiscSettingService
{
    public function __construct(
        private InquiryMiscSettingMapper $inquiryMiscSettingMapper
    ) {
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function find(int $id): InquiryMiscSetting
    {
        return $this->inquiryMiscSettingMapper->find($id);
    }

    public function findByInquiryId(int $inquiryId): array
    {
        return $this->inquiryMiscSettingMapper->findByInquiryId($inquiryId);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByInquiryIdAndKey(int $inquiryId, string $key): InquiryMiscSetting
    {
        return $this->inquiryMiscSettingMapper->findByInquiryIdAndKey($inquiryId, $key);
    }

    public function findByKey(string $key): array
    {
        return $this->inquiryMiscSettingMapper->findByKey($key);
    }

    public function getValue(int $inquiryId, string $key): ?string
    {
        return $this->inquiryMiscSettingMapper->getValue($inquiryId, $key);
    }

    public function setValue(int $inquiryId, string $key, ?string $value): InquiryMiscSetting
    {
        return $this->inquiryMiscSettingMapper->setValue($inquiryId, $key, $value);
    }

    public function create(InquiryMiscSetting $inquiryMiscSetting): InquiryMiscSetting
    {
        return $this->inquiryMiscSettingMapper->insert($inquiryMiscSetting);
    }

    public function update(InquiryMiscSetting $inquiryMiscSetting): InquiryMiscSetting
    {
        return $this->inquiryMiscSettingMapper->update($inquiryMiscSetting);
    }

    public function delete(int $id): InquiryMiscSetting
    {
        $inquiryMiscSetting = $this->find($id);
        return $this->inquiryMiscSettingMapper->delete($inquiryMiscSetting);
    }

    public function deleteByInquiryId(int $inquiryId): int
    {
        return $this->inquiryMiscSettingMapper->deleteByInquiryId($inquiryId);
    }

    public function deleteByInquiryIdAndKey(int $inquiryId, string $key): int
    {
        return $this->inquiryMiscSettingMapper->deleteByInquiryIdAndKey($inquiryId, $key);
    }
}
