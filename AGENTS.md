## codex project memory

This project has repository-specific Codex workflow documentation in `.codex/`.

For every new non-trivial task:
- Read `.codex/README.md`, `.codex/PROJECT_STRUCTURE.md`, and `.codex/RULES.md` before broad analysis or implementation.
- Use `.codex/CHECKLISTS.md` as the task checklist.
- Create or update task documentation with one shared task id in:
  - `.codex/specs/`
  - `.codex/analysis/`
  - `.codex/implementation/`
  - `.codex/reports/`
- Write new and updated `.codex` documents in Russian, keeping technical identifiers, paths, routes, code, SQL, XML, PHP/Twig/JS fragments, and config values untranslated.
- Do not store secrets, tokens, full logs, customer data, or environment credentials in `.codex`.
- Before finishing, update `.codex` if the task discovers durable project knowledge.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, invoke the `skill` tool with `skill: "graphify"` before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
