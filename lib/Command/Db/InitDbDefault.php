<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Agora\Command\Db;

use OCP\IGroupManager;
use OCA\Agora\Command\Command;
use OCP\IDBConnection;
use OCA\Agora\Db\Category;
use OCA\Agora\Db\Location;
use OCA\Agora\Db\InquiryStatus;
use OCA\Agora\Db\InquiryType;
use OCA\Agora\Db\InquiryFamily;
use OCP\Migration\IOutput;

class InitDbDefault extends Command
{
    protected string $name=parent::NAME_PREFIX . 'db:init-default';
    protected string $description = 'Initialize default Agora categories and locations';
    private IGroupManager $groupManager;

    private array $inquiryTypeFamilies = [
    [
    'family_type' => 'deliberative',
    'label' => 'Deliberative',
    'description' => 'Citizen-driven processes such as debates, proposals, petitions, projects, and deliberations.',
    'icon' => 'AccountGroup',
    'sort_order' => 1,
    'created' => '',
    ],
    [
    'family_type' => 'legislative',
    'label' => 'Legislative',
    'description' => 'Law proposals, amendments, constitutional workshops, and official legislative responses.',
    'icon' => 'Gavel',
    'sort_order' => 2,
    'created' => '',
    ],
    [
    'family_type' => 'administrative',
    'label' => 'Administrative',
    'description' => 'Administrative requests and grievances addressed to institutions.',
    'icon' => 'OfficeBuilding',
    'sort_order' => 3,
    'created' => '',
    ],
    [
    'family_type' => 'service',
    'label' => 'Service',
    'description' => 'Social and citizen service requests such as housing, childcare, or scholarships.',
    'icon' => 'Offer',
    'sort_order' => 4,
    'created' => '',
    ],
    [
    'family_type' => 'collective',
    'label' => 'Collective',
    'description' => 'Assemblies and grouped consultations on themes or topics, could be used for submit it to polls',
    'icon' => 'AccountMultiple',
    'sort_order' => 5,
    'created' => '',
    ],
    [
    'family_type' => 'social',
    'label' => 'Social',
    'description' => 'Social request by citizan to help them in their daily life',
    'icon' => 'Offer',
    'sort_order' => 6,
    ],
    [
    'family_type' => 'official',
    'label' => 'Official',
    'description' => 'Responses and contributions from official entities such as city hall, experts, or commissions.',
    'icon' => 'Seal',
    'sort_order' => 7,
    'created' => '',
    ],
    ];

    private array $inquiryTypes = [
    // --- Debate arguments ---
    [
        'inquiry_type' => 'debate_for',
        'family' => 'debate',
        'icon' => 'ThumbUp',
        'label' => 'Debate For',
        'description' => 'Argument in favor within a debate.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['suggestion','proposal','official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'debate_against',
        'family' => 'debate',
        'icon' => 'ThumbDown',
        'label' => 'Debate Against',
        'description' => 'Argument against within a debate.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['suggestion','proposal','official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'debate_choice',
        'family' => 'debate',
        'icon' => 'CheckboxMultiple',
        'label' => 'Debate Choice',
        'description' => 'Option or choice within a debate.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => null,
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'debate_accepted',
        'family' => 'debate',
        'icon' => 'CheckCircle',
        'label' => 'Debate Accepted',
        'description' => 'Argument that has been accepted or resolved.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"resolved_by_suggestion_id","label"=>"Resolved By Suggestion","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],

    // --- Law Proposal ---
    [
        'inquiry_type' => 'chapter',
        'family' => 'law_proposal',
        'icon' => 'BookOpenVariant',
        'label' => 'Chapter',
        'description' => 'Chapter in a law proposal, can contain articles.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['article','official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'article',
        'family' => 'law_proposal',
        'icon' => 'FileDocument',
        'label' => 'Article',
        'description' => 'Article in a chapter, can contain amendments.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['amendment','official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'amendment',
        'family' => 'law_proposal',
        'icon' => 'FileDocumentEdit',
        'label' => 'Amendment',
        'description' => 'Amendment to a specific article, cannot be transformed further.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"article_ref","label"=>"Article Reference","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'law_proposal',
        'family' => 'legislative',
        'icon' => 'BookOpenVariant',
        'label' => 'Law Proposal',
        'description' => 'Draft or amendment of a law, with mapped articles and status.',
        'fields' => [
            ["key"=>"parent_law_id","label"=>"Loi parente","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"article_map","label"=>"Article associé","type"=>"string","required"=>false,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"legal_status","label"=>"Statut juridique","type"=>"enum","required"=>true,"allowed_values"=>["pending","validated","rejected"],"default"=>"pending","rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"facilitator_id","label"=>"Facilitateur","type"=>"users","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['amendment','objection','response','official'],
        'allowed_transformation' => ['decree','policy'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'amendment',
        'family' => 'legislative',
        'icon' => 'FileDocument-edit',
        'label' => 'Amendment',
        'description' => 'Amendment targeting a specific article of a law or proposal.',
        'fields' => [
            ["key"=>"parent_law_id","label"=>"Parent Law","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"article_ref","label"=>"Article Reference","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"facilitator_id","label"=>"Facilitator","type"=>"users","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => null,
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'constitutional_workshop',
        'family' => 'legislative',
        'icon' => 'Library',
        'label' => 'Constitutional Workshop',
        'description' => 'Workshop for drafting or revising constitutional texts.',
        'fields' => [
            ["key"=>"draft_text","label"=>"Draft Text","type"=>"text","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"article_map","label"=>"Article Map","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"facilitator_id","label"=>"Facilitator","type"=>"users","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['law_proposal'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'policy_consultation',
        'family' => 'legislative',
        'icon' => 'AccountVoice',
        'label' => 'Policy Consultation',
        'description' => 'Consultation on public policies with impact evaluation.',
        'fields' => [
            ["key"=>"policy_area","label"=>"Policy Area","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"impact_assessment","label"=>"Impact Assessment","type"=>"text","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['law_proposal','response'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Deliberative ---
    [
        'inquiry_type' => 'debate',
        'family' => 'deliberative',
        'icon' => 'Forum',
        'label' => 'Debate',
        'description' => 'Public debate with a neutral facilitator and optional quorum.',
        'fields' => [
            ["key"=>"facilitator_id","label"=>"Facilitator","type"=>"users","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"quorum","label"=>"Quorum","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['suggestion','proposal','petition','official'],
        'allowed_transformation' => ['law_proposal','policy_consultation'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'objection',
        'family' => 'deliberative',
        'icon' => 'AlertCircle',
        'label' => 'Objection',
        'description' => 'Objection linked to another inquiry, can be resolved via suggestions.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['suggestion'],
        'allowed_transformation' => null,
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'suggestion',
        'family' => 'deliberative',
        'icon' => 'Lightbulb',
        'label' => 'Suggestion',
        'description' => 'Suggestion to solve or refine an objection or debate argument.',
        'fields' => [
            ["key"=>"parent_inquiry_id","label"=>"Parent Inquiry","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => null,
        'allowed_transformation' => ['proposal','law_proposal'],
        'is_option' => 1,
        'created' => '',
    ],
    [
        'inquiry_type' => 'proposal',
        'family' => 'deliberative',
        'icon' => 'LightbulbOn',
        'label' => 'Proposal',
        'description' => 'Citizen proposal requiring support or linked to a future law.',
        'fields' => [
            ["key"=>"quorum","label"=>"Quorum","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"parent_law_id","label"=>"Parent Law","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['objection','suggestion','official'],
        'allowed_transformation' => ['law_proposal','initiative'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'petition',
        'family' => 'deliberative',
        'icon' => 'ClipboardText',
        'label' => 'Petition',
        'description' => 'Petition requiring citizen signatures.',
        'fields' => [
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => ['law_proposal','initiative'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'vision',
        'family' => 'deliberative',
        'icon' => 'Map',
        'label' => 'Vision / Roadmap',
        'description' => 'Long-term citizen roadmap or strategic vision.',
        'fields' => [
            ["key"=>"horizon_year","label"=>"Horizon Year","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => null,
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'initiative',
        'family' => 'deliberative',
        'icon' => 'RocketLaunch',
        'label' => 'Initiative',
        'description' => 'Collective citizen initiative requiring a threshold of support.',
        'fields' => [
            ["key"=>"co_owners","label"=>"Co-owners","type"=>"string","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"quorum","label"=>"Quorum","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['proposal','petition','official'],
        'allowed_transformation' => ['law_proposal'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'deliberation',
        'family' => 'deliberative',
        'icon' => 'AccountMultipleCheck',
        'label' => 'Deliberation',
        'description' => 'Citizen jury or deliberation assembly with defined participants.',
        'fields' => [
            ["key"=>"facilitator_id","label"=>"Facilitator","type"=>"users","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"participants_list","label"=>"Participants List","type"=>"json","required"=>true,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['proposal','suggestion','official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Project / Review ---
    [
        'inquiry_type' => 'project',
        'family' => 'deliberative',
        'icon' => 'BriefcaseCheck',
        'label' => 'Project',
        'description' => 'Concrete project with cost, responsible unit, and deadline.',
        'fields' => [
            ["key"=>"budget","label"=>"Budget","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"deadline","label"=>"Deadline","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['project_review','suggestion','official'],
        'allowed_transformation' => ['law_proposal','initiative'],
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'project_review',
        'family' => 'deliberative',
        'icon' => 'ChartBar',
        'label' => 'Project Review',
        'description' => 'Citizen evaluation of an ongoing or completed project.',
        'fields' => [
            ["key"=>"evaluation_score","label"=>"Evaluation Score","type"=>"integer","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"progress_status","label"=>"Progress Status","type"=>"string","required"=>false,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Collective ---
    [
        'inquiry_type' => 'assembly',
        'family' => 'collective',
        'icon' => 'AccountGroup',
        'label' => 'Assembly',
        'description' => 'Citizen assembly or meeting with agenda and facilitator.',
        'fields' => [
            ["key"=>"agenda","label"=>"Agenda","type"=>"text","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"facilitator_id","label"=>"Facilitator","type"=>"users","required"=>false,"default"=>null,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'consultation',
        'family' => 'collective',
        'icon' => 'CalendarMultiselect',
        'label' => 'Consultation',
        'description' => 'Grouped inquiries or consultations on a specific theme.',
        'fields' => [
            ["key"=>"topic","label"=>"Topic","type"=>"string","required"=>true,"default"=>null,"rules"=>["maxLength"=>255]],
            ["key"=>"start_date","label"=>"Start Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"end_date","label"=>"End Date","type"=>"datetime","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"form_schema","label"=>"Form Schema","type"=>"json","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"type_of_vote","label"=>"Type of Vote","type"=>"enum","required"=>false,"default"=>"simple","allowed_values"=>["simple","majority_judgement_beneficial","majority_judgement_number"],"rules"=>[]],
            ["key"=>"voting_start","label"=>"Voting Start","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"voting_end","label"=>"Voting End","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['law_proposal','response','official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Official (single type) ---
    [
        'inquiry_type' => 'official',
        'family' => 'official',
        'icon' => 'ClipboardCheck',
        'label' => 'Official Response',
        'description' => 'Official answer to an inquiry (accepted, rejected, under review).',
        'fields' => [
            ["key"=>"responder_id","label"=>"Responder","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"resolution_status","label"=>"Resolution Status","type"=>"enum","required"=>true,"default"=>"pending","allowed_values"=>["pending","accepted","rejected"],"rules"=>[]]
        ],
        'allowed_response' => null,
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Administrative / Service / Social ---
    [
        'inquiry_type' => 'admin_request',
        'family' => 'administrative',
        'icon' => 'FileDocument',
        'label' => 'Administrative Request',
        'description' => 'General citizen administrative requests.',
        'fields' => [
            ["key"=>"request_type","label"=>"Request Type","type"=>"string","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"processing_deadline","label"=>"Processing Deadline","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"resolution_date","label"=>"Resolution Date","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'grievance',
        'family' => 'administrative',
        'icon' => 'AlertOctagon',
        'label' => 'Grievance',
        'description' => 'Complaint or report regarding an administrative issue.',
        'fields' => [
            ["key"=>"severity","label"=>"Severity","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"resolution_date","label"=>"Resolution Date","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'service_request',
        'family' => 'service',
        'icon' => 'Offer',
        'label' => 'Service / Social Request',
        'description' => 'General service request or social support demand.',
        'fields' => [
            ["key"=>"support_type","label"=>"Support Type","type"=>"string","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"eligibility","label"=>"Eligibility","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"processing_deadline","label"=>"Processing Deadline","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"resolution_date","label"=>"Resolution Date","type"=>"datetime","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_launch","label"=>"Auto launch","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],

    // --- Social / Childcare / Housing / Scholarship (examples) ---
    [
        'inquiry_type' => 'scholarship_request',
        'family' => 'social',
        'icon' => 'School',
        'label' => 'Scholarship Request',
        'description' => 'Request for scholarship or educational aid.',
        'fields' => [
            ["key"=>"student_id","label"=>"Student ID","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"requested_amount","label"=>"Requested Amount","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_launch","label"=>"Auto launch","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'childcare_request',
        'family' => 'social',
        'icon' => 'BabyCarriage',
        'label' => 'Childcare Request',
        'description' => 'Request for childcare support or enrollment.',
        'fields' => [
            ["key"=>"child_id","label"=>"Child ID","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_launch","label"=>"Auto launch","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ],
    [
        'inquiry_type' => 'housing_request',
        'family' => 'social',
        'icon' => 'Home',
        'label' => 'Housing Request',
        'description' => 'Request for housing support or allocation.',
        'fields' => [
            ["key"=>"applicant_id","label"=>"Applicant ID","type"=>"integer","required"=>true,"default"=>null,"rules"=>[]],
            ["key"=>"priority_status","label"=>"Priority Status","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"assigned_unit","label"=>"Assigned Unit","type"=>"string","required"=>false,"default"=>null,"rules"=>[]],
            ["key"=>"auto_launch","label"=>"Auto launch","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]],
            ["key"=>"auto_reminder","label"=>"Auto Reminder","type"=>"boolean","required"=>true,"default"=>true,"rules"=>[]]
        ],
        'allowed_response' => ['official'],
        'allowed_transformation' => null,
        'is_option' => 0,
        'created' => '',
    ]
    ];

    private array $inquiryStatuses = [

    // ----------------------
    // DELIBERATIVE FAMILY
    // ----------------------
    'proposal' => [
    ['status_key' => 'under_process',      'label' => 'Under Process',      'description' => 'The proposal is being reviewed.',              'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'need_revised',       'label' => 'Need Revised',       'description' => 'The proposal requires changes.',               'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'rejected',           'label' => 'Rejected',           'description' => 'The proposal was not accepted.',               'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ['status_key' => 'collecting_support', 'label' => 'Collecting Support', 'description' => 'The proposal is open for support.',             'is_final' => false, 'icon' => 'Offer',       'sort_order' => 4],
    ['status_key' => 'quorum_reached',     'label' => 'Quorum Reached',     'description' => 'The proposal reached required support.',       'is_final' => true,  'icon' => 'Check',       'sort_order' => 5],
    ],
    'petition' => [
    ['status_key' => 'under_process',      'label' => 'Under Process',      'description' => 'The petition is under review.',                'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'need_revised',       'label' => 'Need Revised',       'description' => 'The petition needs improvements.',             'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'rejected',           'label' => 'Rejected',           'description' => 'The petition was not accepted.',               'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ['status_key' => 'collecting_support', 'label' => 'Collecting Support', 'description' => 'The petition is open for signatures.',          'is_final' => false, 'icon' => 'Offer',       'sort_order' => 4],
    ['status_key' => 'quorum_reached',     'label' => 'Quorum Reached',     'description' => 'The petition reached the required signatures.', 'is_final' => true,  'icon' => 'Check',       'sort_order' => 5],
    ],
    'initiative' => [
    ['status_key' => 'under_process',      'label' => 'Under Process',      'description' => 'The initiative is being reviewed.',            'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'collecting_support', 'label' => 'Collecting Support', 'description' => 'The initiative is open for support.',           'is_final' => false, 'icon' => 'Offer',       'sort_order' => 2],
    ['status_key' => 'quorum_reached',     'label' => 'Quorum Reached',     'description' => 'The initiative reached required support.',      'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'rejected',           'label' => 'Rejected',           'description' => 'The initiative was not accepted.',              'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 4],
    ],
    'debate' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The debate is being prepared.',                 'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'discussion_open',  'label' => 'Discussion Open',  'description' => 'The debate is open for contributions.',          'is_final' => false, 'icon' => 'ForumOutline', 'sort_order' => 2],
    ['status_key' => 'concluded',        'label' => 'Concluded',        'description' => 'The debate has ended with conclusions.',         'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'rejected',         'label' => 'Rejected',         'description' => 'The debate was cancelled.',                      'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 4],
    ],
    'deliberation' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The deliberation is being prepared.',            'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'in_session',       'label' => 'In Session',       'description' => 'The deliberation is currently ongoing.',          'is_final' => false, 'icon' => 'ForumOutline', 'sort_order' => 2],
    ['status_key' => 'concluded',        'label' => 'Concluded',        'description' => 'The deliberation ended with conclusions.',        'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ],
    'vision' => [
    ['status_key' => 'draft',            'label' => 'Draft',            'description' => 'The vision document is being drafted.',          'is_final' => false, 'icon' => 'FileOutline',  'sort_order' => 1],
    ['status_key' => 'under_review',     'label' => 'Under Review',     'description' => 'The vision is being discussed.',                 'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'validated',        'label' => 'Validated',        'description' => 'The vision has been validated.',                 'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'archived',         'label' => 'Archived',         'description' => 'The vision has been archived.',                  'is_final' => true,  'icon' => 'Archive',     'sort_order' => 4],
    ],
    'objection' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The objection is being reviewed.',               'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'resolved',         'label' => 'Resolved',         'description' => 'The objection was resolved.',                    'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'dismissed',        'label' => 'Dismissed',        'description' => 'The objection was dismissed.',                   'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ],
    'suggestion' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The suggestion is under review.',                'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'integrated',       'label' => 'Integrated',       'description' => 'The suggestion has been integrated.',             'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'discarded',        'label' => 'Discarded',        'description' => 'The suggestion was not accepted.',                'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ],
    'project' => [
    ['status_key' => 'under_process',      'label' => 'Under Process',      'description' => 'The project is being prepared.',              'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'feasibility_review', 'label' => 'Feasibility Review', 'description' => 'The project is being checked for feasibility.','is_final' => false, 'icon' => 'EyeOutline',   'sort_order' => 2],
    ['status_key' => 'funded',             'label' => 'Funded',             'description' => 'The project has received funding.',            'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'not_funded',         'label' => 'Not Funded',         'description' => 'The project will not be financed.',            'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 4],
    ],
    'project_review' => [
    ['status_key' => 'in_progress',      'label' => 'In Progress',      'description' => 'The project review is ongoing.',                 'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'completed',        'label' => 'Completed',        'description' => 'The project review has been completed.',          'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ],

    // ----------------------
    // COLLECTIVE FAMILY
    // ----------------------
    'assembly' => [
    ['status_key' => 'planned',          'label' => 'Planned',          'description' => 'The assembly is planned but not started.',       'is_final' => false, 'icon' => 'Calendar',     'sort_order' => 1],
    ['status_key' => 'in_session',       'label' => 'In Session',       'description' => 'The assembly is ongoing.',                       'is_final' => false, 'icon' => 'ForumOutline', 'sort_order' => 2],
    ['status_key' => 'concluded',        'label' => 'Concluded',        'description' => 'The assembly ended with conclusions.',            'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ],
    'consultation' => [
    ['status_key' => 'open',             'label' => 'Open',             'description' => 'The consultation is open for contributions.',     'is_final' => false, 'icon' => 'ForumOutline', 'sort_order' => 1],
    ['status_key' => 'closed',           'label' => 'Closed',           'description' => 'The consultation has ended.',                     'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ],

    // ----------------------
    // LEGISLATIVE FAMILY
    // ----------------------
    'law_proposal' => [
    ['status_key' => 'draft',            'label' => 'Draft',            'description' => 'The law proposal is being drafted.',              'is_final' => false, 'icon' => 'FileOutline',  'sort_order' => 1],
    ['status_key' => 'under_review',     'label' => 'Under Review',     'description' => 'The law proposal is under discussion.',           'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'accepted',         'label' => 'Accepted',         'description' => 'The law proposal was accepted.',                  'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'rejected',         'label' => 'Rejected',         'description' => 'The law proposal was rejected.',                  'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 4],
    ],
    'amendment' => [
    ['status_key' => 'draft',            'label' => 'Draft',            'description' => 'The amendment is being drafted.',                 'is_final' => false, 'icon' => 'FileOutline',  'sort_order' => 1],
    ['status_key' => 'under_review',     'label' => 'Under Review',     'description' => 'The amendment is under review.',                  'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'accepted',         'label' => 'Accepted',         'description' => 'The amendment was accepted.',                     'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'rejected',         'label' => 'Rejected',         'description' => 'The amendment was rejected.',                     'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 4],
    ],
    'constitutional_workshop' => [
    ['status_key' => 'drafting',         'label' => 'Drafting',         'description' => 'The constitutional workshop is drafting text.',   'is_final' => false, 'icon' => 'FileOutline',  'sort_order' => 1],
    ['status_key' => 'reviewing',        'label' => 'Reviewing',        'description' => 'The workshop text is being reviewed.',            'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 2],
    ['status_key' => 'validated',        'label' => 'Validated',        'description' => 'The constitutional text has been validated.',     'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ],
    'policy_consultation' => [
    ['status_key' => 'open',             'label' => 'Open',             'description' => 'The policy consultation is open.',                'is_final' => false, 'icon' => 'ForumOutline', 'sort_order' => 1],
    ['status_key' => 'closed',           'label' => 'Closed',           'description' => 'The policy consultation is closed.',              'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ],
    'response' => [
    ['status_key' => 'under_review',     'label' => 'Under Review',     'description' => 'The response is being reviewed.',                 'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'accepted',         'label' => 'Accepted',         'description' => 'The response was accepted.',                      'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'rejected',         'label' => 'Rejected',         'description' => 'The response was rejected.',                      'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ],

    // ----------------------
    // ADMINISTRATIVE FAMILY
    // ----------------------
    'admin_request' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The request is being processed.',                 'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'resolved',         'label' => 'Resolved',         'description' => 'The request was resolved.',                       'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'unresolved',       'label' => 'Unresolved',       'description' => 'The request could not be resolved.',              'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ],
    'grievance' => [
    ['status_key' => 'under_investigation',  'label' => 'Under Investigation',  'description' => 'The grievance is being investigated.',       'is_final' => false, 'icon' => 'Magnify',     'sort_order' => 1],
    ['status_key' => 'resolved_by_proposal', 'label' => 'Resolved by Proposal', 'description' => 'The grievance was resolved via proposal.',   'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'resolved_directly',    'label' => 'Resolved Directly',    'description' => 'The grievance was resolved directly.',       'is_final' => true,  'icon' => 'Check',       'sort_order' => 3],
    ['status_key' => 'unresolved',           'label' => 'Unresolved',           'description' => 'The grievance could not be resolved.',       'is_final' => true,  'icon' => 'AlertCircleOutline', 'sort_order' => 4],
    ],

    // ----------------------
    // SERVICE FAMILY
    // ----------------------
    'service_request' => [
    ['status_key' => 'under_process',    'label' => 'Under Process',    'description' => 'The service request is being processed.',         'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'resolved',         'label' => 'Resolved',         'description' => 'The service request was resolved.',               'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ['status_key' => 'unresolved',       'label' => 'Unresolved',       'description' => 'The service request could not be resolved.',      'is_final' => true,  'icon' => 'Cancel',      'sort_order' => 3],
    ],

    // ----------------------
    // OFFICIAL FAMILY
    // ----------------------
    'official_response' => [
    ['status_key' => 'under_review',     'label' => 'Under Review',     'description' => 'The official response is under review.',          'is_final' => false, 'icon' => 'ClockOutline', 'sort_order' => 1],
    ['status_key' => 'published',        'label' => 'Published',        'description' => 'The official response was published.',            'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ],
    'official_document' => [
    ['status_key' => 'draft',            'label' => 'Draft',            'description' => 'The official document is in draft.',              'is_final' => false, 'icon' => 'FileOutline',  'sort_order' => 1],
    ['status_key' => 'published',        'label' => 'Published',        'description' => 'The official document is published.',             'is_final' => true,  'icon' => 'Check',       'sort_order' => 2],
    ],
    ];


    private array $categories = [
    // 1. Ecology & Resources
    ['name' => 'Ecology & Resources', 'parent' => 0],
    ['name' => 'Biodiversity', 'parent' => 'Ecology & Resources'],
    ['name' => 'Water & Lagoon', 'parent' => 'Ecology & Resources'],
    ['name' => 'Land & Farming', 'parent' => 'Ecology & Resources'],
    ['name' => 'Energy', 'parent' => 'Ecology & Resources'],
    ['name' => 'Waste & Recycling', 'parent' => 'Ecology & Resources'],

    // 2. Planning & Development
    ['name' => 'Planning & Development', 'parent' => 0],
    ['name' => 'Housing & Urbanism', 'parent' => 'Planning & Development'],
    ['name' => 'Transport', 'parent' => 'Planning & Development'],
    ['name' => 'Public Works', 'parent' => 'Planning & Development'],
    ['name' => 'Tourism', 'parent' => 'Planning & Development'],
    ['name' => 'Local Economy', 'parent' => 'Planning & Development'],

    // 3. Health & Wellbeing
    ['name' => 'Health & Wellbeing', 'parent' => 0],
    ['name' => 'Care & Prevention', 'parent' => 'Health & Wellbeing'],
    ['name' => 'Health Access', 'parent' => 'Health & Wellbeing'],
    ['name' => 'Sports', 'parent' => 'Health & Wellbeing'],
    ['name' => 'Food & Nutrition', 'parent' => 'Health & Wellbeing'],
    ['name' => 'Healthy Environment', 'parent' => 'Health & Wellbeing'],

    // 4. Citizenship & Society
    ['name' => 'Citizenship & Society', 'parent' => 0],
    ['name' => 'Participation', 'parent' => 'Citizenship & Society'],
    ['name' => 'Associations', 'parent' => 'Citizenship & Society'],
    ['name' => 'Culture & Heritage', 'parent' => 'Citizenship & Society'],
    ['name' => 'Safety', 'parent' => 'Citizenship & Society'],
    ['name' => 'Youth & Seniors', 'parent' => 'Citizenship & Society'],

    // 5. Education & Spirituality
    ['name' => 'Education & Spirituality', 'parent' => 0],
    ['name' => 'School', 'parent' => 'Education & Spirituality'],
    ['name' => 'Training', 'parent' => 'Education & Spirituality'],
    ['name' => 'Language & Identity', 'parent' => 'Education & Spirituality'],
    ['name' => 'Spirituality', 'parent' => 'Education & Spirituality'],
    ['name' => 'Arts & Creativity', 'parent' => 'Education & Spirituality'],
    ];

    private array $locations = [
    ['name' => 'Country', 'parent' => 0],

    ['name' => 'State 1', 'parent' => 'Country'],
    ['name' => 'State 2', 'parent' => 'Country'],
    ['name' => 'State 3', 'parent' => 'Country'],
    ];

    public function __construct(IDBConnection $connection, IGroupManager $groupManager)
    {
        parent::__construct();
        $this->name = parent::NAME_PREFIX . 'db:init-default';
        $this->connection = $connection;
        $this->groupManager = $groupManager;
    }

    private function log(?IOutput $output, string $message): void
    {
        if ($output !== null) {
            $output->info($message);
        } else {
            $this->output->writeln('[InitDbDefault] ' . $message);
        }
    }


    public function runCommands(?IOutput $output = null): int
    {
        $this->log($output, 'Initializing default statuses...');


        $this->insertDefaultCategories($output);
        $this->insertDefaultLocations($output);
        $this->insertDefaultInquiryStatuses($output);
        $this->insertDefaultInquiryFamilies($output);
        $this->insertDefaultInquiryTypes($output);
        $this->createDefaultGroups($output);
        return 0;
    }

    private function insertDefaultCategories(?IOutput $output = null): void
    {
        $this->log($output, 'Inserting default categories...');

        $inserted = [];

        foreach ($this->categories as $category) {
            $query = $this->connection->prepare('SELECT `id` FROM `*PREFIX*'.Category::TABLE.'` WHERE `name` = ?');
            $cursor = $query->execute([$category['name']]);
            $row = $cursor->fetch();

            if ($row !== false) {
                $this->log($output, 'Category already exists: ' . $category['name']);
                $inserted[$category['name']] = (int) $row['id'];
                continue;
            }

            $parentId = $category['parent'] !== 0 && isset($inserted[$category['parent']]) ? $inserted[$category['parent']] : 0;

            $insert = $this->connection->prepare('INSERT INTO `*PREFIX*'.Category::TABLE.'` (`name`, `parent_id`) VALUES (?, ?)');
            $insert->execute([$category['name'], $parentId]);

            $id = (int) $this->connection->lastInsertId('*PREFIX*'.Category::TABLE);

            $inserted[$category['name']] = $id;


            $this->log($output, 'Inserted category: ' . $category['name']);
        }
    }

    private function insertDefaultLocations(?IOutput $output = null): void
    {
        $this->log($output, 'Inserting default locations...');

        $inserted = [];

        foreach ($this->locations as $location) {
            $query = $this->connection->prepare('SELECT `id` FROM `*PREFIX*'.Location::TABLE.'` WHERE `name` = ?');
            $cursor = $query->execute([$location['name']]);
            $row = $cursor->fetch();

            if ($row !== false) {
                $this->log($output, 'Location already exists: ' . $location['name']);
                $inserted[$location['name']] = (int) $row['id'];
                continue;
            }

            $parentId = $location['parent'] !== 0 && isset($inserted[$location['parent']]) ? $inserted[$location['parent']] : 0;

            $insert = $this->connection->prepare('INSERT INTO `*PREFIX*'.Location::TABLE.'` (`name`, `parent_id`) VALUES (?, ?)');
            $insert->execute([$location['name'], $parentId]);

            $id = (int) $this->connection->lastInsertId('*PREFIX*'.Location::TABLE);
            $inserted[$location['name']] = $id;

            $this->log($output, 'Inserted location: ' . $location['name']);
        }
    }

    private function insertDefaultInquiryStatuses(?IOutput $output = null): void
    {
        $this->log($output, 'Inserting default inquiry statuses...');

        foreach ($this->inquiryStatuses as $inquiryType => $statuses) {
            foreach ($statuses as $status) {
                $query = $this->connection->prepare(
                    'SELECT `id` FROM `*PREFIX*'.InquiryStatus::TABLE.'`
					WHERE `inquiry_type` = ? AND `status_key` = ?'
                );
                $cursor = $query->execute([$inquiryType, $status['status_key']]);
                $row = $cursor->fetch();

                if ($row !== false) {
                       $this->log($output, 'Inquiry status already exists: ' . $inquiryType . ' -> ' . $status['status_key']);
                       continue;
                }
                $insert = $this->connection->prepare(
                    'INSERT INTO `*PREFIX*'.InquiryStatus::TABLE.'` 
					(`inquiry_type`, `status_key`, `label`, `description`, `icon`, `is_final`, `sort_order`) 
					VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute(
                    [
                    $inquiryType,
                    $status['status_key'],
                    $status['label'],
                    $status['description'],
                    $status['icon'],
                    (int) $status['is_final'],
                    $status['sort_order'],
                    ]
                );
            }
        }
    }
    private function insertDefaultInquiryTypes(?IOutput $output = null): void
    {
        $this->log($output, 'Inserting default inquiry types...');

        $inserted = [];

        foreach ($this->inquiryTypes as $inquiryType) {
            $uniqueKey = $inquiryType['inquiry_type'] . '_' . $inquiryType['family'] . '_' . $inquiryType['is_option'];

            if (isset($inserted[$uniqueKey])) {
                $this->log($output, 'Inquiry type already processed: ' . $inquiryType['inquiry_type']);
                continue;
            }

            $tableName = '*PREFIX*' . InquiryType::TABLE;

            $query = $this->connection->prepare(
                'SELECT `id` FROM `' . $tableName . '`
				WHERE `inquiry_type` = ? AND `family` = ? AND `is_option` = ?'
            );

            $cursor = $query->execute(
                [
                $inquiryType['inquiry_type'],
                $inquiryType['family'],
                $inquiryType['is_option']
                ]
            );
            $row = $cursor->fetch();

            if ($row !== false) {
                   $this->log($output, 'Inquiry type already exists in DB: ' . $inquiryType['inquiry_type']);
                   $inserted[$uniqueKey] = (int) $row['id'];
                   continue;
            }

            $insert = $this->connection->prepare(
                'INSERT INTO `' . $tableName . '`
				(`inquiry_type`, `family`, `icon`, `label`, `description`, `fields`, `allowed_response`, `is_option`, `allowed_transformation`,`created`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $created = !empty($inquiryType['created']) ? (int)$inquiryType['created'] : time();
            $isOption = !empty($inquiryType['is_option']) ? (int)$inquiryType['is_option'] : 0;
            $icon = !empty($inquiryType['icon']) ? $inquiryType['icon'] : '';
            $description = !empty($inquiryType['description']) ? $inquiryType['description'] : '';
            $fields = !empty($inquiryType['fields']) ? json_encode($inquiryType['fields']) : '';
            $allowedResponse = !empty($inquiryType['allowed_response']) ? json_encode($inquiryType['allowed_response']) : '';
            $allowedTransformation = !empty($inquiryType['allowed_transformation']) ? json_encode($inquiryType['allowed_transformation']) : '';

            try {
                        $insert->execute(
                            [
                            $inquiryType['inquiry_type'],
                            $inquiryType['family'],
                            $icon,
                            $inquiryType['label'],
                            $description,
                            $fields,
                            $allowedResponse,
                            $isOption,
                            $allowedTransformation,
                            $created,
                            ]
                        );

                        $id = (int) $this->connection->lastInsertId($tableName);
                        $inserted[$uniqueKey] = $id;

                        $this->log($output, 'Inserted inquiry type: ' . $inquiryType['inquiry_type'] . ' (family: ' . $inquiryType['family'] . ')');
            } catch (\Exception $e) {
                  $this->log($output, 'ERROR inserting inquiry type ' . $inquiryType['inquiry_type'] . ': ' . $e->getMessage());
            }
        }
    }
    private function insertDefaultInquiryFamilies(?IOutput $output = null): void
    {
        $this->log($output, 'Inserting default inquiry families...');

        $inserted = [];

        foreach ($this->inquiryTypeFamilies as $family) {
            if (isset($inserted[$family['family_type']])) {
                $this->log($output, 'Inquiry family already processed: ' . $family['family_type']);
                continue;
            }

            $query = $this->connection->prepare(
                'SELECT `id` FROM `*PREFIX*' . InquiryFamily::TABLE . '`
				WHERE `family_type` = ?'
            );
            $cursor = $query->execute([$family['family_type']]); 
            $row = $cursor->fetch();

            if ($row !== false) {
                   $this->log($output, 'Inquiry family already exists in DB: ' . $family['family_type']);
                   $inserted[$family['family_type']] = (int) $row['id'];
                   continue;
            }

            $insert = $this->connection->prepare(
                'INSERT INTO `*PREFIX*' . InquiryFamily::TABLE . '`
				(`family_type`, `label`, `description`, `icon`, `sort_order`, `created`)
				VALUES (?, ?, ?, ?, ?, ?)'
            );

            try {
                     $created = !empty($family['created']) ? (int)$family['created'] : time();

                        $insert->execute(
                            [ 
                            $family['family_type'],
                            $family['label'],
                            $family['description'] ?? '',
                            $family['icon'] ?? '',
                            $family['sort_order'] ?? 0,
                            $created,
                            ]
                        );

                        $id = (int) $this->connection->lastInsertId('*PREFIX*' . InquiryFamily::TABLE); 
                        $inserted[$family['family_type']] = $id;

                        $this->log($output, 'Inserted inquiry family: ' . $family['family_type']);
            } catch (\Exception $e) {
                  $this->log($output, 'ERROR inserting inquiry family ' . $family['family_type'] . ': ' . $e->getMessage());
            }
        }
    }



    private function createDefaultGroups(?IOutput $output = null): void
    {

        $this->log($output, 'Creating default Nextcloud groups...');

        $groups = ['Agora Users','Agora Moderator', 'Agora Official','Agora Legislative','Agora Administrative','Agora Collective'];

        foreach ($groups as $groupName) {
            $group = $this->groupManager->get($groupName);
            if ($group !== null) {
                $this->log($output, 'Group already exists: ' . $groupName);
                continue;
            }
            $this->groupManager->createGroup($groupName);
        }
    }
}

