-- =======================================================
-- AGORA DATASET POLYNÉSIE FRANÇAISE
-- Version adaptée du modèle suisse hiérarchique
-- =======================================================

-- ============================
-- Table: oc_agora_inq_group
-- ============================

INSERT INTO oc_agora_inq_group (id, parent_id, created, deleted, title, type, owner, description, title_ext, owned_group, expire, metadata, cover_id, protected, group_status, allow_edit) VALUES
-- === Programmes nationaux (niveau racine) ===
(1, NULL, UNIX_TIMESTAMP() - 86400*90, 0, 'Stratégie Climatique de la Polynésie 2030', 'program', 'admin', 'Stratégie transversale pour la résilience climatique, la protection des océans et la transition énergétique des archipels.', 'Plan Climat', 'gouvernement-polynesie', UNIX_TIMESTAMP() + 86400*365*5, '{"budget": "12.5B XPF", "ministre": "Ministre de l''Environnement"}', NULL, 1, 'active', 1),
(2, NULL, UNIX_TIMESTAMP() - 86400*60, 0, 'Polynésie Numérique 2025-2030', 'program', 'admin', 'Modernisation des services publics numériques et connectivité inter-îles.', 'Transition Numérique', 'gouvernement-polynesie', UNIX_TIMESTAMP() + 86400*365*3, '{"budget": "3.2B XPF", "timeline": "2025-2030"}', NULL, 1, 'active', 1),
(31, NULL, UNIX_TIMESTAMP() - 86400*30, 0, 'Stratégie Santé des Populations 2030', 'program', 'admin', 'Cadre de santé publique pour les archipels éloignés et prévention des maladies non-transmissibles.', 'Santé 2030', 'gouvernement-polynesie', UNIX_TIMESTAMP() + 86400*365*5, '{"focus": "désert_medical,prevention,seniors"}', NULL, 1, 'active', 1),
(32, NULL, UNIX_TIMESTAMP() - 86400*45, 0, 'Politique Agricole et Halieutique 2023+', 'program', 'admin', 'Souveraineté alimentaire, agriculture durable et gestion des ressources marines.', 'PAH 23+', 'gouvernement-polynesie', UNIX_TIMESTAMP() + 86400*365*7, '{"budget": "2.1B XPF/an", "target": "autosuffisance_30%"}', NULL, 1, 'active', 1),

-- === Assemblées (niveau racine) ===
(3, NULL, UNIX_TIMESTAMP() - 86400*180, 0, 'Assemblée Citoyenne de Tahiti', 'assembly', 'admin', 'Assemblée générale des résidents de l''île de Tahiti pour délibérer sur les enjeux locaux.', 'Huiraatira no Tahiti', 'tahiti-residents', UNIX_TIMESTAMP() + 86400*365, '{"quorum": 80, "venue": "Hôtel de Ville de Papeete"}', NULL, 0, 'active', 1),
(4, NULL, UNIX_TIMESTAMP() - 86400*150, 0, 'Assemblée des Îles Sous-le-Vent', 'assembly', 'admin', 'Assemblée générale des résidents des Îles Sous-le-Vent (Raiatea, Tahaa, Huahine, Bora Bora).', 'Huiraatira no te Tuhaa Pae', 'iles-sous-le-vent-residents', UNIX_TIMESTAMP() + 86400*365, '{"quorum": 50, "venue": "Mairie d''Uturoa"}', NULL, 0, 'active', 1),
(33, NULL, UNIX_TIMESTAMP() - 86400*120, 0, 'Conseil Consultatif des Marquises', 'assembly', 'admin', 'Instance de consultation et de délibération citoyenne pour l''archipel des Marquises.', 'Hui Haavā no te Henua Enata', 'marquises-residents', UNIX_TIMESTAMP() + 86400*365, '{"quorum": 30, "venue": "Taiohae, Nuku Hiva"}', NULL, 0, 'active', 1),

-- === Archipels (niveau racine - équivalent cantons) ===
(5, NULL, UNIX_TIMESTAMP() - 86400*365, 0, 'Îles de la Société', 'canton', 'admin', 'Archipel principal de la Polynésie française, comprenant les Îles du Vent et les Îles Sous-le-Vent.', 'Te Fenua A’ai', 'archipel-societe-gov', NULL, '{"capital": "Papeete", "population": 245000, "langues": "français, reo tahiti"}', NULL, 1, 'active', 1),
(6, NULL, UNIX_TIMESTAMP() - 86400*365, 0, 'Îles Tuamotu', 'canton', 'admin', 'Archipel d''atolls, patrimoine UNESCO, axé sur la perliculture et le tourisme.', 'Te Tuāmotu', 'archipel-tuamotu-gov', NULL, '{"capital": "Rangiroa", "population": 17000, "langues": "français, reo tuamotu"}', NULL, 1, 'active', 1),
(7, NULL, UNIX_TIMESTAMP() - 86400*365, 0, 'Îles Marquises', 'canton', 'admin', 'Archipel nordique, culturellement distinct, avec une autonomie administrative renforcée.', 'Te Henua Enata', 'archipel-marquises-gov', NULL, '{"capital": "Taiohae", "population": 9600, "langues": "français, reo marquisien"}', NULL, 1, 'active', 1),
(35, NULL, UNIX_TIMESTAMP() - 86400*365, 0, 'Îles Australes', 'canton', 'admin', 'Archipel méridional, aux traditions agricoles et artisanales préservées.', 'Te Tuhaa Pae', 'archipel-australes-gov', NULL, '{"capital": "Tubuai", "population": 7000, "langues": "français, reo austral"}', NULL, 1, 'active', 1),

-- === Districts (enfants des archipels) ===
(8, 5, UNIX_TIMESTAMP() - 86400*200, 0, 'Îles du Vent', 'district', 'admin', 'Subdivision administrative de l''archipel de la Société (Tahiti, Moorea).', 'Te Moana', 'archipel-societe-gov', NULL, '{"superficie": "1045 km²", "communes": 12}', NULL, 0, 'active', 1),
(9, 5, UNIX_TIMESTAMP() - 86400*200, 0, 'Îles Sous-le-Vent', 'district', 'admin', 'Subdivision administrative de l''archipel de la Société (Bora Bora, Raiatea, Tahaa, Huahine).', 'Te Tua Rā', 'archipel-societe-gov', NULL, '{"superficie": "404 km²", "communes": 5}', NULL, 0, 'active', 1),
(10, 6, UNIX_TIMESTAMP() - 86400*200, 0, 'District de Rangiroa', 'district', 'admin', 'District administratif du nord des Tuamotu, centré sur l''atoll de Rangiroa.', 'Hāpītu no Rangiroa', 'archipel-tuamotu-gov', NULL, '{"superficie": "79 km² (terre)", "communes": 2}', NULL, 0, 'active', 1),
(37, 7, UNIX_TIMESTAMP() - 86400*200, 0, 'District Nord des Marquises', 'district', 'admin', 'District administratif des îles septentrionales des Marquises.', 'Hāpītu a Toa o te Henua Enata', 'archipel-marquises-gov', NULL, '{"superficie": "1127 km²", "communes": 3}', NULL, 0, 'active', 1),

-- === Communes (enfants des districts) ===
(11, 8, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Papeete', 'commune', 'admin', 'Capitale de la Polynésie française, située sur l''île de Tahiti.', 'Faa’apu no Pape’ete', 'papeete-residents', NULL, '{"code_postal": "98714", "maire": "Michel Buillard", "population": 26926}', NULL, 0, 'active', 1),
(12, 8, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Moorea-Maiao', 'commune', 'admin', 'Commune de l''île de Moorea, incluant l''îlot de Maiao.', 'Faa’apu no Mo’orea-Maiao', 'moorea-residents', NULL, '{"code_postal": "98729", "maire": "Tearii Alpha", "population": 17487}', NULL, 0, 'active', 1),
(13, 9, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Bora Bora', 'commune', 'admin', 'Commune de l''île de Bora Bora, destination touristique mondialement connue.', 'Faa’apu no Porapora', 'borabora-residents', NULL, '{"code_postal": "98730", "maire": "Gaston Tong Sang", "population": 10705}', NULL, 0, 'active', 1),
(14, 10, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Rangiroa', 'commune', 'admin', 'Commune de l''atoll de Rangiroa, plus grand atoll des Tuamotu.', 'Faa’apu no Rangiroa', 'rangiroa-residents', NULL, '{"code_postal": "98775", "maire": "Teina Maraeura", "population": 2567}', NULL, 0, 'active', 1),
(15, 37, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Nuku Hiva', 'commune', 'admin', 'Commune de l''île de Nuku Hiva, centre administratif des Marquises.', 'Faa’apu no Nuku Hiva', 'nukuhiva-residents', NULL, '{"code_postal": "98743", "maire": "Benoît Kautai", "population": 2960}', NULL, 0, 'active', 1),
(39, 35, UNIX_TIMESTAMP() - 86400*100, 0, 'Commune de Tubuai', 'commune', 'admin', 'Commune de l''île de Tubuai, centre administratif des Australes.', 'Faa’apu no Tupua’i', 'tubuai-residents', NULL, '{"code_postal": "98754", "maire": "Fernand Pitomohi", "population": 2171}', NULL, 0, 'active', 1),

-- === Groupes de travail (divers parents) ===
(16, 1, UNIX_TIMESTAMP() - 86400*80, 0, 'Groupe de travail Énergies Renouvelables', 'working_group', 'admin', 'Cellule de coordination pour le développement du solaire, de l''hydrogène vert et de l''énergie des mers.', 'GT Énergie', 'experts-gouvernement', UNIX_TIMESTAMP() + 86400*180, '{"president": "Dr. Hinano Teavai", "frequence_reunion": "bimensuelle", "mandat": "mix_energetique"}', NULL, 0, 'active', 1),
(17, 1, UNIX_TIMESTAMP() - 86400*75, 0, 'Groupe de travail Mobilité Inter-îles Durable', 'working_group', 'admin', 'Développement de solutions de transport maritime et aérien décarboné entre les archipels.', 'GT Mobilité', 'experts-gouvernement', UNIX_TIMESTAMP() + 86400*180, '{"president": "Prof. Temahere Auna", "frequence_reunion": "mensuelle", "mandat": "liaisons_maritimes"}', NULL, 0, 'active', 1),
(18, 2, UNIX_TIMESTAMP() - 86400*70, 0, 'Cellule Éthique et Données Numériques', 'working_group', 'admin', 'Groupe de réflexion sur la protection des données personnelles et l''éthique de l''IA dans le contexte polynésien.', 'Cellule Éthique', 'experts-gouvernement', UNIX_TIMESTAMP() + 86400*240, '{"president": "Prof. Mareva Tihoni", "frequence_reunion": "mensuelle", "mandat": "protection_donnees"}', NULL, 0, 'active', 1),
(19, 11, UNIX_TIMESTAMP() - 86400*60, 0, 'Comité des Transports de Papeete', 'working_group', 'admin', 'Comité local pour la planification de la mobilité urbaine à Papeete.', 'Comité Transport', 'conseil-papeete', UNIX_TIMESTAMP() + 86400*90, '{"president": "M. Heremoana Richmond", "frequence_reunion": "mensuelle", "mandat": "trafic_urbain"}', NULL, 0, 'active', 1),
(20, 13, UNIX_TIMESTAMP() - 86400*55, 0, 'Groupe Stratégie Touristique Bora Bora', 'working_group', 'admin', 'Développement d''une stratégie de tourisme durable pour Bora Bora.', 'GT Tourisme', 'conseil-borabora', UNIX_TIMESTAMP() + 86400*120, '{"president": "Mme. Vaitiare Bugnard", "frequence_reunion": "bimensuelle", "mandat": "tourisme_durable"}', NULL, 0, 'active', 1),
(42, 31, UNIX_TIMESTAMP() - 86400*50, 0, 'Groupe de travail Prévention Santé', 'working_group', 'admin', 'Stratégies de prévention des maladies non-transmissibles et promotion de la santé dans les îles.', 'GT Prévention', 'experts-sante', UNIX_TIMESTAMP() + 86400*200, '{"president": "Dr. Teraitua Clay", "frequence_reunion": "mensuelle", "mandat": "prevention"}', NULL, 0, 'active', 1),

-- === Commissions (divers parents) ===
(21, 5, UNIX_TIMESTAMP() - 86400*365, 0, 'Commission Environnement des Îles de la Société', 'commission', 'admin', 'Comité d''experts pour l''écologie et la protection du lagon.', 'Tāura Taiao', 'experts-societe', NULL, '{"presidente": "Mme. Hinanui Cauchois", "membres": 12, "mandat": "2022-2026"}', NULL, 0, 'active', 1),
(22, 6, UNIX_TIMESTAMP() - 86400*365, 0, 'Commission des Ressources Marines des Tuamotu', 'commission', 'admin', 'Surveillance budgétaire et supervision des aides à la perliculture et à la pêche.', 'Tāura Tai', 'experts-tuamotu', NULL, '{"president": "M. Teariki Timeri", "membres": 10, "mandat": "2022-2026"}', NULL, 0, 'active', 1),
(23, 7, UNIX_TIMESTAMP() - 86400*365, 0, 'Commission Culture et Patrimoine des Marquises', 'commission', 'admin', 'Audit et supervision des systèmes numériques publics et préservation du patrimoine culturel immatériel.', 'Tāura Tumu', 'experts-marquises', NULL, '{"president": "M. Pascal Erhel Hatuuku", "membres": 8, "mandat": "2023-2027"}', NULL, 0, 'active', 1),
(45, 35, UNIX_TIMESTAMP() - 86400*365, 0, 'Commission Tourisme des Australes', 'commission', 'admin', 'Comité pour le développement et la promotion du tourisme culturel et naturel.', 'Tāura Haere', 'experts-australes', NULL, '{"presidente": "Mme. Hinano Vernaudon", "membres": 7, "mandat": "2023-2027"}', NULL, 0, 'active', 1),

-- === Bundles (divers parents) ===
(24, 5, UNIX_TIMESTAMP() - 86400*40, 0, 'Paquet Aménagement Tahiti 2025', 'bundle', 'admin', 'Ensemble de réformes urbaines pour le développement de Tahiti.', 'Schéma d''Aménagement', 'service-amenagement', UNIX_TIMESTAMP() + 86400*365, '{"theme": "amenagement_urbain", "service_pilote": "Service de l''Urbanisme"}', NULL, 0, 'active', 1),
(25, 6, UNIX_TIMESTAMP() - 86400*35, 0, 'Paquet Gestion des Atolls (Tuamotu)', 'bundle', 'admin', 'Politique intégrée pour la gestion des ressources en eau et la protection des atolls.', 'Plan Atolls', 'service-environnement', UNIX_TIMESTAMP() + 86400*365, '{"theme": "resilience_atolls", "service_pilote": "Service de l''Environnement"}', NULL, 0, 'active', 1),
(47, 7, UNIX_TIMESTAMP() - 86400*30, 0, 'Paquet Patrimoine Marquises', 'bundle', 'admin', 'Projets de transformation numérique et de valorisation culturelle pour les Marquises.', 'Plan Numérique', 'service-culture', UNIX_TIMESTAMP() + 86400*365, '{"theme": "patrimoine_numerique", "service_pilote": "Service de la Culture"}', NULL, 0, 'active', 1),

-- === Archives ===
(26, 5, UNIX_TIMESTAMP() - 86400*730, 0, 'Archives Consultations Îles de la Société', 'archive', 'admin', 'Consultations achevées pour les Îles de la Société (2019-2024).', 'Pūrua Parau', 'archives-societe', NULL, '{"archiviste": "M. Jean-Pierre Kaddour", "duree_conservation": "10 ans"}', NULL, 1, 'active', 0),
(27, NULL, UNIX_TIMESTAMP() - 86400*730, 0, 'Archives Nationales des Consultations', 'archive', 'admin', 'Consultations nationales archivées (2021-2025).', 'Te Fare Upa Rau', 'archives-nationales', NULL, '{"archiviste": "Archives de la Polynésie", "duree_conservation": "permanent"}', NULL, 1, 'active', 0),
(49, 7, UNIX_TIMESTAMP() - 86400*730, 0, 'Archives Historiques des Marquises', 'archive', 'admin', 'Délibérations closes des Marquises (2016-2023).', 'Te Petepete', 'archives-marquises', NULL, '{"archiviste": "M. Hikuroa Temaiana", "duree_conservation": "7 ans"}', NULL, 1, 'active', 0),

-- === Jurys citoyens ===
(28, 5, UNIX_TIMESTAMP() - 86400*60, 0, 'Jury Citoyen Pollution Lagonaire (Tahiti)', 'citizen_jury', 'admin', 'Citoyens tirés au sort pour délibérer sur la régulation des rejets en lagons.', 'Arioi Taiao', 'tahiti-residents', UNIX_TIMESTAMP() + 86400*30, '{"methode_tirage": "aleatoire", "duree": "3 mois", "compensation": "oui"}', NULL, 0, 'active', 1),
(50, 35, UNIX_TIMESTAMP() - 86400*45, 0, 'Jury Citoyen Autonomie Alimentaire (Australes)', 'citizen_jury', 'admin', 'Délibération citoyenne sur les modèles d''agriculture locale et d''autosuffisance.', 'Arioi Ma’a', 'australes-residents', UNIX_TIMESTAMP() + 86400*45, '{"methode_tirage": "aleatoire_stratifie", "duree": "4 mois", "compensation": "oui"}', NULL, 0, 'active', 1),

-- === Ensembles de consultations ===
(29, 31, UNIX_TIMESTAMP() - 86400*50, 0, 'Consultations Nationales Santé des Îles', 'consultation_set', 'admin', 'Série de consultations publiques sur la santé dans tous les archipels.', 'Parau Tūroro', 'sante-publique', UNIX_TIMESTAMP() + 86400*180, '{"theme": "sante_publique", "coordinateur": "Direction de la Santé", "date_limite": "2025-08-30"}', NULL, 0, 'active', 1),
(52, 1, UNIX_TIMESTAMP() - 86400*40, 0, 'Dialogues Énergie Polynésienne', 'consultation_set', 'admin', 'Dialogues publics sur les scénarios de transition énergétique.', 'Parau Uira', 'energie-publique', UNIX_TIMESTAMP() + 86400*150, '{"theme": "transition_energetique", "coordinateur": "Service de l''Énergie", "date_limite": "2025-07-31"}', NULL, 0, 'active', 1),

-- === Groupe de travail agricole (manquant) ===
(43, 32, UNIX_TIMESTAMP() - 86400*50, 0, 'Groupe de travail Agriculture Organique', 'working_group', 'admin', 'Groupe de développement de l''agriculture biologique et des circuits courts.', 'GT Bio', 'experts-agriculture', UNIX_TIMESTAMP() + 86400*200, '{"president": "Dr. Raimana Doom", "frequence_reunion": "mensuelle"}', NULL, 0, 'active', 1),

-- === Groupes référendaires ===
(30, 5, UNIX_TIMESTAMP() - 86400*90, 0, 'Référendum Taxe de Séjour (Tahiti)', 'referendum_group', 'admin', 'Référendum sur la modification de la taxe de séjour touristique (Octobre 2025).', 'Referendum Takau', 'tahiti-votants', UNIX_TIMESTAMP() + 86400*60, '{"date_referendum": "2025-10-12", "signatures_requises": 8000, "budget_campagne": "5M XPF"}', NULL, 0, 'active', 1),
(54, 7, UNIX_TIMESTAMP() - 86400*80, 0, 'Référendum Statut Autonome (Marquises)', 'referendum_group', 'admin', 'Référendum consultatif sur un statut d''autonomie renforcée pour les Marquises.', 'Referendum Tumu', 'marquises-votants', UNIX_TIMESTAMP() + 86400*75, '{"date_referendum": "2025-11-20", "signatures_requises": 2000, "budget_campagne": "2M XPF"}', NULL, 0, 'active', 1);


-- ============================
-- Table: oc_agora_inq_group_misc
-- ============================

INSERT INTO oc_agora_inq_group_misc (inquiry_group_id, `key`, value) VALUES
-- Métadonnées supplémentaires
(1, 'language', 'fr,ty,mg,other'),
(5, 'language', 'fr,ty'),
(6, 'language', 'fr,pmt'),
(7, 'language', 'fr,mqj'),
(28, 'juror_count', '25'),
(28, 'meeting_dates', '2025-03-15,2025-03-29,2025-04-12'),
(30, 'referendum_number', 'PF-TAH-2025-045'),
(54, 'referendum_number', 'PF-MQ-2025-128');



-- =======================================================
-- AGORA DATASET POLYNÉSIE FRANÇAISE - STRUCTURE COMPLÈTE
-- Version avec BIGINT et schéma exact
-- =======================================================

-- ============================
-- Table: oc_agora_locations (BIGINT)
-- ============================
CREATE TABLE IF NOT EXISTS oc_agora_locations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    parent_id BIGINT DEFAULT 0,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- Table: oc_agora_categories (BIGINT)
-- ============================
CREATE TABLE IF NOT EXISTS oc_agora_categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    parent_id BIGINT DEFAULT 0,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- Insertion: Localisations Polynésie Française
-- ============================
INSERT INTO oc_agora_locations (name, parent_id) VALUES
-- --- Pays/Territoire ---
('Polynésie Française', 0),

-- --- Archipels ---
('Îles de la Société', 1),
('Îles Tuamotu', 1),
('Îles Marquises', 1),
('Îles Australes', 1),
('Îles Gambier', 1),

-- --- Districts Îles de la Société ---
('Îles du Vent', 2),
('Îles Sous-le-Vent', 2),

-- --- Districts Îles Tuamotu ---
('Rangiroa District', 3),
('Fakarava District', 3),

-- --- Districts Îles Marquises ---
('Nuku Hiva District', 4),
('Hiva Oa District', 4),

-- --- Districts Îles Australes ---
('Tubuai District', 5),
('Rurutu District', 5),

-- --- Communes/Villes ---
-- Îles du Vent
('Papeete', 6),
('Mahina', 6),
('Pirae', 6),
('Arue', 6),
('Moorea-Maiao', 6),

-- Îles Sous-le-Vent
('Uturoa (Raiatea)', 7),
('Bora Bora', 7),
('Tahaa', 7),
('Huahine', 7),

-- Tuamotu
('Rangiroa', 8),
('Fakarava', 9),
('Tikehau', 8),

-- Marquises
('Taiohae (Nuku Hiva)', 10),
('Atuona (Hiva Oa)', 11),

-- Australes
('Tubuai', 12),
('Rurutu', 13),
('Raivavae', 12),

-- --- Localisations composites (pour misc) ---
('Îles Sous-le-Vent, Société', 7),
('Rangiroa, Tuamotu', 8),
('Nord Marquises', 10);

-- ============================
-- Insertion: Catégories (traduites/adaptées)
-- ============================
INSERT INTO oc_agora_categories (name, parent_id) VALUES
-- 1. Écologie & Ressources
('Écologie & Ressources', 0),
('Biodiversité', 1),
('Eau & Lagon', 1),
('Terre & Agriculture', 1),
('Énergie', 1),
('Déchets & Recyclage', 1),

-- 2. Aménagement & Développement
('Aménagement & Développement', 0),
('Logement & Urbanisme', 2),
('Transport', 2),
('Travaux Publics', 2),
('Tourisme', 2),
('Économie Locale', 2),

-- 3. Santé & Bien-être
('Santé & Bien-être', 0),
('Soins & Prévention', 3),
('Accès aux Soins', 3),
('Sports', 3),
('Alimentation & Nutrition', 3),
('Environnement Sain', 3),

-- 4. Citoyenneté & Société
('Citoyenneté & Société', 0),
('Participation', 4),
('Associations', 4),
('Culture & Patrimoine', 4),
('Sécurité', 4),
('Jeunesse & Seniors', 4),

-- 5. Éducation & Spiritualité
('Éducation & Spiritualité', 0),
('École', 5),
('Formation', 5),
('Langue & Identité', 5),
('Spiritualité', 5),
('Arts & Créativité', 5);

-- ============================
-- Table: oc_agora_inquiries (AVEC location_id et category_id CORRECTS)
-- Important: On utilise maintenant les IDs BIGINT des tables ci-dessus
-- ============================

-- D'abord, vérifions/truncations si nécessaire
TRUNCATE TABLE oc_agora_inquiries;

INSERT INTO oc_agora_inquiries
(id, cover_id, type, title, description, location_id, category_id, owner, created, archived, expire, deleted, owned_group, access, show_results, last_interaction, parent_id, moderation_status, inquiry_status, allow_comment, allow_support) VALUES

-- === Inquiries Programme Climat (location: 1=Polynésie Française, category: 5=Énergie) ===
(1, NULL, 'news', 'Le Gouvernement adopte de nouvelles mesures climatiques', 'La Polynésie actualise ses objectifs climatiques pour 2030 avec des objectifs de réduction de CO2 renforcés pour le transport maritime et le bâtiment.', 1, 5, 'admin', UNIX_TIMESTAMP() - 86400*20, 0, 0, 0, 'gouvernement-polynesie', 'open', 'always', UNIX_TIMESTAMP() - 86400*5, NULL, 'accepted', 'published', 1, 1),

(11, NULL, 'consultation', 'Consultation publique : Développement des Énergies Renouvelables 2025-2030', 'Saisine du public sur les objectifs de développement du solaire et de l''énergie des mers, et les programmes de soutien pour les 5 prochaines années.', 1, 5, 'admin', UNIX_TIMESTAMP() - 86400*15, 0, UNIX_TIMESTAMP() + 86400*45, 0, 'service-energie', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),

(12, NULL, 'meeting', 'Réunion trimestrielle du GT Énergie : Bilan d''exécution', 'Réunion pour discuter des progrès de la mise en œuvre de la transition énergétique et de l''allocation budgétaire.', 14, 1, 'admin', UNIX_TIMESTAMP() - 86400*10, 0, UNIX_TIMESTAMP() + 86400*5, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 0),

(13, NULL, 'proposal', 'Proposition : Cadre d''affectation des revenus de la taxe carbone', 'Proposition détaillée sur l''affectation des revenus de la contribution climat entre fonds de transition, redistribution et programmes d''innovation.', 1, 5, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*60, 0, 'gouvernement-polynesie', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 1),

-- === Programme Numérique (location: 1=Polynésie Française, category: 12=Économie Locale) ===
(14, NULL, 'consultation', 'Consultation publique sur l''Identité Numérique Polynésienne', 'Retours du public sur le système d''identité numérique proposé : fonctionnalités, garanties de confidentialité et calendrier.', 1, 12, 'admin', UNIX_TIMESTAMP() - 86400*25, 0, UNIX_TIMESTAMP() + 86400*30, 0, 'service-numerique', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0),

(15, NULL, 'deliberation', 'Débat d''experts : Cadre de régulation de l''IA', 'Débat structuré d''experts sur la gouvernance de l''IA, la classification des risques et les approches réglementaires pour la Polynésie.', 1, 12, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, UNIX_TIMESTAMP() + 86400*15, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),

(16, NULL, 'announcement', 'Lancement de la nouvelle plateforme API publique : api.pf', 'L''administration ouvre une nouvelle plateforme API pour les développeurs afin d''accéder aux données et services publics.', 1, 12, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, 0, 0, 'service-numerique', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 1, 0),

-- === Programme Santé (location: 1=Polynésie Française, category: 14=Soins & Prévention) ===
(17, NULL, 'consultation', 'Stratégie Santé Mentale 2025-2030 : Saisine du public', 'Consultation publique sur le nouveau cadre des services de santé mentale, des programmes de prévention et des systèmes de soutien.', 1, 14, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, UNIX_TIMESTAMP() + 86400*40, 0, 'sante-publique', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 0),

(18, NULL, 'meeting', 'Groupe de travail Prévention : Session stratégique mensuelle', 'Session mensuelle du groupe de travail pour développer des programmes de prévention des maladies non transmissibles.', 14, 14, 'admin', UNIX_TIMESTAMP() - 86400*8, 0, UNIX_TIMESTAMP() + 86400*2, 0, 'experts-sante', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- === Assemblée Citoyenne de Tahiti (location: 14=Papeete, category: 17=Participation) ===
(2, NULL, 'meeting', 'Assemblée Citoyenne de Tahiti : Délibération sur le Budget 2026', 'Les résidents de Tahiti sont invités à discuter et à donner leur avis sur les priorités du budget municipal 2026.', 14, 17, 'admin', UNIX_TIMESTAMP() - 86400*35, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*6, NULL, 'accepted', 'active', 1, 0),

(19, NULL, 'deliberation', 'Projets d''Aménagement Urbain : Débat Public', 'Les citoyens débattent des grands projets de construction, y compris les nouveaux logements, parcs et infrastructures.', 14, 17, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*20, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 1),

-- === Communes locales ===
(3, NULL, 'consultation', 'Extension des pistes cyclables à Moorea : Planification du tracé', 'Consultation publique sur les nouvelles infrastructures cyclables, y compris les voies protégées et les stationnements.', 19, 8, 'admin', UNIX_TIMESTAMP() - 86400*28, 0, UNIX_TIMESTAMP() + 86400*15, 0, 'moorea-residents', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),

(20, NULL, 'proposal', 'Proposition d''extension de la zone piétonne à Papeete', 'Proposition d''étendre la zone sans voiture au centre-ville pour inclure la rue des Remparts et améliorer les espaces publics.', 14, 8, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, UNIX_TIMESTAMP() + 86400*25, 0, 'papeete-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 1),

(21, NULL, 'meeting', 'Réunion communale de Rangiroa : Réunion mensuelle', 'Réunion communale mensuelle ouverte à tous les résidents pour les affaires locales et les annonces.', 24, 17, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*1, 0, 'rangiroa-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 0),

-- === Groupes de travail ===
(4, NULL, 'proposal', 'Révision du programme de subventions pour panneaux solaires', 'Proposition pour des incitations résidentielles actualisées à l''énergie solaire, incluant le soutien au stockage par batterie.', 1, 5, 'admin', UNIX_TIMESTAMP() - 86400*25, 0, UNIX_TIMESTAMP() + 86400*35, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 1),

(5, NULL, 'announcement', 'Nouvelles règles de mobilité pour trottinettes et vélos électriques', 'Réglementations actualisées pour les engins de micromobilité, y compris les zones de stationnement et les limites de vitesse.', 1, 8, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, 0, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 0, 0),

(6, NULL, 'consultation', 'Stratégie Logement Abordable de Bora Bora 2025-2030', 'Avis du public sur les nouvelles politiques du logement, incluant le contrôle des loyers, les quotas de logements sociaux et les objectifs de construction.', 22, 7, 'admin', UNIX_TIMESTAMP() - 86400*15, 0, UNIX_TIMESTAMP() + 86400*30, 0, 'borabora-residents', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),

(7, NULL, 'deliberation', 'Normes de confidentialité numérique : Débat technique', 'Débat technique sur les normes de protection des données pour les services numériques publics.', 1, 12, 'admin', UNIX_TIMESTAMP() - 86400*14, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),

(8, NULL, 'meeting', 'Comité des Transports de Papeete : Réunion mensuelle sur la mobilité', 'Réunion mensuelle du comité de mobilité pour discuter des problèmes et projets de transport locaux.', 14, 8, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*2, 0, 'conseil-papeete', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- === Jurys citoyens ===
(9, NULL, 'news', 'Pollution Lagonaire : Les conclusions du Jury Citoyen publiées', 'Conclusions et recommandations de la délibération du jury citoyen sur la régulation des rejets en lagons.', 14, 3, 'admin', UNIX_TIMESTAMP() - 86400*8, 0, 0, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 1, 1),

(22, NULL, 'deliberation', 'Options d''autonomie alimentaire : Délibération du Jury Citoyen', 'Le jury citoyen évalue différents modèles d''agriculture locale et fait des recommandations.', 30, 4, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*20, 0, 'australes-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0),

-- === Bundles ===
(10, NULL, 'proposal', 'Aménagement Vert de Tahiti 2025 : Développement complet', 'Proposition de développement urbain complet incluant espaces verts, zonage mixte et construction durable.', 14, 7, 'admin', UNIX_TIMESTAMP() - 86400*7, 0, UNIX_TIMESTAMP() + 86400*40, 0, 'service-amenagement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 1),

(24, NULL, 'meeting', 'Réunion de revue des projets numériques des Marquises', 'Revue trimestrielle des projets de transformation numérique en cours et allocation budgétaire.', 28, 20, 'admin', UNIX_TIMESTAMP() - 86400*10, 0, UNIX_TIMESTAMP() + 86400*3, 0, 'service-culture', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- === Référendums ===
(25, NULL, 'consultation', 'Pré-référendum : Examen de la Taxe de Séjour (Tahiti)', 'Session d''information et collecte de retours du public avant le vote référendaire sur la taxe de séjour.', 2, 17, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'tahiti-votants', 'open', 'always', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 0),

(26, NULL, 'news', 'Date du référendum sur l''autonomie des Marquises fixée à novembre 2025', 'Annonce officielle : Référendum consultatif sur un statut d''autonomie renforcée prévu pour le 20 novembre 2025.', 4, 17, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, 0, 0, 'polynesie-votants', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'published', 1, 1),

-- === Programme Agricole ===
(29, NULL, 'consultation', 'Programmes de soutien à l''agriculture biologique 2026-2030', 'Avis du public sur les programmes de subventions à l''agriculture biologique et le soutien à la transition.', 1, 4, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*25, 0, 'association-agriculteurs', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0),

-- === Inquiries supplémentaires (5000+) ===
(5001, NULL, 'proposal', 'Créer plus de pistes cyclables à Papeete', 'Extension du réseau cyclable entre la mairie et la gare maritime.', 14, 8, 'admin', UNIX_TIMESTAMP()-86400*200, 0, UNIX_TIMESTAMP()+86400*160, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*190, NULL, 'accepted', 'active', 1, 1),

(5002, NULL, 'debate', 'Limiter les navettes de croisière dans le lagon de Bora Bora', 'Faut-il interdire les gros bateaux dans les zones coralliennes sensibles ?', 22, 11, 'moderator', UNIX_TIMESTAMP()-86400*150, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*145, NULL, 'accepted', 'active', 1, 1),

(5003, NULL, 'project', 'Installation de panneaux solaires sur les écoles de Moorea', 'Projet de transition énergétique soutenu par la commune.', 19, 5, 'test', UNIX_TIMESTAMP()-86400*100, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*95, NULL, 'accepted', 'active', 1, 1),

(5004, NULL, 'petition', 'Protéger la passe de Tiputa à Rangiroa', 'Interdire les ancrages sur les coraux dans la passe célèbre.', 24, 3, 'test2', UNIX_TIMESTAMP()-86400*80, 0, UNIX_TIMESTAMP()+86400*60, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*75, NULL, 'accepted', 'active', 1, 1),

(5005, NULL, 'grievance', 'Bruit excessif des scooters des marines la nuit à Taiohae', 'Plainte concernant le trafic nocturne des deux-roues.', 28, 21, 'test3', UNIX_TIMESTAMP()-86400*60, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*55, NULL, 'accepted', 'active', 1, 1),

(5006, NULL, 'suggestion', 'Installer des parois anti-bruit près de l''aéroport', 'Suggestion liée à la plainte sur le bruit des avions.', 19, 21, 'admin', UNIX_TIMESTAMP()-86400*55, 0, UNIX_TIMESTAMP()+86400*100, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*50, 5005, 'accepted', 'active', 1, 1),

(5007, NULL, 'proposal', 'Planter 2''000 arbres à Tahiti', 'Plan de reforestation urbaine pour lutter contre les îlots de chaleur.', 14, 2, 'moderator', UNIX_TIMESTAMP()-86400*40, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*35, NULL, 'accepted', 'active', 1, 1),

(5008, NULL, 'official', 'Réponse officielle : Arbres à Tahiti', 'Le service des espaces verts soutient le projet.', 14, 2, 'official', UNIX_TIMESTAMP()-86400*38, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*37, 5007, 'accepted', 'active', 1, 1),

(5009, NULL, 'project', 'Créer un jardin partagé à Tubuai', 'Espace vert ouvert géré par les habitants pour l''autosuffisance.', 30, 4, 'test', UNIX_TIMESTAMP()-86400*30, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*25, NULL, 'accepted', 'active', 1, 1),

(5010, NULL, 'proposal', 'Installer une serre permaculture', 'Extension du projet 5009 pour produire des légumes tropicaux.', 30, 4, 'test2', UNIX_TIMESTAMP()-86400*28, 0, UNIX_TIMESTAMP()+86400*150, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*25, 5009, 'accepted', 'active', 1, 1),

(5011, NULL, 'grievance', 'Retards récurrents des avions inter-îles aux Marquises', 'Nombreuses plaintes depuis la saison des pluies.', 4, 8, 'test3', UNIX_TIMESTAMP()-86400*27, 0, UNIX_TIMESTAMP()+86400*90, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*26, NULL, 'accepted', 'active', 1, 1),

(5012, NULL, 'suggestion', 'Améliorer le suivi GPS des petits avions', 'Suggestion liée à la plainte 5011 sur la fiabilité des vols.', 4, 8, 'admin', UNIX_TIMESTAMP()-86400*26, 0, UNIX_TIMESTAMP()+86400*100, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*25, 5011, 'accepted', 'active', 1, 1),

(5013, NULL, 'petition', 'Interdire les jet-skis près des sites de plongée aux Tuamotu', 'Réduction du bruit et protection de la faune sous-marine.', 3, 3, 'moderator', UNIX_TIMESTAMP()-86400*24, 0, UNIX_TIMESTAMP()+86400*60, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*22, NULL, 'accepted', 'active', 1, 1),

(5014, NULL, 'debate', 'Faut-il enseigner le Reo Tahiti dans toutes les écoles de l''archipel ?', 'Débat public sur la place de la langue ancestrale à l''école.', 2, 24, 'test', UNIX_TIMESTAMP()-86400*20, 0, UNIX_TIMESTAMP()+86400*90, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*18, NULL, 'accepted', 'active', 1, 1),

(5015, NULL, 'proposal', 'Toitures végétalisées pour les bâtiments publics de Papeete', 'Projet de verdissement urbain pour la capitale.', 14, 2, 'test2', UNIX_TIMESTAMP()-86400*15, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*14, NULL, 'accepted', 'active', 1, 1),

(5016, NULL, 'official', 'Réponse officielle : Toitures végétalisées', 'Projet accepté en phase d''étude par la direction de l''environnement.', 14, 2, 'official', UNIX_TIMESTAMP()-86400*14, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'open', 'always', UNIX_TIMESTAMP()-86400*13, 5015, 'accepted', 'active', 1, 1);

-- ============================
-- Mise à jour: oc_agora_inq_group_misc avec IDs de localisation
-- ============================
-- Suppression des anciennes entrées location
DELETE FROM oc_agora_inq_group_misc WHERE `key` = 'location';

-- Insertion avec les vrais IDs de localisation (BIGINT)
INSERT INTO oc_agora_inq_group_misc (inquiry_group_id, `key`, value) VALUES
(1, 'location', '1'),   -- Polynésie Française
(2, 'location', '1'),   -- Polynésie Française
(3, 'location', '14'),  -- Papeete
(4, 'location', '7'),   -- Îles Sous-le-Vent
(5, 'location', '2'),   -- Îles de la Société
(6, 'location', '3'),   -- Îles Tuamotu
(7, 'location', '4'),   -- Îles Marquises
(8, 'location', '6'),   -- Îles du Vent
(9, 'location', '7'),   -- Îles Sous-le-Vent
(10, 'location', '8'),  -- Rangiroa District
(11, 'location', '14'), -- Papeete
(12, 'location', '19'), -- Moorea-Maiao
(13, 'location', '22'), -- Bora Bora
(14, 'location', '24'), -- Rangiroa
(15, 'location', '28'), -- Taiohae (Nuku Hiva)
(16, 'location', '14'), -- Papeete
(17, 'location', '1'),  -- Polynésie Française
(18, 'location', '1'),  -- Polynésie Française
(19, 'location', '1'),  -- Polynésie Française
(20, 'location', '14'), -- Papeete
(21, 'location', '22'), -- Bora Bora
(22, 'location', '2'),  -- Îles de la Société
(23, 'location', '3'),  -- Îles Tuamotu
(24, 'location', '4'),  -- Îles Marquises
(25, 'location', '14'), -- Papeete
(26, 'location', '3'),  -- Tuamotu
(27, 'location', '14'), -- Papeete
(28, 'location', '1'),  -- Polynésie Française
(29, 'location', '14'), -- Papeete
(30, 'location', '1'),  -- Polynésie Française
(31, 'location', '14'), -- Papeete
(32, 'location', '1'),  -- Polynésie Française
(33, 'location', '1'),  -- Polynésie Française
(35, 'location', '4'),  -- Îles Marquises
(37, 'location', '5'),  -- Îles Australes
(39, 'location', '10'), -- Nuku Hiva District
(42, 'location', '30'), -- Tubuai
(45, 'location', '1'),  -- Polynésie Française
(47, 'location', '5'),  -- Îles Australes
(49, 'location', '4'),  -- Îles Marquises
(50, 'location', '28'), -- Taiohae (Nuku Hiva)
(52, 'location', '4'),  -- Îles Marquises
(54, 'location', '1');  -- Polynésie Française

-- ============================
-- Table: oc_agora_inquiries
-- ============================
-- NOTE: Les champs 'location_id' et 'category_id' sont mis à 0 par défaut.
-- NOTE: Le champ 'family' a été supprimé de cette requête INSERT pour correspondre au schéma original fourni.

INSERT INTO oc_agora_inquiries (id, cover_id, type, title, description, location_id, category_id, owner, created, archived, expire, deleted, owned_group, access, show_results, last_interaction, parent_id, moderation_status, inquiry_status, allow_comment, allow_support) VALUES
-- Inquiries Programme Climat
(1, NULL, 'news', 'Le Gouvernement adopte de nouvelles mesures climatiques', 'La Polynésie actualise ses objectifs climatiques pour 2030 avec des objectifs de réduction de CO2 renforcés pour le transport maritime et le bâtiment.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*20, 0, 0, 0, 'gouvernement-polynesie', 'open', 'always', UNIX_TIMESTAMP() - 86400*5, NULL, 'accepted', 'published', 1, 1),
(11, NULL, 'consultation', 'Consultation publique : Développement des Énergies Renouvelables 2025-2030', 'Saisine du public sur les objectifs de développement du solaire et de l''énergie des mers, et les programmes de soutien pour les 5 prochaines années.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*15, 0, UNIX_TIMESTAMP() + 86400*45, 0, 'service-energie', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),
(12, NULL, 'meeting', 'Réunion trimestrielle du GT Énergie : Bilan d''exécution', 'Réunion pour discuter des progrès de la mise en œuvre de la transition énergétique et de l''allocation budgétaire.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*10, 0, UNIX_TIMESTAMP() + 86400*5, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 0),
(13, NULL, 'proposal', 'Proposition : Cadre d''affectation des revenus de la taxe carbone', 'Proposition détaillée sur l''affectation des revenus de la contribution climat entre fonds de transition, redistribution et programmes d''innovation.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*60, 0, 'gouvernement-polynesie', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 1),

-- Inquiries Programme Numérique
(14, NULL, 'consultation', 'Consultation publique sur l''Identité Numérique Polynésienne', 'Retours du public sur le système d''identité numérique proposé : fonctionnalités, garanties de confidentialité et calendrier.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*25, 0, UNIX_TIMESTAMP() + 86400*30, 0, 'service-numerique', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0),
(15, NULL, 'deliberation', 'Débat d''experts : Cadre de régulation de l''IA', 'Débat structuré d''experts sur la gouvernance de l''IA, la classification des risques et les approches réglementaires pour la Polynésie.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, UNIX_TIMESTAMP() + 86400*15, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),
(16, NULL, 'announcement', 'Lancement de la nouvelle plateforme API publique : api.pf', 'L''administration ouvre une nouvelle plateforme API pour les développeurs afin d''accéder aux données et services publics.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, 0, 0, 'service-numerique', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 1, 0),

-- Inquiries Programme Santé
(17, NULL, 'consultation', 'Stratégie Santé Mentale 2025-2030 : Saisine du public', 'Consultation publique sur le nouveau cadre des services de santé mentale, des programmes de prévention et des systèmes de soutien.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, UNIX_TIMESTAMP() + 86400*40, 0, 'sante-publique', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 0),
(18, NULL, 'meeting', 'Groupe de travail Prévention : Session stratégique mensuelle', 'Session mensuelle du groupe de travail pour développer des programmes de prévention des maladies non transmissibles.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*8, 0, UNIX_TIMESTAMP() + 86400*2, 0, 'experts-sante', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- Inquiries Assemblée Citoyenne de Tahiti
(2, NULL, 'meeting', 'Assemblée Citoyenne de Tahiti : Délibération sur le Budget 2026', 'Les résidents de Tahiti sont invités à discuter et à donner leur avis sur les priorités du budget municipal 2026.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*35, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*6, NULL, 'accepted', 'active', 1, 0),
(19, NULL, 'deliberation', 'Projets d''Aménagement Urbain : Débat Public', 'Les citoyens débattent des grands projets de construction, y compris les nouveaux logements, parcs et infrastructures.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*20, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 1),

-- Inquiries Communes locales
(3, NULL, 'consultation', 'Extension des pistes cyclables à Moorea : Planification du tracé', 'Consultation publique sur les nouvelles infrastructures cyclables, y compris les voies protégées et les stationnements.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*28, 0, UNIX_TIMESTAMP() + 86400*15, 0, 'moorea-residents', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),
(20, NULL, 'proposal', 'Proposition d''extension de la zone piétonne à Papeete', 'Proposition d''étendre la zone sans voiture au centre-ville pour inclure la rue des Remparts et améliorer les espaces publics.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, UNIX_TIMESTAMP() + 86400*25, 0, 'papeete-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 1),
(21, NULL, 'meeting', 'Réunion communale de Rangiroa : Réunion mensuelle', 'Réunion communale mensuelle ouverte à tous les résidents pour les affaires locales et les annonces.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*1, 0, 'rangiroa-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 1, 0),

-- Inquiries Groupes de travail
(4, NULL, 'proposal', 'Révision du programme de subventions pour panneaux solaires', 'Proposition pour des incitations résidentielles actualisées à l''énergie solaire, incluant le soutien au stockage par batterie.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*25, 0, UNIX_TIMESTAMP() + 86400*35, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 1),
(5, NULL, 'announcement', 'Nouvelles règles de mobilité pour trottinettes et vélos électriques', 'Réglementations actualisées pour les engins de micromobilité, y compris les zones de stationnement et les limites de vitesse.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, 0, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 0, 0),
(6, NULL, 'consultation', 'Stratégie Logement Abordable de Bora Bora 2025-2030', 'Avis du public sur les nouvelles politiques du logement, incluant le contrôle des loyers, les quotas de logements sociaux et les objectifs de construction.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*15, 0, UNIX_TIMESTAMP() + 86400*30, 0, 'borabora-residents', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),
(7, NULL, 'deliberation', 'Normes de confidentialité numérique : Débat technique', 'Débat technique sur les normes de protection des données pour les services numériques publics.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*14, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'experts-gouvernement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 0),
(8, NULL, 'meeting', 'Comité des Transports de Papeete : Réunion mensuelle sur la mobilité', 'Réunion mensuelle du comité de mobilité pour discuter des problèmes et projets de transport locaux.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*5, 0, UNIX_TIMESTAMP() + 86400*2, 0, 'conseil-papeete', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- Inquiries Jurys citoyens
(9, NULL, 'news', 'Pollution Lagonaire : Les conclusions du Jury Citoyen publiées', 'Conclusions et recommandations de la délibération du jury citoyen sur la régulation des rejets en lagons.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*8, 0, 0, 0, 'tahiti-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'published', 1, 1),
(22, NULL, 'deliberation', 'Options d''autonomie alimentaire : Délibération du Jury Citoyen', 'Le jury citoyen évalue différents modèles d''agriculture locale et fait des recommandations.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*20, 0, 'australes-residents', 'open', 'always', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0),

-- Inquiries Bundles
(10, NULL, 'proposal', 'Aménagement Vert de Tahiti 2025 : Développement complet', 'Proposition de développement urbain complet incluant espaces verts, zonage mixte et construction durable.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*7, 0, UNIX_TIMESTAMP() + 86400*40, 0, 'service-amenagement', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'active', 1, 1),
(24, NULL, 'meeting', 'Réunion de revue des projets numériques des Marquises', 'Revue trimestrielle des projets de transformation numérique en cours et allocation budgétaire.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*10, 0, UNIX_TIMESTAMP() + 86400*3, 0, 'service-culture', 'open', 'never', UNIX_TIMESTAMP() - 86400*1, NULL, 'accepted', 'active', 0, 0),

-- Inquiries Référendums
(25, NULL, 'consultation', 'Pré-référendum : Examen de la Taxe de Séjour (Tahiti)', 'Session d''information et collecte de retours du public avant le vote référendaire sur la taxe de séjour.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*22, 0, UNIX_TIMESTAMP() + 86400*10, 0, 'tahiti-votants', 'open', 'always', UNIX_TIMESTAMP() - 86400*4, NULL, 'accepted', 'active', 1, 0),
(26, NULL, 'news', 'Date du référendum sur l''autonomie des Marquises fixée à novembre 2025', 'Annonce officielle : Référendum consultatif sur un statut d''autonomie renforcée prévu pour le 20 novembre 2025.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*18, 0, 0, 0, 'polynesie-votants', 'open', 'always', UNIX_TIMESTAMP() - 86400*2, NULL, 'accepted', 'published', 1, 1),

-- Inquiries Programme Agricole
(29, NULL, 'consultation', 'Programmes de soutien à l''agriculture biologique 2026-2030', 'Avis du public sur les programmes de subventions à l''agriculture biologique et le soutien à la transition.', 0, 0, 'admin', UNIX_TIMESTAMP() - 86400*12, 0, UNIX_TIMESTAMP() + 86400*25, 0, 'association-agriculteurs', 'open', 'after_vote', UNIX_TIMESTAMP() - 86400*3, NULL, 'accepted', 'active', 1, 0);

-- ============================
-- Table: oc_agora_inq_misc
-- ============================

INSERT INTO oc_agora_inq_misc (inquiry_id, `key`, value) VALUES
-- Entrées existantes (adaptées)
(11, 'consultation_start', '2025-01-25'),
(11, 'consultation_end', '2025-03-25'),
(11, 'target_participants', '3000'),

(14, 'consultation_start', '2025-01-30'),
(14, 'consultation_end', '2025-03-15'),
(14, 'digital_id_version', '1.0'),

(25, 'referendum_date', '2025-10-12'),
(25, 'referendum_number', 'PF-TAH-2025-045'),
(25, 'campaign_website', 'https://polynesie.pf/taxe-sejour-referendum'),

(26, 'referendum_date', '2025-11-20'),
(26, 'referendum_number', 'PF-MQ-2025-128'),
(26, 'required_signatures', '2000'),

(2, 'meeting_date', '2025-03-15'),
(2, 'meeting_time', '14:00'),
(2, 'meeting_location', 'Hôtel de Ville de Papeete'),
(2, 'registration_required', 'oui'),

(12, 'meeting_date', '2025-03-20'),
(12, 'meeting_time', '10:00'),
(12, 'meeting_location', 'Siège du Service de l''Énergie, Papeete'),
(12, 'agenda_url', 'https://energie.pf/agenda-t1-2025'),

(1, 'contact_email', 'climat@polynesie.pf'),
(1, 'contact_phone', '+689 40 47 00 00'),
(1, 'official_gazette', 'https://journal-officiel.pf/jo/2025/123'),

(3, 'contact_email', 'mobilite@moorea.pf'),
(3, 'contact_phone', '+689 40 56 11 11'),
(3, 'project_manager', 'Maeva Tehei'),

(3, 'participant_count', '847'),
(14, 'participant_count', '1568'),
(17, 'participant_count', '592'),
(11, 'participant_count', '1156'),

(1, 'geo_scope', 'national'),
(3, 'geo_scope', 'commune'),
(20, 'geo_scope', 'commune'),
(25, 'geo_scope', 'archipel'),

(13, 'legal_basis', 'Loi Climat Art. 12'),
(25, 'legal_basis', 'Code du Tourisme Art. 45'),
(29, 'legal_basis', 'Loi d''Orientation Agricole Art. 22'),

(9, 'report_url', 'https://environnement.pf/rapport-pollution-lagon-2025.pdf'),
(9, 'executive_summary', 'https://environnement.pf/resume-pollution-lagon.pdf'),
(15, 'background_paper', 'https://numerique.pf/livre-blanc-ia'),
(10, 'full_proposal', 'https://amenagement.pf/plan-tahiti-2025-complet.pdf'),

-- ===========================================
-- Nouvelles entrées layout + render_mode
-- ===========================================

-- NEWS (footer + cards)
(1,  'layout_zone', 'footer'), (1,  'render_mode', 'cards'),
(9,  'layout_zone', 'footer'), (9,  'render_mode', 'cards'),
(26, 'layout_zone', 'footer'), (26, 'render_mode', 'cards'),

-- MEETING (main + cards)
(12, 'layout_zone', 'main'), (12, 'render_mode', 'cards'),
(2,  'layout_zone', 'main'), (2,  'render_mode', 'cards'),
(18, 'layout_zone', 'main'), (18, 'render_mode', 'cards'),
(21, 'layout_zone', 'main'), (21, 'render_mode', 'cards'),
(8,  'layout_zone', 'main'), (8,  'render_mode', 'cards'),
(24, 'layout_zone', 'main'), (24, 'render_mode', 'cards'),

-- ALL OTHER TYPES (sidebar + summary)
(11, 'layout_zone', 'sidebar'), (11, 'render_mode', 'summary'),
(13, 'layout_zone', 'sidebar'), (13, 'render_mode', 'summary'),
(14, 'layout_zone', 'sidebar'), (14, 'render_mode', 'summary'),
(15, 'layout_zone', 'sidebar'), (15, 'render_mode', 'summary'),
(16, 'layout_zone', 'sidebar'), (16, 'render_mode', 'summary'),
(17, 'layout_zone', 'sidebar'), (17, 'render_mode', 'summary'),
(19, 'layout_zone', 'sidebar'), (19, 'render_mode', 'summary'),
(20, 'layout_zone', 'sidebar'), (20, 'render_mode', 'summary'),
(3,  'layout_zone', 'sidebar'), (3,  'render_mode', 'summary'),
(4,  'layout_zone', 'sidebar'), (4,  'render_mode', 'summary'),
(5,  'layout_zone', 'sidebar'), (5,  'render_mode', 'summary'),
(6,  'layout_zone', 'sidebar'), (6,  'render_mode', 'summary'),
(7,  'layout_zone', 'sidebar'), (7,  'render_mode', 'summary'),
(10, 'layout_zone', 'sidebar'), (10, 'render_mode', 'summary'),
(22, 'layout_zone', 'sidebar'), (22, 'render_mode', 'summary'),
(25, 'layout_zone', 'sidebar'), (25, 'render_mode', 'summary'),
(29, 'layout_zone', 'sidebar'), (29, 'render_mode', 'summary');

-- ============================
-- Table: oc_agora_groups_inquiries
-- ============================

INSERT INTO oc_agora_groups_inquiries (inquiry_id, group_id) VALUES
-- Assignations principales
(1,1),
(2,3),
(3,12), -- Moorea
(4,16),
(5,17),
(6,20), -- Bora Bora
(7,18),
(8,19),
(9,28),
(10,24),

-- Inquiries programme climat
(11,1),
(11,16),
(12,16),
(13,1),
(13,16),

-- Inquiries programme numérique
(14,2),
(14,18),
(15,2),
(15,18),
(16,2),

-- Inquiries programme santé
(17,31),
(17,42),
(18,42),

-- Inquiries assemblée Tahiti
(19,3),
(19,5),

-- Inquiries locales
(20,11), -- Papeete
(20,19),
(21,14), -- Rangiroa

-- Inquiries jurys citoyens
(22,50),

-- Inquiries bundles
(24,47),
(24,7),

-- Inquiries référendums
(25,30),
(25,5),
(26,1),

-- Inquiries programme agricole
(29,32),
(29,43);


-- ============================
-- Données Agora supplémentaires (Polynésie Edition)
-- ============================

-- Inquiries
INSERT INTO oc_agora_inquiries
(id, cover_id, type, title, description, location_id, category_id, owner,
 created, archived, expire, deleted, owned_group, access, show_results,
 last_interaction, parent_id, moderation_status, inquiry_status,
 allow_comment, allow_support)
VALUES
-- 5001 — Papeete / Mobilité douce
(5001, NULL, 'proposal',
 'Créer plus de pistes cyclables à Papeete',
 'Extension du réseau cyclable entre la mairie et la gare maritime.',
 101, 6, 'admin',
 UNIX_TIMESTAMP()-86400*200, 0, UNIX_TIMESTAMP()+86400*160, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*190, NULL,
 'accepted', 'active', 1, 1),

-- 5002 — Bora Bora / Débat
(5002, NULL, 'debate',
 'Limiter les navettes de croisière dans le lagon de Bora Bora',
 'Faut-il interdire les gros bateaux dans les zones coralliennes sensibles ?',
 102, 11, 'moderator',
 UNIX_TIMESTAMP()-86400*150, 0, UNIX_TIMESTAMP()+86400*120, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*145, NULL,
 'accepted', 'active', 1, 1),

-- 5003 — Moorea / Projet solaire
(5003, NULL, 'project',
 'Installation de panneaux solaires sur les écoles de Moorea',
 'Projet de transition énergétique soutenu par la commune.',
 103, 5, 'test',
 UNIX_TIMESTAMP()-86400*100, 0, UNIX_TIMESTAMP()+86400*200, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*95, NULL,
 'accepted', 'active', 1, 1),

-- 5004 — Rangiroa / Pétition
(5004, NULL, 'petition',
 'Protéger la passe de Tiputa à Rangiroa',
 'Interdire les ancrages sur les coraux dans la passe célèbre.',
 104, 2, 'test2',
 UNIX_TIMESTAMP()-86400*80, 0, UNIX_TIMESTAMP()+86400*60, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*75, NULL,
 'accepted', 'active', 1, 1),

-- 5005 — Nuku Hiva / Grief
(5005, NULL, 'grievance',
 'Bruit excessif des scooters des marines la nuit à Taiohae',
 'Plainte concernant le trafic nocturne des deux-roues.',
 105, 23, 'test3',
 UNIX_TIMESTAMP()-86400*60, 0, UNIX_TIMESTAMP()+86400*120, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*55, NULL,
 'accepted', 'active', 1, 1),

-- 5006 — Moorea / Suggestion liée
(5006, NULL, 'suggestion',
 'Installer des parois anti-bruit près de l''aéroport',
 'Suggestion liée à la plainte sur le bruit des avions.',
 103, 6, 'admin',
 UNIX_TIMESTAMP()-86400*55, 0, UNIX_TIMESTAMP()+86400*100, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*50, 5005,
 'accepted', 'active', 1, 1),

-- 5007 — Tahiti / Communauté
(5007, NULL, 'proposal',
 'Planter 2’000 arbres à Tahiti',
 'Plan de reforestation urbaine pour lutter contre les îlots de chaleur.',
 101, 9, 'moderator',
 UNIX_TIMESTAMP()-86400*40, 0, UNIX_TIMESTAMP()+86400*200, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*35, NULL,
 'accepted', 'active', 1, 1),

-- 5008 — Réponse officielle (Tahiti)
(5008, NULL, 'official',
 'Réponse officielle : Arbres à Tahiti',
 'Le service des espaces verts soutient le projet.',
 101, 9, 'official',
 UNIX_TIMESTAMP()-86400*38, 0, UNIX_TIMESTAMP()+86400*120, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*37, 5007,
 'accepted', 'active', 1, 1),

-- 5009 — Tubuai / Jardin communautaire
(5009, NULL, 'project',
 'Créer un jardin partagé à Tubuai',
 'Espace vert ouvert géré par les habitants pour l''autosuffisance.',
 106, 17, 'test',
 UNIX_TIMESTAMP()-86400*30, 0, UNIX_TIMESTAMP()+86400*200, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*25, NULL,
 'accepted', 'active', 1, 1),

-- 5010 — Suggestion liée (Tubuai)
(5010, NULL, 'proposal',
 'Installer une serre permaculture',
 'Extension du projet 5009 pour produire des légumes tropicaux.',
 106, 4, 'test2',
 UNIX_TIMESTAMP()-86400*28, 0, UNIX_TIMESTAMP()+86400*150, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*25, 5009,
 'accepted', 'active', 1, 1),

-- 5011 — Marquises / Transport
(5011, NULL, 'grievance',
 'Retards récurrents des avions inter-îles aux Marquises',
 'Nombreuses plaintes depuis la saison des pluies.',
 105, 9, 'test3',
 UNIX_TIMESTAMP()-86400*27, 0, UNIX_TIMESTAMP()+86400*90, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*26, NULL,
 'accepted', 'active', 1, 1),

-- 5012 — Suggestion avions GPS
(5012, NULL, 'suggestion',
 'Améliorer le suivi GPS des petits avions',
 'Suggestion liée à la plainte 5011 sur la fiabilité des vols.',
 105, 9, 'admin',
 UNIX_TIMESTAMP()-86400*26, 0, UNIX_TIMESTAMP()+86400*100, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*25, 5011,
 'accepted', 'active', 1, 1),

-- 5013 — Tuamotu / Jet-skis
(5013, NULL, 'petition',
 'Interdire les jet-skis près des sites de plongée aux Tuamotu',
 'Réduction du bruit et protection de la faune sous-marine.',
 102, 3, 'moderator',
 UNIX_TIMESTAMP()-86400*24, 0, UNIX_TIMESTAMP()+86400*60, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*22, NULL,
 'accepted', 'active', 1, 1),

-- 5014 — Îles de la Société / École
(5014, NULL, 'debate',
 'Faut-il enseigner le Reo Tahiti dans toutes les écoles de l''archipel ?',
 'Débat public sur la place de la langue ancestrale à l''école.',
 103, 26, 'test',
 UNIX_TIMESTAMP()-86400*20, 0, UNIX_TIMESTAMP()+86400*90, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*18, NULL,
 'accepted', 'active', 1, 1),

-- 5015 — Tahiti / Écologie
(5015, NULL, 'proposal',
 'Toitures végétalisées pour les bâtiments publics de Papeete',
 'Projet de verdissement urbain pour la capitale.',
 101, 2, 'test2',
 UNIX_TIMESTAMP()-86400*15, 0, UNIX_TIMESTAMP()+86400*200, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*14, NULL,
 'accepted', 'active', 1, 1),

-- 5016 — Réponse officielle toits verts
(5016, NULL, 'official',
 'Réponse officielle : Toitures végétalisées',
 'Projet accepté en phase d’étude par la direction de l''environnement.',
 101, 2, 'official',
 UNIX_TIMESTAMP()-86400*14, 0, UNIX_TIMESTAMP()+86400*120, 0, '',
 'open', 'always',
 UNIX_TIMESTAMP()-86400*13, 5015,
 'accepted', 'active', 1, 1);


-- ============================
-- INSERT pour oc_agora_comments (exemple)
-- ============================

INSERT INTO oc_agora_comments (inquiry_id, user_id, comment, timestamp, deleted, confidential, recipient)
VALUES
(5001, 'test2', 'Mauruuru ! Très bonne idée pour réduire les embouteillages.', UNIX_TIMESTAMP()-86400*190, 0, 0, NULL),
(5002, 'admin', 'Cela protégera le corail mais affectera le tourisme.', UNIX_TIMESTAMP()-86400*145, 0, 0, NULL),
(5003, 'moderator', 'Excellente initiative pour notre autonomie énergétique !', UNIX_TIMESTAMP()-86400*95, 0, 0, NULL),
(5005, 'test', 'Le bruit des scooters est insupportable après 22h.', UNIX_TIMESTAMP()-86400*55, 0, 0, NULL),
(5007, 'test2', 'Plus d’arbres = plus de fraîcheur pour nos enfants.', UNIX_TIMESTAMP()-86400*35, 0, 0, NULL),
(5009, 'test3', 'Parfait pour renforcer notre autosuffisance alimentaire.', UNIX_TIMESTAMP()-86400*25, 0, 0, NULL),
(5013, 'test', 'Les moteurs effraient les raies manta à la passe.', UNIX_TIMESTAMP()-86400*22, 0, 0, NULL),
(5015, 'admin', 'Cela réduira la température dans le centre-ville.', UNIX_TIMESTAMP()-86400*14, 0, 0, NULL);

-- ============================
-- INSERT pour oc_agora_supports (exemple)
-- ============================

INSERT INTO oc_agora_supports (inquiry_id, option_id, user_id, value, created, support_hash)
VALUES
-- Vote simple (value = 1)
(5001, 0, 'test',      1, UNIX_TIMESTAMP()-86400*190, MD5(CONCAT('5001','test'))),
(5001, 0, 'test2',     1, UNIX_TIMESTAMP()-86400*189, MD5(CONCAT('5001','test2'))),
(5001, 0, 'moderator', 1, UNIX_TIMESTAMP()-86400*188, MD5(CONCAT('5001','moderator'))),

-- Vote à deux choix (+1 / -1)
(5002, 1, 'test3',  +1, UNIX_TIMESTAMP()-86400*145, MD5(CONCAT('5002','test3'))),
(5002, 2, 'admin',  -1, UNIX_TIMESTAMP()-86400*144, MD5(CONCAT('5002','admin'))),

-- Vote simple
(5003, 0, 'admin', 1, UNIX_TIMESTAMP()-86400*95, MD5(CONCAT('5003','admin'))),
(5003, 0, 'test2', 1, UNIX_TIMESTAMP()-86400*94, MD5(CONCAT('5003','test2'))),

-- Vote à 3 choix : -1 / 0 / +1
(5004, 1, 'moderator', +1, UNIX_TIMESTAMP()-86400*75, MD5(CONCAT('5004','moderator'))),
(5004, 2, 'test',       0, UNIX_TIMESTAMP()-86400*74, MD5(CONCAT('5004','test'))),
(5004, 3, 'test3',     -1, UNIX_TIMESTAMP()-86400*73, MD5(CONCAT('5004','test3'))),

-- Simple
(5007, 0, 'admin', 1, UNIX_TIMESTAMP()-86400*35, MD5(CONCAT('5007','admin'))),

-- Simple
(5009, 0, 'test2', 1, UNIX_TIMESTAMP()-86400*25, MD5(CONCAT('5009','test2'))),

-- 2 choix
(5013, 1, 'test3', +1, UNIX_TIMESTAMP()-86400*22, MD5(CONCAT('5013','test3'))),
(5013, 2, 'test',  -1, UNIX_TIMESTAMP()-86400*21, MD5(CONCAT('5013','test'))),

-- Simple
(5015, 0, 'admin', 1, UNIX_TIMESTAMP()-86400*14, MD5(CONCAT('5015','admin')));
