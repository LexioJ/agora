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
 * @template-extends QBMapper<Option>
 */
class OptionMapper extends QBMapper
{
    public const TABLE = Option::TABLE;
    public const CONCAT_SEPARATOR = ',';

    public function __construct(
        IDBConnection $db,
        private UserSession $userSession,
    ) {
        parent::__construct($db, Option::TABLE, Option::class);
    }

    public function get(int $id, bool $getDeleted = false, bool $withRoles = false): Option
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select(self::TABLE . '.*')
            ->from($this->getTableName(), self::TABLE)
            ->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        if (!$getDeleted) {
            $qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
        }

        if ($withRoles) {
            $optionGroupsAlias = 'option_groups';
            $currentUserId = $this->userSession->getCurrentUserId();
            $this->joinUserRole($qb, self::TABLE, $currentUserId);
            $this->joinGroupShares($qb, self::TABLE);
            $this->joinOptionGroups($qb, self::TABLE, $optionGroupsAlias);
            $this->joinOptionGroupShares($qb, $optionGroupsAlias, $currentUserId, $optionGroupsAlias);
            $this->joinParticipantsCount($qb, self::TABLE);
            $this->joinMiscs($qb, self::TABLE);
        }
        return $this->findEntity($qb);
    }

    public function getChildOptionIds(int $parentId): array
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

    public function find(int $id): Option
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        $option = $this->findEntity($qb);
        
        // Load dynamic fields from OptionMisc
        $this->loadDynamicFields($option);
        
        return $option;
    }

    public function findForMe(string $userId): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
            ->orWhere($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $options = $this->findEntities($qb);
        
        // Load dynamic fields for all options
        foreach ($options as $option) {
		$this->loadDynamicFields($option);
	}

	return $options;
    }


    public function findByInquiry(int $inquiryId): array
    {
	    $qb = $this->db->getQueryBuilder();
	    $qb->select('*')
	->from($this->getTableName())
	->where($qb->expr()->eq('inquiry_id', $qb->createNamedParameter($inquiryId, IQueryBuilder::PARAM_INT)));

	    return $this->findEntities($qb);
    }

    public function listByOwner(string $userId): array
    {
	    $qb = $this->buildQuery();
	    $qb->where($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

	    $options = $this->findEntities($qb);

	    // Load dynamic fields for all options
	    foreach ($options as $option) {
		    $this->loadDynamicFields($option);
	    }

	    return $options;
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
							self::TABLE . '.option_text',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						)
					);
				}, explode(' ', $query->getTerm())
			)
		)
	);

	    $options = $this->findEntities($qb);

	    // Load dynamic fields for all options
	    foreach ($options as $option) {
		    $this->loadDynamicFields($option);
	    }

	    return $options;
    }

    public function findForAdmin(string $userId): array
    {
	    $qb = $this->buildQuery();
	    $qb->where($qb->expr()->neq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

	    $options = $this->findEntities($qb);

	    // Load dynamic fields for all options
	    foreach ($options as $option) {
		    $this->loadDynamicFields($option);
	    }

	    return $options;
    }

    public function archiveExpiredOptions(int $offset): int
    {
	    $archiveDate = time();
	    $qb = $this->db->getQueryBuilder();
	    $qb->update($this->getTableName())
	->set('archived', $qb->createNamedParameter($archiveDate))
	->where($qb->expr()->eq('archived', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
	    return $qb->executeStatement();
    }

    public function setModerationStatus(int $optionId, string $mstatus): void
    {
	    $qb = $this->db->getQueryBuilder();
	    $qb->update($this->getTableName())
	->set('status', $qb->createNamedParameter($mstatus))
	->where($qb->expr()->eq('id', $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT)));
	    $qb->executeStatement();
    }

    public function deleteArchivedOptions(int $offset): int
    {
	    $qb = $this->db->getQueryBuilder();
	    $qb->delete($this->getTableName())
	->where($qb->expr()->lt('archived', $qb->createNamedParameter($offset)))
	->andWhere($qb->expr()->gt('archived', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
	    return $qb->executeStatement();
    }

    public function setLastInteraction(int $optionId): void
    {
	    $timestamp = time();
	    $qb = $this->db->getQueryBuilder();
	    $qb->update($this->getTableName())
	->set('updated', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
	->where($qb->expr()->eq('id', $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT)));
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
	    $optionGroupsAlias = 'option_groups';
	    $this->joinUserRole($qb, self::TABLE, $currentUserId);
	    $this->joinGroupShares($qb, self::TABLE);
	    $this->joinOptionGroups($qb, self::TABLE, $optionGroupsAlias);
	    $this->joinOptionGroupShares($qb, $optionGroupsAlias, $currentUserId, $optionGroupsAlias);
	    $this->joinParticipantsCount($qb, self::TABLE);
	    $this->joinSupportsCount($qb, self::TABLE);
	    $this->joinCommentsCount($qb, self::TABLE);
	    $this->joinMiscs($qb, self::TABLE);

	    return $qb;
    }

    /**
     * Join misc settings from OptionMisc table
     */
    protected function joinMiscs(
	    IQueryBuilder &$qb,
	    string $fromAlias,
	    string $joinAlias = 'option_misc_settings'
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT CONCAT(' . $joinAlias . '.key, ":", ' . $joinAlias . '.value)) AS misc_settings_concat'));

	    $qb->leftJoin(
		    $fromAlias,
		    OptionMisc::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.option_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT))
		    )
	    );
    }

    /**
     * Load dynamic fields from OptionMisc for an option
     */
    private function loadDynamicFields(Option $option): void
    {
	    $qb = $this->db->getQueryBuilder();
	    $qb->select('*')
	->from(OptionMisc::TABLE)
	->where($qb->expr()->eq('option_id', $qb->createNamedParameter($option->getId(), IQueryBuilder::PARAM_INT)))
	->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

	    $stmt = $qb->executeQuery();
	    $miscSettings = $stmt->fetchAll();
	    $stmt->closeCursor();

	    $dynamicFields = [];
	    foreach ($miscSettings as $setting) {
		    $dynamicFields[$setting['key']] = $setting['value'];
	    }

	    $option->setDynamicFields($dynamicFields);

	    // Also parse concatenated misc settings if available
	    if (method_exists($option, 'getMiscsConcat') && $option->getMiscsConcat()) {
		    $concatSettings = explode(',', $option->getMiscsConcat());
		    foreach ($concatSettings as $concatSetting) {
			    if (strpos($concatSetting, ':') !== false) {
				    [$key, $value] = explode(':', $concatSetting, 2);
				    $dynamicFields[$key] = $value;
			    }
		    }
		    $option->setDynamicFields($dynamicFields);
	    }
    }

    /**
     * Save dynamic fields to OptionMisc
     */
    public function saveDynamicFields(Option $option): void
    {
	    $dynamicFields = $option->getDynamicFieldsForStorage();
	    $optionId = $option->getId();

	    // First, mark existing settings as deleted
	    $qb = $this->db->getQueryBuilder();
	    $qb->update(OptionMisc::TABLE)
	->set('deleted', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
	->where($qb->expr()->eq('option_id', $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT)))
	->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
	    $qb->executeStatement();

	    // Then insert new settings
	    foreach ($dynamicFields as $key => $value) {
		    $qb = $this->db->getQueryBuilder();
		    $qb->insert(OptionMisc::TABLE)
	 ->values([
		 'option_id' => $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT),
		 'key' => $qb->createNamedParameter($key, IQueryBuilder::PARAM_STR),
		 'value' => $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR),
		 'created' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
		 'deleted' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
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
			    $qb->expr()->eq($joinAlias . '.option_id', $fromAlias . '.id'),
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
			    $qb->expr()->eq($joinAlias . '.option_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.type', $qb->expr()->literal('group')),
			    $qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
		    )
	    );
    }

    protected function joinOptionGroups(
	    IQueryBuilder $qb,
	    string $fromAlias,
	    string $joinAlias = 'option_groups',
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT ' . $joinAlias . '.group_id) AS option_groups'));

	    $qb->leftJoin(
		    $fromAlias,
		    OptionGroup::RELATION_TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq(self::TABLE . '.id', $joinAlias . '.option_id'),
		    )
	    );
    }

    protected function joinOptionGroupShares(
	    IQueryBuilder $qb,
	    string $fromAlias,
	    string $currentUserId,
	    string $optionGroupsAlias,
	    string $joinAlias = 'option_group_shares',
    ): void {
	    $qb->addSelect($qb->createFunction('GROUP_CONCAT(DISTINCT ' . $joinAlias . '.type) AS option_group_user_shares'));

	    $qb->leftJoin(
		    $fromAlias,
		    Share::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.group_id', $optionGroupsAlias . '.group_id'),
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
		    $qb->expr()->eq($joinAlias . '.option_id', $fromAlias . '.id')
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
		    $qb->expr()->eq($joinAlias . '.option_id', $fromAlias . '.id')
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
		    Option::TABLE,
		    $joinAlias,
		    $qb->expr()->andX(
			    $qb->expr()->eq($joinAlias . '.parent_id', $fromAlias . '.id'),
			    $qb->expr()->eq($joinAlias . '.access', $qb->createNamedParameter('open'))
		    )
	    );
	    $qb->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.id)) AS count_participants'));
    }
}
