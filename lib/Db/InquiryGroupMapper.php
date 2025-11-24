<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Db;

use Exception;
use OCA\Agora\Helper\SqlHelper;
use OCA\Agora\UserSession;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<InquiryGroup>
 */
class InquiryGroupMapper extends QBMapper
{
    public const TABLE = InquiryGroup::TABLE;
    public const CONCAT_SEPARATOR = ',';

    /**
     * @psalm-suppress PossiblyUnusedMethod 
     */
    public function __construct(
        IDBConnection $db,
        private UserSession $userSession,
    ) {
        parent::__construct($db, InquiryGroup::TABLE, InquiryGroup::class);
    }

    /**
     * List all InquiryGroups
     *
     * @return InquiryGroup[]
     */
    public function list(): array
    {
        $qb = $this->buildQuery();
        $qb->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    /**
     * Find a InquiryGroup by its ID
     *
     * @param  int $id id off inquiry group
     * @return InquiryGroup
     */
    public function find(int $id): InquiryGroup
    {
        $qb = $this->buildQuery();

        $qb->where($qb->expr()->eq('g.id', $qb->createNamedParameter($id)));

        return $this->findEntity($qb);
    }

    /**
     * Find InquiryGroups by owner
     *
     * @param  string $owner
     * @return InquiryGroup[]
     */
    public function findByOwner(string $owner): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq('g.owner', $qb->createNamedParameter($owner)))
           ->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    /**
     * Find InquiryGroups by type
     *
     * @param  string $type
     * @return InquiryGroup[]
     */
    public function findByType(string $type): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq('g.type', $qb->createNamedParameter($type)))
           ->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    /**
     * Find InquiryGroups by status
     *
     * @param  string $status
     * @return InquiryGroup[]
     */
    public function findByStatus(string $status): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq('g.group_status', $qb->createNamedParameter($status)))
           ->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    /**
     * Find active (non-deleted) InquiryGroups
     *
     * @return InquiryGroup[]
     */
    public function findActive(): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->eq('g.deleted', $qb->createNamedParameter(0)))
           ->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    /**
     * Find expired InquiryGroups
     *
     * @return InquiryGroup[]
     */
    public function findExpired(): array
    {
        $qb = $this->buildQuery();
        $qb->where($qb->expr()->lt('g.expire', $qb->createNamedParameter(time())))
           ->andWhere($qb->expr()->isNotNull('g.expire'))
           ->orderBy('g.order', 'ASC')
           ->addOrderBy('g.title', 'ASC');
        return $this->findEntities($qb);
    }

    public function addInquiryToGroup(int $inquiryId, int $groupId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(InquiryGroup::RELATION_TABLE)
            ->setValue('inquiry_id', $qb->createNamedParameter($inquiryId))
            ->setValue('group_id', $qb->createNamedParameter($groupId));
        $qb->executeStatement();
    }

    /**
     * Remove a Inquiry from a InquiryGroup
     *
     * @param  int $inquiryId id of inquiry
     * @param  int $groupId   id of group
     * @throws Exception
     */
    public function removeInquiryFromGroup(int $inquiryId, int $groupId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->delete(InquiryGroup::RELATION_TABLE)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('inquiry_id', $qb->createNamedParameter($inquiryId)),
                    $qb->expr()->eq('group_id', $qb->createNamedParameter($groupId))
                )
            );
        $qb->executeStatement();
    }

    public function add(InquiryGroup $inquiryGroup): InquiryGroup
    {
        $inquiryGroup->setCreated(time());
        $inquiryGroup->setOwner($this->userSession->getCurrentUserId());
        return $this->insert($inquiryGroup);
    }

    public function tidyInquiryGroups(): void
    {
        $qb = $this->db->getQueryBuilder();

        $subquery = $this->db->getQueryBuilder();
        $subquery->selectDistinct('group_id')->from(InquiryGroup::RELATION_TABLE);

        $qb->delete(InquiryGroup::TABLE)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->notIn(
                        'id',
                        $qb->createFunction($subquery->getSQL()),
                        IQueryBuilder::PARAM_INT_ARRAY
                    ),
                    $qb->expr()->eq('protected', $qb->createNamedParameter(false))
                )
            );
        $qb->executeStatement();
    }

    /**
     * Update the order of an inquiry group
     */
    public function updateOrder(int $groupId, int $order): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update(InquiryGroup::TABLE)
            ->set('order', $qb->createNamedParameter($order))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($groupId)));
        $qb->executeStatement();
    }

    /**
     * Soft delete an inquiry group
     */
    public function softDelete(int $groupId): void
    {
        $qb = $this->db->getQueryBuilder();
        $qb->update(InquiryGroup::TABLE)
            ->set('deleted', $qb->createNamedParameter(time()))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($groupId)));
        $qb->executeStatement();
    }

    /**
     * Build the enhanced query with joined tables
     */
    protected function buildQuery(): IQueryBuilder
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('g.*')
            ->from($this->getTableName(), 'g')
            ->groupBy('g.id');

        // Join inquiries
        $this->joinInquiryIds($qb);

        return $qb;
    }

    protected function joinInquiryIds(
        IQueryBuilder $qb,
        string $joinAlias = 'inqs',
    ): void {
        SqlHelper::getConcatenatedArray(
            qb: $qb,
            concatColumn: $joinAlias . '.inquiry_id',
            asColumn: 'inquiry_ids',
            dbProvider: $this->db->getDatabaseProvider(),
        );

        $qb->leftJoin(
            'g',
            InquiryGroup::RELATION_TABLE,
            $joinAlias,
            $qb->expr()->andX(
                $qb->expr()->eq('g.id', $joinAlias . '.group_id'),
            )
        );
    }
}
