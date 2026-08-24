# Inspectors API Reference

## Inspector Interfaces

### MetadataInspectorInterface
### DownloadStatsInspectorInterface
### ContentInspectorInterface
### ActivityInspectorInterface
### SecurityInspectorInterface
### QualityInspectorInterface

## Inspector Implementations

### MetadataInspector
### DownloadStatsInspector
### ContentInspector
### ActivityInspector
### SecurityInspector
### QualityInspector

`QualityInspector` combines package metadata and repository contents into a score from 0 to 100. Each available quality signal contributes 15 points:

- README
- license
- tests
- description
- repository URL
- `.gitattributes`
- `.gitignore`

Ignored example, demo, documentation, test, and specification files reduce the score by 2 points each, up to a maximum 15-point penalty. Scores are capped between 0 and 100 and graded as follows: A from 90, B from 75, C from 60, D from 40, and F below 40.

## Usage Patterns

### Single Inspector Usage
### Multiple Inspector Coordination
### Provider Iteration

## Configuration

### Provider Registration
### Inspector Configuration
### Dependency Injection

## Error Handling

### Exception Handling
### Fallback Strategies
### Provider Failures

## Performance

### Provider Ordering
### Parallel Execution
### Caching Strategy
