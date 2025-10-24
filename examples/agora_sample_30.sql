-- ============================
-- Agora Sample Data (ID offset = 5000)
-- ============================

-- Inquiries
INSERT INTO oc_agora_inquiries
(id, cover_id, type, title, description, location_id, category_id, owner, created, archived, expire, deleted, owned_group, access, show_results, last_interaction, parent_id, moderation_status, inquiry_status, allow_comment, allow_support)
VALUES
(5001, NULL, 'proposal', 'Ban Single-Use Plastics in Moorea', 'A proposal to reduce pollution by banning plastic bags and straws.', 6, 6, 'admin', UNIX_TIMESTAMP()-86400*200, 0, UNIX_TIMESTAMP()+86400*160, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*190, 0, 'collecting_support', 'active', 1, 1),
(5002, NULL, 'debate', 'Tourism Limits in Bora Bora', 'Should Bora Bora cap the number of tourists each year?', 9, 11, 'moderator', UNIX_TIMESTAMP()-86400*150, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*145, 0, 'discussion_open', 'active', 1, 1),
(5003, NULL, 'project', 'Community Solar Panels in Tahiti', 'Install solar panels for community centers across Tahiti.', 5, 5, 'test', UNIX_TIMESTAMP()-86400*100, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*95, 0, 'under_process', 'active', 1, 1),
(5004, NULL, 'petition', 'Protect Coral Reefs of Rangiroa', 'A petition to designate new marine protected areas in Rangiroa.', 15, 2, 'test2', UNIX_TIMESTAMP()-86400*80, 0, UNIX_TIMESTAMP()+86400*60, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*75, 0, 'collecting_support', 'active', 1, 1),
(5005, NULL, 'grievance', 'Noise Pollution from Airport in Tahiti', 'Residents complain about excessive airplane noise near Faa’a airport.', 5, 23, 'test3', UNIX_TIMESTAMP()-86400*60, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*55, 0, 'under_investigation', 'active', 1, 1),
(5006, NULL, 'suggestion', 'Add Recycling Bins Near Schools', 'A suggestion linked to grievance 5005 about environmental impact.', 5, 6, 'admin', UNIX_TIMESTAMP()-86400*55, 0, UNIX_TIMESTAMP()+86400*100, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*50, 5005, 'under_process', 'active', 1, 1),
(5007, NULL, 'proposal', 'Bike Lanes in Papeete', 'Proposal to create safe bicycle lanes in Papeete downtown.', 5, 9, 'moderator', UNIX_TIMESTAMP()-86400*40, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*35, 0, 'collecting_support', 'active', 1, 1),
(5008, NULL, 'official', 'Official Response: Bike Lanes', 'The municipality supports this proposal and will launch a feasibility study.', 5, 9, 'official', UNIX_TIMESTAMP()-86400*38, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*37, 5007, 'integrated', 'active', 1, 1),
(5009, NULL, 'project', 'Community Garden in Huahine', 'Create a shared garden to promote food security and social links.', 12, 17, 'test', UNIX_TIMESTAMP()-86400*30, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*25, 0, 'feasibility_review', 'active', 1, 1),
(5010, NULL, 'proposal', 'Vertical Farming in Raiatea', 'Introduce hydroponic vertical farms to save space and water.', 10, 4, 'test2', UNIX_TIMESTAMP()-86400*28, 0, UNIX_TIMESTAMP()+86400*150, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*25, 5009, 'under_process', 'active', 1, 1),
(5011, NULL, 'grievance', 'Public Transport Delays', 'Citizens report frequent delays in Tahiti buses.', 5, 9, 'test3', UNIX_TIMESTAMP()-86400*27, 0, UNIX_TIMESTAMP()+86400*90, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*26, 5007, 'under_investigation', 'active', 1, 1),
(5012, NULL, 'suggestion', 'Add GPS Tracking to Buses', 'Linked to grievance 5011, adding GPS tracking could reduce uncertainty.', 5, 9, 'admin', UNIX_TIMESTAMP()-86400*26, 0, UNIX_TIMESTAMP()+86400*100, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*25, 5011, 'under_process', 'active', 1, 1),
(5013, NULL, 'petition', 'Ban Jet Skis in Bora Bora Lagoon', 'Protect biodiversity by banning noisy jet skis.', 9, 3, 'moderator', UNIX_TIMESTAMP()-86400*24, 0, UNIX_TIMESTAMP()+86400*60, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*22, 0, 'collecting_support', 'active', 1, 1),
(5014, NULL, 'debate', 'Extend School Hours in Tahiti', 'Should schools end later to help working parents?', 5, 26, 'test', UNIX_TIMESTAMP()-86400*20, 0, UNIX_TIMESTAMP()+86400*90, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*18, 0, 'discussion_open', 'active', 1, 1),
(5015, NULL, 'proposal', 'Tree Planting Campaign in Moorea', 'Organize a campaign to plant 10,000 trees in Moorea.', 6, 2, 'test2', UNIX_TIMESTAMP()-86400*15, 0, UNIX_TIMESTAMP()+86400*200, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*14, 0, 'collecting_support', 'active', 1, 1),
(5016, NULL, 'official', 'Official Response: Tree Planting', 'The Environment Office commits to supporting this campaign.', 6, 2, 'official', UNIX_TIMESTAMP()-86400*14, 0, UNIX_TIMESTAMP()+86400*120, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*13, 5015, 'integrated', 'active', 1, 1),
(5017, NULL, 'grievance', 'Flooding in Paopao', 'Heavy rains cause recurrent floods in Paopao district.', 6, 10, 'test3', UNIX_TIMESTAMP()-86400*12, 0, UNIX_TIMESTAMP()+86400*100, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*11, 5003, 'under_process', 'active', 1, 1),
(5018, NULL, 'suggestion', 'Build Better Drainage', 'Suggestion linked to grievance 5017: improve drainage system.', 6, 10, 'moderator', UNIX_TIMESTAMP()-86400*11, 0, UNIX_TIMESTAMP()+86400*90, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*10, 5017, 'under_process', 'active', 1, 1),
(5019, NULL, 'proposal', 'Green Roofs in Papeete', 'Encourage rooftop gardens for energy savings and urban cooling.', 5, 7, 'admin', UNIX_TIMESTAMP()-86400*8, 0, UNIX_TIMESTAMP()+86400*150, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*7, 0, 'collecting_support', 'active', 1, 1),
(5020, NULL, 'debate', 'Should Schools Teach Tahitian Language?', 'Discussion on integrating Tahitian language into curriculum.', 5, 28, 'test2', UNIX_TIMESTAMP()-86400*5, 0, UNIX_TIMESTAMP()+86400*60, 0, '', 'public', 'always', UNIX_TIMESTAMP()-86400*4, 0, 'discussion_open', 'active', 1, 1);

-- Comments
INSERT INTO oc_agora_comments (inquiry_id, user_id, comment, timestamp, deleted, confidential, recipient) VALUES
(5001, 'test2', 'This is much needed!', UNIX_TIMESTAMP()-86400*190, 0, 0, NULL),
(5001, 'test3', 'How will businesses adapt?', UNIX_TIMESTAMP()-86400*185, 0, 0, NULL),
(5002, 'admin', 'Tourism brings money but harms the environment.', UNIX_TIMESTAMP()-86400*145, 0, 0, NULL),
(5003, 'moderator', 'Good renewable initiative!', UNIX_TIMESTAMP()-86400*95, 0, 0, NULL),
(5005, 'test', 'I agree with this grievance.', UNIX_TIMESTAMP()-86400*55, 0, 0, NULL),
(5007, 'test2', 'This will reduce car traffic.', UNIX_TIMESTAMP()-86400*35, 0, 0, NULL),
(5009, 'test3', 'Community gardens build resilience.', UNIX_TIMESTAMP()-86400*25, 0, 0, NULL),
(5013, 'test', 'Biodiversity first!', UNIX_TIMESTAMP()-86400*22, 0, 0, NULL),
(5015, 'admin', 'Planting trees will also help soil erosion.', UNIX_TIMESTAMP()-86400*14, 0, 0, NULL);

-- Supports
INSERT INTO oc_agora_supports (inquiry_id, option_id, user_id, created, support_hash)
VALUES
(5001, 0, 'test', UNIX_TIMESTAMP()-86400*190, MD5(CONCAT('5001','test'))),
(5001, 0, 'test2', UNIX_TIMESTAMP()-86400*189, MD5(CONCAT('5001','test2'))),
(5001, 0, 'moderator', UNIX_TIMESTAMP()-86400*188, MD5(CONCAT('5001','moderator'))),
(5002, 0, 'test3', UNIX_TIMESTAMP()-86400*145, MD5(CONCAT('5002','test3'))),
(5003, 0, 'admin', UNIX_TIMESTAMP()-86400*95, MD5(CONCAT('5003','admin'))),
(5003, 0, 'test2', UNIX_TIMESTAMP()-86400*94, MD5(CONCAT('5003','test2'))),
(5004, 0, 'moderator', UNIX_TIMESTAMP()-86400*75, MD5(CONCAT('5004','moderator'))),
(5005, 0, 'test2', UNIX_TIMESTAMP()-86400*55, MD5(CONCAT('5005','test2'))),
(5007, 0, 'test3', UNIX_TIMESTAMP()-86400*35, MD5(CONCAT('5007','test3'))),
(5007, 0, 'admin', UNIX_TIMESTAMP()-86400*34, MD5(CONCAT('5007','admin'))),
(5009, 0, 'test2', UNIX_TIMESTAMP()-86400*25, MD5(CONCAT('5009','test2'))),
(5010, 0, 'moderator', UNIX_TIMESTAMP()-86400*24, MD5(CONCAT('5010','moderator'))),
(5013, 0, 'test3', UNIX_TIMESTAMP()-86400*22, MD5(CONCAT('5013','test3'))),
(5015, 0, 'admin', UNIX_TIMESTAMP()-86400*14, MD5(CONCAT('5015','admin'))),
(5015, 0, 'test', UNIX_TIMESTAMP()-86400*13, MD5(CONCAT('5015','test'))),
(5019, 0, 'test3', UNIX_TIMESTAMP()-86400*7, MD5(CONCAT('5019','test3'))),
(5020, 0, 'moderator', UNIX_TIMESTAMP()-86400*4, MD5(CONCAT('5020','moderator')));

