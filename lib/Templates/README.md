# Agora Template System

## Overview

The Agora template system allows administrators to customize the application's configuration data (inquiry families, types, statuses, categories, locations, etc.) by loading JSON template files. This system supports internationalization (i18n) through Transifex integration.

## Key Features

- **JSON-based templates**: Define configuration in structured JSON files
- **i18n integration**: Use translation keys that reference Transifex translations
- **Multiple use cases**: Support for citizen participation, enterprise, education, and more
- **Non-destructive**: Load templates into a clean instance or alongside existing data
- **Extensible**: Easy to create custom templates for specific organizational needs

## Template Structure

A template JSON file consists of the following sections:

### template_info (required)
Basic metadata about the template:
```json
{
  "template_info": {
    "name": "template_name",
    "version": "1.0.0",
    "description": "Template description",
    "author": "Author name",
    "use_case": "citizen_participation",
    "supports_i18n": true
  }
}
```

### inquiry_families (optional)
Define inquiry family types (e.g., deliberative, legislative, administrative):
```json
{
  "inquiry_families": [
    {
      "family_type": "deliberative",
      "label_key": "template.family.deliberative.label",
      "description_key": "template.family.deliberative.description",
      "icon": "AccountGroup",
      "sort_order": 1
    }
  ]
}
```

### inquiry_types (optional)
Define specific inquiry types (e.g., proposal, debate, petition):
```json
{
  "inquiry_types": [
    {
      "inquiry_type": "proposal",
      "family": "deliberative",
      "icon": "LightbulbOn",
      "label_key": "template.inquiry_type.proposal.label",
      "description_key": "template.inquiry_type.proposal.description",
      "is_root": true,
      "allowed_response": ["suggestion", "objection"]
    }
  ]
}
```

### inquiry_statuses (optional)
Define status workflows for inquiry types:
```json
{
  "inquiry_statuses": [
    {
      "inquiry_type": "proposal",
      "status_key": "draft",
      "label_key": "template.status.draft.label",
      "description_key": "template.status.draft.description",
      "is_final": false,
      "icon": "FileDocumentEdit",
      "sort_order": 1
    }
  ]
}
```

### option_types (optional)
Define option types for different inquiry families:
```json
{
  "option_types": [
    {
      "family": "debate",
      "option_type": "debate_for",
      "icon": "ThumbUp",
      "label_key": "template.option_type.debate_for.label",
      "description_key": "template.option_type.debate_for.description",
      "allowed_response": ["suggestion", "proposal"]
    }
  ]
}
```

### inquiry_group_types (optional)
Define how inquiries can be grouped:
```json
{
  "inquiry_group_types": [
    {
      "group_type": "theme",
      "label_key": "template.group_type.theme.label",
      "description_key": "template.group_type.theme.description",
      "icon": "Tag",
      "sort_order": 1
    }
  ]
}
```

### categories (optional)
Define categorization options:
```json
{
  "categories": [
    {
      "category_key": "urban_planning",
      "label_key": "template.category.urban_planning.label",
      "description_key": "template.category.urban_planning.description",
      "sort_order": 1
    }
  ]
}
```

### locations (optional)
Define location options:
```json
{
  "locations": [
    {
      "location_key": "city_center",
      "label_key": "template.location.city_center.label",
      "description_key": "template.location.city_center.description",
      "sort_order": 1
    }
  ]
}
```

## Usage

### 1. Clean Instance (Optional but Recommended)

Before loading a new template, clean the existing configuration:

```bash
occ agora:db:clean-instance
```

This removes all existing data including:
- User-created content (inquiries, comments, supports)
- Configuration data (categories, locations, types, statuses)
- Support tables (logs, preferences)

**Warning**: This operation cannot be undone! Make a backup first.

### 2. Load a Template

#### Load the default template:
```bash
occ agora:db:load-template --default
```

#### Load a custom template:
```bash
occ agora:db:load-template /path/to/your/template.json
```

### 3. Verify the Results

The command will output detailed information about:
- Which sections were loaded
- How many items were created in each section
- Any errors encountered

## Internationalization (i18n)

### Using i18n Keys

Templates should reference i18n keys instead of hardcoded text:

```json
{
  "label_key": "template.inquiry_type.proposal.label",
  "description_key": "template.inquiry_type.proposal.description"
}
```

### Adding Translations

1. Add translation keys to `l10n/en_GB.json`:
```json
{
  "translations": {
    "template.inquiry_type.proposal.label": "Proposal",
    "template.inquiry_type.proposal.description": "A proposal for improving the community"
  }
}
```

2. Translations are managed through Transifex for all supported languages

### Fallback Values

For custom or AI-generated templates, you can include fallback values:

```json
{
  "label_key": "custom.my_type.label",
  "label": "My Custom Type",
  "description_key": "custom.my_type.description",
  "description": "This is a custom type"
}
```

If the translation key is not found, the fallback value will be used.

## Creating Custom Templates

### For Specific Use Cases

You can create templates for different organizational contexts:

- **Enterprise IT**: Help desk tickets, automation requests, service requests
- **Education**: Course proposals, student feedback, facility requests
- **Healthcare**: Patient feedback, improvement suggestions, facility issues
- **Nonprofit**: Project proposals, volunteer coordination, community feedback

### Template Design Guidelines

1. **Keep it focused**: Design templates for specific use cases
2. **Use clear naming**: Use descriptive `family_type`, `inquiry_type`, and `status_key` values
3. **Define workflows**: Create appropriate status progressions for each inquiry type
4. **Plan relationships**: Define which inquiry types can respond to others via `allowed_response`
5. **Provide i18n keys**: Always include translation keys for official templates
6. **Test thoroughly**: Load templates in a test environment first

## Examples

See the included templates:
- `default_citizen_participation.json` - Default citizen participation template (included)

## Technical Details

### Template Loader Service

The `TemplateLoader` service (`lib/Service/TemplateLoader.php`) handles:
- JSON parsing and validation
- i18n key resolution
- Entity creation and database insertion
- Error handling and reporting

### Database Mappers

Templates use the following database mappers:
- `CategoryMapper`
- `InquiryFamilyMapper`
- `InquiryGroupTypeMapper`
- `InquiryOptionTypeMapper`
- `InquiryStatusMapper`
- `InquiryTypeMapper`
- `LocationMapper`

## Troubleshooting

### Template won't load
- Verify JSON syntax is valid
- Check that all required fields are present
- Ensure `template_info` section exists

### Duplicate errors
- Run `occ agora:db:clean-instance` first to remove existing data
- Check for unique constraint violations in your template

### Missing translations
- Verify i18n keys are added to `l10n/en_GB.json`
- Use fallback values for custom templates
- Check Transifex for translation status

## Future Enhancements

Planned improvements:
- Web-based wizard UI for template selection
- Template validation command
- Template export functionality
- AI-assisted template generation
- Multi-language template bundles

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/vinimoz/agora
- Issues: https://github.com/vinimoz/agora/issues
