-- ---------------------------------------------------------
-- ROOT GROUPS (Assemblies, Districts, Committees)
-- ---------------------------------------------------------

INSERT INTO oc_agora_groups
(id, title, type, description, owner, parent_id, group_status, metadata)
VALUES
(1000, 'City Assembly', 'assembly',
 'Main participatory assembly for the city.', 'admin', NULL, 'active',
 '{"allowed_inquiry_types":["consultation","proposal","poll"]}' ),

(2000, 'District North', 'district',
 'Administrative district covering the northern area.', 'admin', NULL, 'active',
 '{"allowed_inquiry_types":["consultation","proposal","vote"]}' ),

(3000, 'Economic Development Committee', 'committee',
 'Committee working on economic policies and development.', 'admin', NULL, 'active',
 '{"allowed_inquiry_types":["proposal","petitio","poll","vote"]}' );


-- ---------------------------------------------------------
-- SUB-GROUPS (Subdistricts, Sub-assemblies)
-- ---------------------------------------------------------

INSERT INTO oc_agora_groups
(id, title, type, description, owner, parent_id, group_status, metadata)
VALUES
(2100, 'District North – Subdistrict A', 'subdistrict',
 'Local area A inside North District.', 'admin', 2000, 'active',
 '{"allowed_inquiry_types":["consultation","vote"]}' ),

(2200, 'District North – Subdistrict B', 'subdistrict',
 'Local area B inside North District.', 'admin', 2000, 'active',
 '{"allowed_inquiry_types":["proposal","consultation"]}' ),

(1100, 'Youth Assembly', 'subassembly',
 'Assembly dedicated to youth initiatives.', 'admin', 1000, 'active',
 '{"allowed_inquiry_types":["proposal","poll"]}' );


-- ---------------------------------------------------------
-- INQUIRIES FOR CITY ASSEMBLY
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(5001, 'consultation',
 'City Priorities for 2026',
 'Residents rank the priorities for the next city strategy.',
 'admin', 1000, 'active'),

(5002, 'proposal',
 'Expand the pedestrian area downtown',
 'Proposal to increase walkable public spaces.',
 'admin', 1000, 'active'),

(5003, 'poll',
 'Should the city adopt free public transport on weekends?',
 'Quick yes/no poll.',
 'admin', 1000, 'active');


-- ---------------------------------------------------------
-- INQUIRIES FOR YOUTH ASSEMBLY
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(5100, 'proposal',
 'Create a Youth Innovation Fund',
 'Proposal for a fund to support youth entrepreneurship.',
 'admin', 1100, 'active'),

(5101, 'poll',
 'Which topic should be discussed at the next Youth Assembly?',
 'Choose the main debate theme.',
 'admin', 1100, 'active');


-- ---------------------------------------------------------
-- INQUIRIES FOR DISTRICT NORTH
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(6001, 'consultation',
 'Traffic Management Priorities',
 'Consultation about traffic flow improvements in District North.',
 'admin', 2000, 'active'),

(6002, 'proposal',
 'Install new bike lanes on Mountain Road',
 'Proposal to add protected bike lanes.',
 'admin', 2000, 'active'),

(6003, 'vote',
 'Select the final redesign plan for North Park',
 'Residents choose between 3 proposed park designs.',
 'admin', 2000, 'active');


-- ---------------------------------------------------------
-- INQUIRIES FOR SUBDISTRICT A
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(6100, 'consultation',
 'Local Waste Collection Feedback',
 'Consultation on improving waste collection schedules.',
 'admin', 2100, 'active'),

(6101, 'vote',
 'Choose location for new playground',
 'Residents select between two proposed locations.',
 'admin', 2100, 'active');


-- ---------------------------------------------------------
-- INQUIRIES FOR SUBDISTRICT B
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(6200, 'proposal',
 'Community Garden Creation',
 'Proposal to build a new shared community garden.',
 'admin', 2200, 'active'),

(6201, 'consultation',
 'Lighting Improvements in Residential Streets',
 'Residents give feedback on street lighting.',
 'admin', 2200, 'active');


-- ---------------------------------------------------------
-- INQUIRIES FOR ECONOMIC DEVELOPMENT COMMITTEE
-- ---------------------------------------------------------

INSERT INTO oc_agora_inquiries
(id, type, title, description, owner, parent_id, inquiry_status)
VALUES
(7000, 'proposal',
 'Tax Incentives for Small Businesses',
 'Draft proposal about reducing taxes for startups.',
 'admin', 3000, 'active'),

(7001, 'poll',
 'Which economic sector should receive priority support?',
 'Vote among tourism, tech, agriculture and services.',
 'admin', 3000, 'active'),

(7002, 'vote',
 'Final approval of the 2026 Economic Action Plan',
 'Committee members validate the final plan.',
 'admin', 3000, 'active');


