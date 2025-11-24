<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\Inquiry;
use OCA\Agora\Db\InquiryGroup;
use OCA\Agora\Db\InquiryGroupMapper;
use OCA\Agora\Db\InquiryMapper;
use OCA\Agora\Event\InquiryUpdatedEvent;
use OCA\Agora\Exceptions\ForbiddenException;
use OCA\Agora\Exceptions\InsufficientAttributesException;
use OCA\Agora\Exceptions\NotFoundException;
use OCA\Agora\Model\Settings\AppSettings;
use OCA\Agora\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;

class InquiryGroupService
{
    public const GROUP_MODERATOR = 'agora_moderator';
    public const GROUP_OFFICIAL  = 'agora_official';

    /**
     * @psalm-suppress PossiblyUnusedMethod 
     */
    public function __construct(
        private AppSettings $appSettings,
        private IEventDispatcher $eventDispatcher,
        private InquiryMapper $inquiryMapper,
        private UserSession $userSession,
        private InquiryGroupMapper $inquiryGroupMapper,
    ) {
    }

    public function listInquiryGroups(): array
    {
        return $this->inquiryGroupMapper->list();
    }

    public function getInquiryGroup(int $inquiryGroupId): InquiryGroup
    {
        return $this->inquiryGroupMapper->find($inquiryGroupId);
    }

    public function createInquiryGroup(
        string $name,
        string $type = 'default',
        ?string $description = null,
        ?string $titleExt = null,
        ?int $parentId = null,
        ?int $order = null,
        ?int $expire = null,
        ?string $metadata = null,
        ?int $coverId = null,
        bool $protected = false,
        string $groupStatus = 'draft'
    ): InquiryGroup {
        if (!$this->appSettings->getInquiryCreationAllowed()) {
            throw new ForbiddenException('Inquiry group creation is disabled');
        }

        $inquiryGroup = new InquiryGroup();
        $inquiryGroup->setName($name);
        $inquiryGroup->setType($type);
        $inquiryGroup->setDescription($description);
        $inquiryGroup->setTitleExt($titleExt);
        $inquiryGroup->setParentId($parentId);
        $inquiryGroup->setOrder($order ?? 0);
        $inquiryGroup->setExpire($expire);
        $inquiryGroup->setMetadata($metadata);
        $inquiryGroup->setCoverId($coverId);
        $inquiryGroup->setProtected($protected);
        $inquiryGroup->setGroupStatus($groupStatus);
        $inquiryGroup->setOwner($this->userSession->getCurrentUserId());

        return $this->inquiryGroupMapper->add($inquiryGroup);
    }

    public function updateInquiryGroup(
        int $inquiryGroupId,
        string $name,
        ?string $titleExt = null,
        ?string $description = null,
        ?string $type = null,
        ?int $parentId = null,
        ?int $order = null,
        ?int $expire = null,
        ?string $metadata = null,
        ?int $coverId = null,
        ?bool $protected = null,
        ?string $groupStatus = null
    ): InquiryGroup {
        try {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
            
            // Check permissions
            if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
                throw new ForbiddenException('You do not have permission to edit this inquiry group');
            }

            // Update basic fields
            $inquiryGroup->setName($name);
            
            if ($titleExt !== null) {
                $inquiryGroup->setTitleExt($titleExt);
            }
            if ($description !== null) {
                $inquiryGroup->setDescription($description);
            }
            if ($type !== null) {
                $inquiryGroup->setType($type);
            }
            if ($parentId !== null) {
                $inquiryGroup->setParentId($parentId);
            }
            if ($order !== null) {
                $inquiryGroup->setOrder($order);
            }
            if ($expire !== null) {
                $inquiryGroup->setExpire($expire);
            }
            if ($metadata !== null) {
                $inquiryGroup->setMetadata($metadata);
            }
            if ($coverId !== null) {
                $inquiryGroup->setCoverId($coverId);
            }
            if ($protected !== null) {
                $inquiryGroup->setProtected($protected);
            }
            if ($groupStatus !== null) {
                $inquiryGroup->setGroupStatus($groupStatus);
            }

            $inquiryGroup = $this->inquiryGroupMapper->update($inquiryGroup);
            return $inquiryGroup;
        } catch (DoesNotExistException $e) {
            throw new NotFoundException('Inquiry group not found');
        }
    }

    public function deleteInquiryGroup(int $inquiryGroupId): void
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions
        if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
            throw new ForbiddenException('You do not have permission to delete this inquiry group');
        }

        // Check if group is protected
        if ($inquiryGroup->getProtected()) {
            throw new ForbiddenException('Cannot delete a protected inquiry group');
        }

        $this->inquiryGroupMapper->delete($inquiryGroup);
    }

    public function softDeleteInquiryGroup(int $inquiryGroupId): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions
        if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
            throw new ForbiddenException('You do not have permission to delete this inquiry group');
        }

        $this->inquiryGroupMapper->softDelete($inquiryGroupId);
        return $this->inquiryGroupMapper->find($inquiryGroupId);
    }

    public function updateInquiryGroupOrder(int $inquiryGroupId, int $order): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions
        if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
            throw new ForbiddenException('You do not have permission to edit this inquiry group');
        }

        $this->inquiryGroupMapper->updateOrder($inquiryGroupId, $order);
        return $this->inquiryGroupMapper->find($inquiryGroupId);
    }

    public function addInquiryToInquiryGroup(
        int $inquiryId,
        ?int $inquiryGroupId = null,
        ?string $inquiryGroupName = null,
    ): InquiryGroup {
        $inquiry = $this->inquiryMapper->get($inquiryId, withRoles: true);
        $inquiry->request(Inquiry::PERMISSION_INQUIRY_EDIT);

        // Without inquiry group id, we create a new inquiry group
        if ($inquiryGroupId === null
            && $inquiryGroupName !== null
            && $inquiryGroupName !== ''
        ) {
            if (!$this->appSettings->getInquiryCreationAllowed()) {
                // If inquiry creation is disabled, creating a inquiry group is also disabled
                throw new ForbiddenException('Inquiry group creation is disabled');
            }

            // Create new inquiry group using the new method
            $inquiryGroup = $this->createInquiryGroup($inquiryGroupName);

        } elseif ($inquiryGroupId !== null) {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);

            // Check if user has permission to add to this group
            if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
                throw new ForbiddenException('You do not have permission to add inquiries to this group');
            }

        } else {
            throw new InsufficientAttributesException('An existing inquiry group id must be provided or a new inquiry group name must be given.');
        }

        if (!$inquiryGroup->hasInquiry($inquiryId)) {
            try {
                $this->inquiryGroupMapper->addInquiryToGroup($inquiryId, $inquiryGroup->getId());
            } catch (Exception $e) {
                if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                    // Inquiry is already member of this group
                } else {
                    throw $e;
                }
            }

            $this->eventDispatcher->dispatchTyped(new InquiryUpdatedEvent($inquiry));
        }

        return $this->inquiryGroupMapper->find($inquiryGroup->getId());
    }

    public function removeInquiryFromInquiryGroup(
        int $inquiryId,
        int $inquiryGroupId,
    ): ?InquiryGroup {
        $inquiry = $this->inquiryMapper->get($inquiryId, withRoles: true);
        $inquiry->request(Inquiry::PERMISSION_INQUIRY_EDIT);

        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);

        // Check if user has permission to remove from this group
        if (!$inquiryGroup->getIsAllowed(InquiryGroup::PERMISSION_INQUIRY_GROUP_EDIT)) {
            throw new ForbiddenException('You do not have permission to remove inquiries from this group');
        }

        if ($inquiryGroup->hasInquiry($inquiryId)) {
            $this->inquiryGroupMapper->removeInquiryFromGroup($inquiryId, $inquiryGroupId);
            $this->eventDispatcher->dispatchTyped(new InquiryUpdatedEvent($inquiry));
        } else {
            throw new NotFoundException('Inquiry not found in group');
        }

        $this->inquiryGroupMapper->tidyInquiryGroups();
        try {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        } catch (DoesNotExistException $e) {
            // Inquiry group was deleted, return null
            return null;
        }
        return $inquiryGroup;
    }

    /**
     * List inquiry groups by type
     *
     * @param string $type
     * @return InquiryGroup[]
     */
    public function listInquiryGroupsByType(string $type): array
    {
        return $this->inquiryGroupMapper->findByType($type);
    }

    /**
     * List inquiry groups by status
     *
     * @param string $status
     * @return InquiryGroup[]
     */
    public function listInquiryGroupsByStatus(string $status): array
    {
        return $this->inquiryGroupMapper->findByStatus($status);
    }

    /**
     * List active (non-deleted) inquiry groups
     *
     * @return InquiryGroup[]
     */
    public function listActiveInquiryGroups(): array
    {
        return $this->inquiryGroupMapper->findActive();
    }

    /**
     * List expired inquiry groups
     *
     * @return InquiryGroup[]
     */
    public function listExpiredInquiryGroups(): array
    {
        return $this->inquiryGroupMapper->findExpired();
    }
}
