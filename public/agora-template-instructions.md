# Creating Custom Agora Templates with AI

This guide explains how to create custom templates for Agora using AI assistants like ChatGPT, Claude, Gemini, or local LLMs.

## Quick Start

1. **Download the schema**: Get the `agora-template-schema.json` file
2. **Choose your AI**: Use ChatGPT, Claude, Gemini, or a local LLM
3. **Generate your template**: Provide the prompt below with your requirements
4. **Upload and test**: Import the generated JSON template

## AI Prompt Template

Copy and paste this prompt to your AI assistant:

```
I need to create a custom Agora template following this JSON schema:

[Paste the contents of agora-template-schema.json here]

Please create a template for: [DESCRIBE YOUR USE CASE]

Requirements:
- Use case category: [e.g., "healthcare_services", "community_planning", "nonprofit_management"]
- Languages: [e.g., "en", "de", "fr"]
- Inquiry types: [List the types of inquiries/proposals users should be able to create]
- Status workflow: [Describe the lifecycle: submitted → reviewed → approved → completed, etc.]
- Categories: [How should inquiries be categorized?]

Additional details:
- Support feature: Use "binary" for yes/no voting, "ternary" for yes/no/abstain, or "none" for no voting
- Allowed option types: Include ["position_for", "position_against", "alternative", "official_summary"]
- Icons: Use Nextcloud icon names (e.g., "Star", "Heart", "CheckCircle", "Alert")

Please generate a complete, valid JSON template that I can upload directly to Agora.
```

## Example Use Cases

### Healthcare Services
```
Use case: Patient feedback and improvement suggestions
Inquiry types:
- Service improvement suggestions
- Appointment scheduling feedback
- Facility improvement requests
- Medical equipment requests
```

### Community Planning
```
Use case: Citizen participation in urban development
Inquiry types:
- Infrastructure proposals
- Park and recreation suggestions
- Public safety initiatives
- Transportation improvements
```

### Nonprofit Management
```
Use case: Volunteer and donor engagement
Inquiry types:
- Volunteer program ideas
- Fundraising initiatives
- Community outreach programs
- Partnership proposals
```

## Schema Field Explanations

### Template Info
- **name**: Unique identifier (lowercase, underscores only)
- **use_case**: Category for filtering templates
- **available_languages**: ISO language codes (en, de, fr, gsw, etc.)

### Inquiry Types
- **inquiry_type**: Unique ID for each type of inquiry
- **family**: Grouping category for related inquiry types
- **is_root**: `true` for user-initiated inquiries, `false` for responses
- **support_feature**:
  - `"binary"`: Yes/No voting (most common)
  - `"ternary"`: Yes/No/Abstain voting
  - `"none"`: No voting (e.g., official responses)
- **allowed_option_type**: Types of options users can create:
  - `"position_for"`: Arguments in favor
  - `"position_against"`: Arguments against
  - `"alternative"`: Alternative proposals
  - `"official_summary"`: Official summaries

### Inquiry Statuses
Define the workflow stages for each inquiry type:
- **status_key**: Unique identifier
- **is_final**: `true` for terminal states (completed, rejected)
- **sort_order**: Defines the progression order

### Categories
Optional categorization for organizing inquiries:
- **category_key**: Unique identifier
- **sort_order**: Display order

## Tips for Best Results

1. **Be specific**: Describe your use case in detail
2. **Define clear workflows**: Map out the status progression before generating
3. **Use consistent naming**: Keep inquiry_type names descriptive (e.g., "budget_proposal" not "bp1")
4. **Test incrementally**: Start with 2-3 inquiry types, then expand
5. **Validate JSON**: Use a JSON validator before uploading
6. **Review translations**: If using multiple languages, verify translations are accurate

## Common AI Assistants

### ChatGPT (OpenAI)
- Best for: Complex templates with many inquiry types
- Tip: Use GPT-4 for better schema compliance

### Claude (Anthropic)
- Best for: Detailed, well-structured templates
- Tip: Provide examples for better consistency

### Gemini (Google)
- Best for: Multilingual templates
- Tip: Specify translation requirements clearly

### Local LLMs (Ollama, LM Studio)
- Models: Llama 3, Mistral, CodeLlama
- Best for: Privacy-sensitive use cases
- Tip: Use larger models (70B+) for complex schemas

## Troubleshooting

**Validation errors**: Ensure all required fields are present
**Upload fails**: Check JSON syntax with jsonlint.com
**Missing translations**: All language codes must have translations for all fields
**Invalid inquiry_type**: Use lowercase letters, numbers, and underscores only

## Support

For issues or questions:
- GitHub: https://github.com/vinimoz/agora/issues
- Documentation: [Link to docs]
