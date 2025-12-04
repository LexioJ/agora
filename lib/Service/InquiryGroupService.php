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
use OCP\IUserManager;

class InquiryGroupService
{
    /**
     * @psalm-suppress PossiblyUnusedMethod 
     */
    public function __construct(
        private AppSettings $appSettings,
        private IEventDispatcher $eventDispatcher,
        private InquiryMapper $inquiryMapper,
        private UserSession $userSession,
        private InquiryGroupMapper $inquiryGroupMapper,
        private IUserManager $userManager,
    ) {
    }

    /**
     * Get a single inquiry group
     */
    public function get(int $inquiryGroupId): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * List all inquiry groups
     */
    public function listInquiryGroups(): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->list();
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }

    /**
     * Create a new inquiry group
     */
    public function createGroup(
        string $title,
        string $type = 'default',
        ?int $parentId = 0,
        ?bool $protected = false,
        ?string $ownedGroup = '',
        ?string $groupStatus = 'draft',
        ?string $titleExt = '',
        ?string $description = ''
    ): InquiryGroup {
        if (!$this->appSettings->getInquiryCreationAllowed()) {
            throw new ForbiddenException('Inquiry group creation is disabled');
        }

        $inquiryGroup = new InquiryGroup();
        $inquiryGroup->setTitle($title);
        $inquiryGroup->setTitleExt($titleExt ?: $title);
        $inquiryGroup->setType($type);
        $inquiryGroup->setParentId($parentId ?? 0);
        $inquiryGroup->setProtected($protected ? 1 : 0);
        $inquiryGroup->setGroupStatus($groupStatus ?? 'draft');
        $inquiryGroup->setOwnedGroup($ownedGroup ?? '');
        $inquiryGroup->setDescription($description ?? '');
        $inquiryGroup->setCreated(time());

        // Set owner from current user session
        $currentUser = $this->userSession->getCurrentUser();
        if ($currentUser) {
            $inquiryGroup->setOwner($this->userSession->getCurrentUserId());
        }

        $inquiryGroup = $this->inquiryGroupMapper->insert($inquiryGroup);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Update an inquiry group with all possible fields
     */
    public function updateGroup(
        int $inquiryGroupId,
        ?string $title = null,
        ?string $titleExt = null,
        ?string $description = null,
        ?string $type = null,
        ?int $parentId = null,
        ?bool $protected = null,
        ?string $ownedGroup = null,
        ?string $groupStatus = null,
        ?int $expire = null
    ): InquiryGroup {
        try {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
            
            // Check permissions
            if (!$inquiryGroup->getAllowEdit() && !$this->userSession->isAdmin()) {
                throw new ForbiddenException('You do not have permission to edit this inquiry group');
            }

            // Update basic fields
            if ($title !== null) {
                $inquiryGroup->setTitle($title);
            }
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
            if ($protected !== null) {
                $inquiryGroup->setProtected($protected ? 1 : 0);
            }
            if ($ownedGroup !== null) {
                $inquiryGroup->setOwnedGroup($ownedGroup);
            }
            if ($groupStatus !== null) {
                $inquiryGroup->setGroupStatus($groupStatus);
            }
            if ($expire !== null) {
                $inquiryGroup->setExpire($expire);
            }

            $inquiryGroup = $this->inquiryGroupMapper->update($inquiryGroup);
            $this->enrichInquiryGroup($inquiryGroup);
            return $inquiryGroup;
        } catch (DoesNotExistException $e) {
            throw new NotFoundException('Inquiry group not found');
        }
    }

    /**
     * Update specific inquiry group fields (legacy method)
     */
    public function updateInquiryGroup(
        int $inquiryGroupId,
        string $name,
        ?string $titleExt = null,
        ?string $description = null
    ): InquiryGroup {
        return $this->updateGroup(
            $inquiryGroupId,
            title: $name,
            titleExt: $titleExt,
            description: $description
        );
    }

    /**
     * Delete an inquiry group
     */
    public function deleteGroup(int $inquiryGroupId): bool
    {
        try {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
            
            // Check permissions
            if (!$inquiryGroup->getAllowEdit() && !$this->userSession->isAdmin()) {
                throw new ForbiddenException('You do not have permission to delete this inquiry group');
            }

            // Check if group is protected
            if ($inquiryGroup->getProtected() === 1) {
                throw new ForbiddenException('Cannot delete a protected inquiry group');
            }

            $this->inquiryGroupMapper->delete($inquiryGroup);
            return true;
        } catch (DoesNotExistException $e) {
            throw new NotFoundException('Inquiry group not found');
        }
    }
    

    /**
     * Soft delete an inquiry group (mark as deleted)
     */
    public function softDeleteInquiryGroup(int $inquiryGroupId): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions
        if (!$inquiryGroup->getAllowEdit() && !$this->userSession->isAdmin()) {
            throw new ForbiddenException('You do not have permission to delete this inquiry group');
        }

        $inquiryGroup->setDeleted(time());
        $inquiryGroup = $this->inquiryGroupMapper->update($inquiryGroup);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Archive an inquiry group
     */
    public function archiveGroup(int $inquiryGroupId): InquiryGroup
    {
        return $this->updateGroup($inquiryGroupId, groupStatus: 'archived');
    }

    /**
     * Restore an archived inquiry group
     */
    public function restoreGroup(int $inquiryGroupId): InquiryGroup
    {
        return $this->updateGroup($inquiryGroupId, groupStatus: 'active');
    }

    /**
     * Reorder inquiries in a group
     */
    public function reorderInquiries(int $inquiryGroupId, array $inquiryIds): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions
        if (!$inquiryGroup->getAllowEdit() && !$this->userSession->isAdmin()) {
            throw new ForbiddenException('You do not have permission to reorder inquiries in this group');
        }

        $this->inquiryGroupMapper->updateInquiryOrder($inquiryGroupId, $inquiryIds);
        
        // Refresh the group to get updated inquiry IDs
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Change owner of an inquiry group
     */
    public function changeOwner(int $inquiryGroupId, string $newOwnerId): InquiryGroup
    {
        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions - only owner or admin can change owner
        $currentUser = $this->userSession->getCurrentUser();
        if ($inquiryGroup->getOwner() !== $this->userSession->getCurrentUserId() && !$this->userSession->isAdmin()) {
            throw new ForbiddenException('Only the owner or admin can change the owner of this inquiry group');
        }

        // Verify new owner exists
        $newOwner = $this->userManager->get($newOwnerId);
        if (!$newOwner) {
            throw new NotFoundException('New owner not found');
        }

        $inquiryGroup->setOwner($newOwnerId);
        $inquiryGroup = $this->inquiryGroupMapper->update($inquiryGroup);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Clone an inquiry group
     */
    public function cloneGroup(int $inquiryGroupId): InquiryGroup
    {
        $originalGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        
        // Check permissions - anyone can clone if they can view
        if (!$originalGroup->getAllowEdit() && !$this->userSession->getCurrentUser()) {
            throw new ForbiddenException('You do not have permission to clone this inquiry group');
        }

        // Create new group with similar properties
        $clonedGroup = new InquiryGroup();
        $clonedGroup->setTitle($originalGroup->getTitle() . ' (Copy)');
        $clonedGroup->setTitleExt($originalGroup->getTitleExt());
        $clonedGroup->setType($originalGroup->getType());
        $clonedGroup->setParentId($originalGroup->getParentId());
        $clonedGroup->setProtected($originalGroup->getProtected());
        $clonedGroup->setGroupStatus('draft');
        $clonedGroup->setOwnedGroup($originalGroup->getOwnedGroup());
        $clonedGroup->setDescription($originalGroup->getDescription());
        $clonedGroup->setCreated(time());

        // Set current user as owner
        $currentUser = $this->userSession->getCurrentUser();
        if ($currentUser) {
            $clonedGroup->setOwner($this->userSession->getCurrentUserId());
        }

        $clonedGroup = $this->inquiryGroupMapper->insert($clonedGroup);
        
        // Clone inquiries if any
        if (!empty($originalGroup->getInquiryIds())) {
            foreach ($originalGroup->getInquiryIds() as $inquiryId) {
                try {
                    $this->inquiryGroupMapper->addInquiryToGroup($inquiryId, $clonedGroup->getId());
                } catch (Exception $e) {
                    // Skip if there's an error adding an inquiry
                    continue;
                }
            }
        }

        $this->enrichInquiryGroup($clonedGroup);
        return $clonedGroup;
    }

    /**
     * Add inquiry to inquiry group
     */
    public function addInquiryToInquiryGroup(
        int $inquiryId,
        ?int $inquiryGroupId = null,
        ?string $inquiryGroupName = null,
    ): InquiryGroup {
        $inquiry = $this->inquiryMapper->get($inquiryId);
        
        // Check if user has permission to edit the inquiry
        //if (!$inquiry->getAllowEdit() && !$this->userSession->isAdmin()) {
         //   throw new ForbiddenException('You do not have permission to edit this inquiry');
        //}

        // Without inquiry group id, create a new inquiry group
        if ($inquiryGroupId === null && $inquiryGroupName !== null && $inquiryGroupName !== '') {
            if (!$this->appSettings->getInquiryCreationAllowed()) {
                throw new ForbiddenException('Inquiry group creation is disabled');
            }

            // Create new inquiry group
            $inquiryGroup = $this->createGroup(
                title: $inquiryGroupName,
                type: 'default'
            );

        } elseif ($inquiryGroupId !== null) {
            $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);

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

        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroup->getId());
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Remove inquiry from inquiry group
     */
    public function removeInquiryFromInquiryGroup(
        int $inquiryId,
        int $inquiryGroupId,
    ): ?InquiryGroup {
        $inquiry = $this->inquiryMapper->get($inquiryId);
        
        // Check if user has permission to edit the inquiry
        if (!$inquiry->getAllowEdit() && !$this->userSession->isAdmin()) {
            throw new ForbiddenException('You do not have permission to edit this inquiry');
        }

        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);

        // Check if user has permission to remove from this group
        if (!$inquiryGroup->getAllowEdit() && !$this->userSession->isAdmin()) {
            throw new ForbiddenException('You do not have permission to remove inquiries from this group');
        }

        if ($inquiryGroup->hasInquiry($inquiryId)) {
            $this->inquiryGroupMapper->removeInquiryFromGroup($inquiryId, $inquiryGroupId);
            $this->eventDispatcher->dispatchTyped(new InquiryUpdatedEvent($inquiry));
        } else {
            throw new NotFoundException('Inquiry not found in group');
        }

        // Check if group is now empty and should be deleted
        $remainingInquiries = $this->inquiryGroupMapper->countInquiriesInGroup($inquiryGroupId);
        if ($remainingInquiries === 0) {
            $this->inquiryGroupMapper->delete($inquiryGroup);
            return null;
        }

        $inquiryGroup = $this->inquiryGroupMapper->find($inquiryGroupId);
        $this->enrichInquiryGroup($inquiryGroup);
        return $inquiryGroup;
    }

    /**
     * Enrich inquiry group with additional data
     */
/**
 * Enrich inquiry group with additional data
 */
private function enrichInquiryGroup(InquiryGroup $inquiryGroup): void
{
    // Get inquiry IDs for this group
    $inquiryIds = $this->inquiryGroupMapper->getInquiryIdsForGroup($inquiryGroup->getId());
    $inquiryGroup->setInquiryIds($inquiryIds);

    // Get child groups
    $childGroups = $this->inquiryGroupMapper->findByParentId($inquiryGroup->getId());
    $inquiryGroup->setChilds($childGroups);

    // Permissions
    $currentUser = $this->userSession->getCurrentUser();
    $isOwner = $currentUser && $inquiryGroup->getOwner() === $this->userSession->getCurrentUserId();
    $isAdmin = $this->userSession->isAdmin();

    // Misc
    $inquiryGroup->setMiscFields([]);
}

    /**
     * List inquiry groups by type
     */
    public function listInquiryGroupsByType(string $type): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->findByType($type);
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }

    /**
     * List inquiry groups by status
     */
    public function listInquiryGroupsByStatus(string $status): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->findByStatus($status);
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }

    /**
     * List active (non-deleted) inquiry groups
     */
    public function listActiveInquiryGroups(): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->findActive();
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }

    /**
     * List expired inquiry groups
     */
    public function listExpiredInquiryGroups(): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->findExpired();
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }

    /**
     * Get child groups of a parent group
     */
    public function getChildGroups(int $parentId): array
    {
        $inquiryGroups = $this->inquiryGroupMapper->findByParentId($parentId);
        foreach ($inquiryGroups as $inquiryGroup) {
            $this->enrichInquiryGroup($inquiryGroup);
        }
        return $inquiryGroups;
    }
}
