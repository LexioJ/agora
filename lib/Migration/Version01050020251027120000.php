<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Migration;

use OCA\Agora\Db\IndexManager;
use OCA\Agora\Db\TableManager;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration from Agora version 1.1 to 1.5
 * Uses TableManager and IndexManager to handle schema changes and data migration
 */
class Version01050020251027120000 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;
	private ?IOutput $output = null;

	public function __construct(
		private TableManager $tableManager,
		private IndexManager $indexManager,
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'Migrate Agora from version 1.1 to 1.5';
	}

	public function description(): string {
		return 'Updates database schema and migrates data to new structure';
	}

	/**
	 * Pre-migration steps for data preparation
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Preparing migration');

		// Préparer les tables en supprimant les contraintes temporairement si nécessaire
		$this->indexManager->createSchema();
		$message = $this->indexManager->removeUniqueIndicesFromTable('shares');
		$this->logInfo($message, 'preMigration:  ');
		$message = $this->indexManager->removeForeignKeysFromTable('shares');
		$this->logInfo($message, 'preMigration:  ');
		$this->indexManager->migrateToSchema();

		// Nettoyer les données temporaires
		$message = $this->tableManager->tidyWatchTable(time());
		$this->logInfo($message, 'preMigration:  ');

		// Préparer les données pour la migration
		$message = $this->tableManager->prepareDataMigration();
		$this->logInfo($message, 'preMigration:  ');
	}

	/**
	 * Main schema migration using TableSchema definitions
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$this->output = $output;
		$this->schema = $schemaClosure();
		$this->tableManager->setConnection($this->connection);
		$this->tableManager->setSchema($this->schema);

		// Créer/mettre à jour les tables selon TableSchema
		$message = $this->tableManager->createTables();
		$this->logInfo($message, 'runMigration:  ');

		if (!($this->schema instanceof ISchemaWrapper)) {
			return null;
		}

		return $this->schema;
	}

	/**
	 * Post-migration steps for data transformation and cleanup
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Finalizing migration');

		// Migrer les données vers les nouvelles structures
		$message = $this->tableManager->migrateModerationToInquiryStatus();
		$this->logInfo($message, 'postMigration: ');

		$message = $this->tableManager->migrateMiscSettings();
		$this->logInfo($message, 'postMigration: ');

		$message = $this->tableManager->migrateAssemblyToInquiryFamily();
		$this->logInfo($message, 'postMigration: ');

		// Mettre à jour les hash de support
		$message = $this->tableManager->updateSupportHashes();
		$this->logInfo($message, 'postMigration: ');

		// Nettoyer les données dupliquées
		$message = $this->tableManager->deleteAllDuplicates();
		$this->logInfo($message, 'postMigration:  ');

		// Supprimer les données orphelines
		$message = $this->tableManager->removeOrphaned();
		$this->logInfo($message, 'postMigration:  ');

		// Supprimer les tables et colonnes obsolètes
		$message = $this->tableManager->removeObsoleteTables();
		$this->logInfo($message, 'postMigration: ');

		$this->tableManager->createSchema();
		$message = $this->tableManager->removeObsoleteColumns();
		$this->logInfo($message, 'postMigration: ');
		$this->tableManager->migrateToSchema();

		// Recréer les contraintes et indices
		$this->indexManager->createSchema();
		$message = $this->indexManager->createForeignKeyConstraints();
		$this->logInfo($message, 'postMigration:  ');

		$message = $this->indexManager->createUniqueIndices();
		$this->logInfo($message, 'postMigration:  ');

		// Les indices optionnels peuvent être créés via 'occ db:add-missing-indices'
		$this->indexManager->migrateToSchema();

		$this->logInfo('Migration completed successfully');
	}

	/**
	 * Logs the given message to the output.
	 */
	private function logInfo(string|array $message, string $prefix = ''): void {
		if ($this->output) {
			if (is_array($message)) {
				foreach ($message as $msg) {
					$this->output->info($prefix . 'Agora - ' . $msg);
				}
			} else {
				$this->output->info($prefix . 'Agora - ' . $message);
			}
		}
	}
}
