## agent memory system

This project uses the `.agent/` memory system for agent workflow documentation.

For every new non-trivial task:
- Read `.agent/README.md`, `.agent/CORE.md`, `.agent/PROJECT_STRUCTURE.md`, and `.agent/STACK.md` before broad analysis or implementation.
- Use `.agent/CHECKLISTS.md` as the task checklist.
- Create or update task documentation with one shared task id in:
  - `.agent/specs/`
  - `.agent/analysis/`
  - `.agent/implementation/`
  - `.agent/reports/`
- Keep `.agent/INDEX.md` (task registry) and `.agent/CHANGELOG.md` (memory system history) current.
- Write new and updated `.agent` documents in Russian, keeping technical identifiers, paths, routes, code, SQL, XML, PHP/Twig/JS fragments, and config values untranslated.
- Do not store secrets, tokens, full logs, customer data, or environment credentials in `.agent`.
- Before finishing, update `.agent` if the task discovers durable project knowledge (see `.agent/CORE.md` for the routing table: PROJECT_STRUCTURE / STACK / RULES / CHECKLISTS / ADR).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, invoke the `skill` tool with `skill: "graphify"` before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
