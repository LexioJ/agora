<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Db;

use OCA\Agora\UserSession;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Search\ISearchQuery;

/**
 * @template-extends QBMapper<Inquiry>
 */
class InquiryMapper extends QBMapper
{
    public const TABLE = Inquiry::TABLE;
    public const CONCAT_SEPARATOR = ',';

    public function __construct(
        IDBConnection $db,
        private UserSession $userSession,
    ) {
        parent::__construct($db, Inquiry::TABLE, Inquiry::class);
    }

    public function get(int $id, bool $getDeleted = false, bool $withRoles = false): Inquiry
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select(self::TABLE . '.*')
            ->from($this->getTableName(), self::TABLE)
            ->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        if (!$getDeleted) {
            $qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
        }

        if ($withRoles) {
            $inquiryGroupsAlias = 'inquiry_groups';
            $currentUserId = $this->userSession->getCurrentUserId();
            $this->joinUserRole($qb, self::TABLE, $currentUserId);
            $this->joinGroupShares($qb, self::TABLE);
            $this->joinInquiryGroups($qb, self::TABLE, $inquiryGroupsAlias);
            $this->joinInquiryGroupShares($qb, $inquiryGroupsAlias, $currentUserId, $inquiryGroupsAlias);
            $this->joinParticipantsCount($qb, self::TABLE);
            $this->joinMiscs($qb, self::TABLE);
        }
        return $this->findEntity($qb);
    }

    public function getChildInquiryIds(int $parentId): array
    {
        $currentUserId = $this->userSession->getCurrentUserId();
        $qb = $this->db->getQueryBuilder();
        $qb->select(self::TABLE . '.id')
            ->from($this->getTableName(), self::TABLE)
            ->where($qb->expr()->eq(self::TABLE . '.parent_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)));

        $qb->andWhere($qb->expr()->neq(self::TABLE . '.access', $qb->createNamedParameter('private')));

        if ($currentUserId !== null) {
            $qb->andWhere($qb->expr()->neq(self::TABLE . '.owner', $qb->createNamedParameter($currentUserId)));
        }

        $stmt = $qb->executeQuery();
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();

        if (empty($rows)) {
            return [];
        }

        return array_map(static fn(array $row): int => (int)$row['id'], $rows);
    }

    public function find(int $id): Inquiry
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        $inquiry = $this->findEntity($qb);
        
        // Load dynamic fields from InquiryMisc
        $this->loadDynamicFields($inquiry);
        
        return $inquiry;
    }

    public function findAutoReminderInquiries(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

        $inquiries = $this->findEntities($qb);
        
        // Load dynamic fields for all inquiries
        foreach ($inquiries as $inquiry) {
            $this->loadDynamicFields($inquiry);
        }
        
        return $inquiries;
    }

    public function findForMe(string $userId): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
            ->orWhere($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $inquiries = $this->findEntities($qb);
        
        // Load dynamic fields for all inquiries
        foreach ($inquiries as $inquiry) {
            $this->loadDynamicFields($inquiry);
        }
        
        return $inquiries;
    }

    public function listByOwner(string $userId): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $inquiries = $this->findEntities($qb);
        
        // Load dynamic fields for all inquiries
        foreach ($inquiries as $inquiry) {
            $this->loadDynamicFields($inquiry);
        }
        
        return $inquiries;
    }

    public function search(ISearchQuery $query): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
            ->andWhere(
                $qb->expr()->orX(
                    ...array_map(
                        function (string $token) use ($qb) {
                            return $qb->expr()->orX(
                                $qb->expr()->iLike(
                                    self::TABLE . '.title',
                                    $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
                                    IQueryBuilder::PARAM_STR
                                ),
                                $qb->expr()->iLike(
                                    self::TABLE . '.description',
                                    $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
                                    IQueryBuilder::PARAM_STR
                                )
                            );
                        }, explode(' ', $query->getTerm())
                    )
                )
            );
        
        $inquiries = $this->findEntities($qb);
        
        // Load dynamic fields for all inquiries
        foreach ($inquiries as $inquiry) {
            $this->loadDynamicFields($inquiry);
        }
        
        return $inquiries;
    }

    public function findForAdmin(string $userId): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->neq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

        $inquiries = $this->findEntities($qb);
        
        // Load dynamic fields for all inquiries
        foreach ($inquiries as $inquiry) {
            $this->loadDynamicFields($inquiry);
        }
        
        return $inquiries;
    }

    public function archiveExpiredInquiries(int $offset): int
    {
        $archiveDate = time();
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
            ->set('archived', $qb->createNamedParameter($archiveDate))
            ->where($qb->expr()->lt('expire', $qb->createNamedParameter($offset)))
            ->andWhere($qb->expr()->gt('expire', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('archived', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
        return $qb->executeStatement();
    }

    public function setInquiryStatus(int $inquiryId, string $mstatus): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
            ->set('status', $qb->createNamedParameter($mstatus))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function setModerationStatus(int $inquiryId, string $mstatus): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
            ->set('moderation_status', $qb->createNamedParameter($mstatus))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function deleteArchivedInquiries(int $offset): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('archived', $qb->createNamedParameter($offset)))
            ->andWhere($qb->expr()->gt('archived', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
        return $qb->executeStatement();
    }

    public function setLastInteraction(int $inquiryId): void
    {
        $timestamp = time();
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
            ->set('last_interaction', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function deleteByUserId(string $userId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where('owner = :userId')
            ->setParameter('userId', $userId);
        $qb->executeStatement();
    }

    protected function buildQuery(): IQueryBuilder
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(self::TABLE . '.*')
            ->from($this->getTableName(), self::TABLE);

        $currentUserId = $this->userSession->getCurrentUserId();
        $inquiryGroupsAlias = 'inquiry_groups';
        $this->joinUserRole($qb, self::TABLE, $currentUserId);
        $this->joinGroupShares($qb, self::TABLE);
        $this->joinInquiryGroups($qb, self::TABLE, $inquiryGroupsAlias);
        $this->joinInquiryGroupShares($qb, $inquiryGroupsAlias, $currentUserId, $inquiryGroupsAlias);
        $this->joinParticipantsCount($qb, self::TABLE);
        $this->joinSupportsCount($qb, self::TABLE);
        $this->joinCommentsCount($qb, self::TABLE);
        $this->joinMiscs($qb, self::TABLE);
        
        return $qb;
    }

    /**
     * Join misc settings from InquiryMisc table
     */
    protected function joinMiscs(
        IQueryBuilder &$qb,
        string $fromAlias,
        string $joinAlias = 'inquiry_misc_settings'
    ): void {
        $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT CONCAT(' . $joinAlias . '.key, ":", ' . $joinAlias . '.value)) AS misc_settings_concat'));

        $qb->leftJoin(
            $fromAlias,
            InquiryMisc::TABLE,
            $joinAlias,
            $qb->expr()->andX(
                $qb->expr()->eq($joinAlias . '.inquiry_id', $fromAlias . '.id'),
	    )
	);
    }

    private function loadDynamicFields(Inquiry $inquiry): void
    {
	    $fieldNames = $inquiry->getFieldNames();

	    if (empty($fieldNames)) {
		    $inquiry->setDynamicFields([]);
		    return;
	    }

	    // Charger depuis InquiryMisc
	    $inquiryId = $inquiry->getId();

	    $qb = $this->db->getQueryBuilder();
	    $qb->select('*')
	->from(InquiryMisc::TABLE)
	->where($qb->expr()->eq('inquiry_id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));

	    $stmt = $qb->executeQuery();
	    $storedData = $stmt->fetchAll();
	    $stmt->closeCursor();

	    $storedValues = [];
	    foreach ($storedData as $data) {
		    if (isset($data['key'], $data['value'])) {
			    $storedValues[$data['key']] = $data['value'];
		    }
	    }

	    $dynamicFields = [];
	    foreach ($fieldNames as $fieldName) {
		    $dynamicFields[$fieldName] = $storedValues[$fieldName] ?? $inquiry->getDefaultValueForField($fieldName);
	    }

	    $inquiry->setDynamicFields($dynamicFields);
    }

    /**
     * Save dynamic fields to InquiryMisc
     */
    public function saveDynamicFields(Inquiry $inquiry): void
    {
	    $dynamicFields = $inquiry->getDynamicFieldsForStorage();
	    $inquiryId = $inquiry->getId();

	    if (empty($dynamicFields)) {
		    return;
	    }

	    // Delete existing records for this inquiry
	    $qb = $this->db->getQueryBuilder();
	    $qb->delete(InquiryMisc::TABLE)
	->where($qb->expr()->eq('inquiry_id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));
	    $qb->executeStatement();

	    // Then insert new settings
	    foreach ($dynamicFields as $key => $value) {
		    $qb = $this->db->getQueryBuilder();
		    $qb->insert(InquiryMisc::TABLE)
	 ->values([
		 'inquiry_id' => $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT),
		 'key' => $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR),
		 'value' => $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR),
	 ])
	 ->executeStatement();
	    }
    }


    protected function joinUserRole(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $currentUserId,
	    string $joinAlias = 'user_shares',
    ): void {
	    $emptyString = $qb->expr()->literal('');

	    $qb->addSelect($qb->createFunction('coalesce(' . $joinAlias . '.type, ' . $emptyString . ') AS user_role'))
	->addGroupBy($joinAlias . '.type');

	    $qb->addSelect($qb->createFunction('coalesce(' . $joinAlias . '.token, ' . $emptyString . ') AS share_token'))
	->addGroupBy($joinAlias . '.token');

	    $qb->leftJoin(
		    $fromAlias,
		    Share::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.inquiry_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
			    $qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
		    )
	    );
    }

    protected function joinGroupShares(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $joinAlias = 'group_shares',
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT ' . $joinAlias . '.user_id) AS group_shares'));

	    $qb->leftJoin(
		    $fromAlias,
		    Share::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.inquiry_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.type', $qb->expr()->literal('group')),
			    $qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
		    )
	    );
    }

    protected function joinInquiryGroups(
	    IQueryBuilder $qb,
	    string $fromAlias,
	    string $joinAlias = 'inquiry_groups',
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT ' . $joinAlias . '.group_id) AS inquiry_groups'));

	    $qb->leftJoin(
		    $fromAlias,
		    InquiryGroup::RELATION_TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq(self::TABLE . '.id', $joinAlias . '.inquiry_id'),
		    )
	    );
    }

    protected function joinInquiryGroupShares(
	    IQueryBuilder $qb,
	    string $fromAlias,
	    string $currentUserId,
	    string $inquiryGroupsAlias,
	    string $joinAlias = 'inquiry_group_shares',
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT ' . $joinAlias . '.type) AS inquiry_group_user_shares'));

	    $qb->leftJoin(
		    $fromAlias,
		    Share::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.group_id', $inquiryGroupsAlias . '.group_id'),
			    $qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			    $qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
		    )
	    );
    }

    protected function joinSupportsCount(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $joinAlias = 'supports',
    ): void {
	    $qb->leftJoin(
		    $fromAlias,
		    Support::TABLE,
		    $joinAlias,
		    $qb->expr()->eq($joinAlias . '.inquiry_id', $fromAlias . '.id')
	    )->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.user_id)) AS count_supports'));
	    $qb->groupBy($fromAlias . '.id');
    }

    protected function joinCommentsCount(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $joinAlias = 'comments',
    ): void {
	    $qb->leftJoin(
		    $fromAlias,
		    Comment::TABLE,
		    $joinAlias,
		    $qb->expr()->eq($joinAlias . '.inquiry_id', $fromAlias . '.id')
	    )->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.id)) AS count_comments'));
	    $qb->groupBy($fromAlias . '.id');
    }

    protected function joinParticipantsCount(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $joinAlias = 'participants',
    ): void {
	    $qb->leftJoin(
		    $fromAlias,
		    Inquiry::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.parent_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.access', $qb->createNamedParameter('open'))
		    )
	    );
	    $qb->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.id)) AS count_participants'));
    }
}
