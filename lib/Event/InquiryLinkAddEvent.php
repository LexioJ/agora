<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Event;

use OCA\Agora\Db\InquiryLink;

class InquiryLinkAddEvent extends InquiryLinkEvent
{
    public function __construct(
        protected InquiryLink $inquiryLink,
    ) {
        parent::__construct($inquiryLink);
        $this->eventId = self::ADD;
    }
}
