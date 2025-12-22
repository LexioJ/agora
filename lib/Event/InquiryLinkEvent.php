<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Event;

use OCA\Agora\Db\InquiryLink;

/**
 * @psalm-suppress UnusedProperty
 */
abstract class InquiryLinkEvent extends BaseEvent
{
    public const ADD = 'inquiryLink_add';
    public const DELETE = 'inquiryLink_delete';
    public const RESTORE = 'inquiryLink_restore';

    public function __construct(
        protected InquiryLink $inquiryLink,
    ) {
        parent::__construct($inquiryLink);
        $this->activityObjectType = 'inquiry';
        $this->activitySubjectParams['lnk'] = [
        'type' => 'highlight',
        'id' => (string)$inquiryLink->getId(),
        'name' => $inquiryLink->getInquiryLink(),
        ];
    }
}
