<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Service;

use OCA\Agora\Db\Attachment;
use OCA\Agora\Db\AttachmentMapper;
use OCA\Agora\Db\InquiryMapper;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Share\Share;

use OCP\IUserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCA\Agora\Model\Group\Group;

class AttachmentService
{
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private AttachmentMapper $attachmentMapper;
    private InquiryMapper $inquiryMapper;
    private IURLGenerator $urlGenerator;
    private LoggerInterface $logger;        
    private IShareManager $shareManager;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        AttachmentMapper $attachmentMapper,
        InquiryMapper $inquiryMapper,
        IURLGenerator $urlGenerator,
        LoggerInterface $logger,
        IShareManager $shareManager
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->attachmentMapper = $attachmentMapper;
        $this->inquiryMapper = $inquiryMapper;
	$this->urlGenerator = $urlGenerator;
	$this->logger = $logger;
	$this->shareManager = $shareManager;
    }

    /**
     * Get Agora root folder storage folder
     */
    private function getAgoraRootFolder()
    {
	    $userId = $this->userSession->getUser()->getUID();
	    $userFolder = $this->rootFolder->getUserFolder($userId);

	    if (!$userFolder->nodeExists(Group::AGORA_FOLDER)) {
		    $agoraFolder = $userFolder->newFolder(Group::AGORA_FOLDER);
	    } else {
		    $agoraFolder = $userFolder->get(Group::AGORA_FOLDER);
	    }

	    return $agoraFolder;
    }    

/**
 * Add a new attachment (both file and database record)
 * Handles cover image replacement if coverId is true
 *
 * @param int $inquiryId ID of the inquiry
 * @param array $uploadedFile Uploaded file data from $_FILES
 * @param bool $coverId Whether this is a cover image
 * @return Attachment
 * @throws \Exception
 */
public function add(int $inquiryId, array $uploadedFile, bool $coverId): Attachment
{
    $inquiryFolder = $this->getInquiryFolder($inquiryId);
    $user = $this->userSession->getUser();

    // If coverId is true, handle old cover removal
    if ($coverId === true) {
        // Get the inquiry
        $inquiry = $this->inquiryMapper->find($inquiryId);
        
        // If inquiry already has a coverId, remove the old file
        if ($inquiry->getCoverId() !== null) {
            try {
                // Check if old attachment exists before attempting removal
                $oldAttachment = $this->attachmentMapper->findByFileId($inquiryId,$inquiry->getCoverId());
                
                // If we get here, attachment exists and can be removed
                $this->remove($oldAttachment->getId());
                
                $this->logger->info('Removed old cover file: ' . $inquiry->getCoverId());
                
            } catch (DoesNotExistException $e) {
                // Attachment doesn't exist in database, just clean up the coverId
                $this->logger->warning('Old cover attachment not found, cleaning coverId: ' . $inquiry->getCoverId());
                $inquiry->setCoverId(null);
                $this->inquiryMapper->update($inquiry);
            } catch (\Exception $e) {
                // Log other errors but continue with new upload
                $this->logger->error('Failed to remove old cover file: ' . $e->getMessage());
            }
        }
    }

    // Copy file to inquiry folder
    $fileName = $this->sanitizeFileName($uploadedFile['name']);
    $content = file_get_contents($uploadedFile['tmp_name']);
    $targetFile = $inquiryFolder->newFile($fileName, $content);

    // Share the file with groups (with error handling)
    try {
        $this->ensureFileSharedWithGroups($targetFile);
    } catch (\Exception $e) {
        // Log error but continue
        $this->logger->error('Error sharing file with groups: ' . $e->getMessage());
        // File is still created, just sharing failed
    }

    // Create database record
    $attachment = new Attachment();
    $attachment->setInquiryId($inquiryId);
    $attachment->setName($uploadedFile['name']);
    $attachment->setMimeType($uploadedFile['type'] ?? 'application/octet-stream');
    $attachment->setSize($uploadedFile['size']);
    $attachment->setFileId((string)$targetFile->getId());
    $attachment->setCreated(time());

    $attachment = $this->attachmentMapper->insert($attachment);

    // If coverId is true, update inquiry with new attachmentId
    if ($coverId === true) {
        $inquiry->setCoverId($attachment->getFileId());
	$this->inquiryMapper->update($inquiry);
	$this->logger->info('Updated inquiry cover with new attachment: ' . $attachment->getId());
    }

    return $attachment;
}

/**
 * Ensure file is shared with required groups
 * Uses basic permissions to avoid create/delete permission errors
 *
 * @param File $file File to share
 */
private function ensureFileSharedWithGroups(File $file): void
{
	$ownerUid = $this->userSession->getUser()->getUID();
	$groups = [
		Group::GROUP_USERS => \OCP\Constants::PERMISSION_READ,
		Group::GROUP_MODERATOR => \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE,
		Group::GROUP_OFFICIAL => \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE
	];

	// Get all existing shares once
	$existingShares = $this->shareManager->getSharesBy(
		$ownerUid,
		IShare::TYPE_GROUP,
		$file,
		false,
		-1
	);

	foreach ($groups as $groupName => $permissions) {
		$alreadyShared = false;

		// Check if already shared with this group
		foreach ($existingShares as $share) {
			if ($share->getSharedWith() === $groupName) {
				$alreadyShared = true;
				break;
			}
		}

		// Create share if not already exists
		if (!$alreadyShared) {
			try {
				$share = $this->shareManager->newShare();
				$share->setNode($file);
				$share->setShareType(IShare::TYPE_GROUP);
				$share->setSharedWith($groupName);
				$share->setSharedBy($ownerUid);
				$share->setPermissions($permissions);

				$this->shareManager->createShare($share);
				$this->logger->info('File shared with group: ' . $groupName . ' - ' . $file->getPath());

			} catch (\Exception $e) {
				$this->logger->error('Error sharing file with group ' . $groupName . ': ' . $e->getMessage());
			}
		}
	}
}



/**
 * Get or create inquiry-specific folder
 */
private function getInquiryFolder(int $inquiryId)
{
	$agoraRoot = $this->getAgoraRootFolder();
	$inquiryFolderName = 'inquiry___' . (string)$inquiryId;

	if (!$agoraRoot->nodeExists($inquiryFolderName)) {
		$inquiryFolder = $agoraRoot->newFolder($inquiryFolderName);
	} else {
		$inquiryFolder = $agoraRoot->get($inquiryFolderName);
	}

	return $inquiryFolder; 
}

/**
 * Sanitize file name to prevent issues
 */
private function sanitizeFileName(string $fileName): string
{
	$fileName = preg_replace('/[\/\\\?%*:|"<>]/', '_', $fileName);
	if (strlen($fileName) > 200) {
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);
		$name = pathinfo($fileName, PATHINFO_FILENAME);
		$fileName = substr($name, 0, 200 - strlen($extension) - 1) . '.' . $extension;
	}
	return $fileName;
}


/**
 * Remove an attachment (both file and database record)
 */
public function remove(int $attachmentId): Attachment
{
	$attachment = $this->attachmentMapper->findById($attachmentId);

	try {
		$file = $this->rootFolder->getById((int)$attachment->getFileId())[0];

		// Delete all shares for this file before deleting the file
		$shares = $this->shareManager->getSharesBy(
			$this->userSession->getUser()->getUID(),
			IShare::TYPE_GROUP,
			$file,
			false,
			-1
		);

		foreach ($shares as $share) {
			$this->shareManager->deleteShare($share);
		}

		$file->delete();
	} catch (NotFoundException $e) {
		// File already deleted, continue with DB removal
	}

	return $this->attachmentMapper->deleteById($attachmentId);
}

/**
 * Get all attachments for an inquiry
 *
 * @return array[]
 */
public function getAll(int $inquiryId): array
{
	$attachments = $this->attachmentMapper->findByInquiryId($inquiryId);
	$result = [];

	$currentUser = $this->userSession->getUser()->getUID();

	foreach ($attachments as $attachment) {
		try {
			$fileId = (int)$attachment->getFileId();

			$nodes = $this->rootFolder->getById((int)$attachment->getFileId());
			if (empty($nodes)) {
				continue; 
			}
			$file = $nodes[0];
			$result[] = [
				'id' => $attachment->getId(),
				'name' => $attachment->getName(),
				'type' => $attachment->getMimeType(),
				'size' => $attachment->getSize(),
				'created' => $attachment->getCreated(),
				'file_id' => $attachment->getFileId(),
				'url'  => $this->urlGenerator->linkToRouteAbsolute('files.View.showFile', ['fileid' => $file->getId()])
			];
		} catch (NotFoundException $e) {
			// File missing but DB record exists, skip or handle as needed
			continue;
		}
	}

	$this->logger->debug(
		'Attachment processing', [
			'inquiryId' => $inquiryId,
			'totalAttachmentsInDb' => count($attachments),
			'validAttachmentsWithFiles' => count($result),
			'invalidAttachments' => count($attachments) - count($result)
		]
	);
	return $result;
}

/**
 * Get attachment by ID
 *
 * @throws DoesNotExistException
 * @throws MultipleObjectsReturnedException
 */
public function get(int $attachmentId): Attachment
{
	return $this->attachmentMapper->findById($attachmentId);
}

/**
 * Get direct download URL for an attachment
 */
public function getDownloadUrl(int $attachmentId): string
{
	$attachment = $this->attachmentMapper->findById($attachmentId);
	$fileId = (int)$attachment->getFileId();

	$nodes = $this->rootFolder->getById($fileId);
	if (empty($nodes)) {
		throw new NotFoundException('File not found');
	}

	$file = $nodes[0];
	return $this->urlGenerator->linkToRouteAbsolute('files.View.showFile', ['fileid' => $file->getId()]);
}
}
