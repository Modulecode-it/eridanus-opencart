# AGENTS.md

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
- Write new and updated `.agent` documents in Russian, keeping technical identifiers, paths, routes, code, SQL, XML, PHP/Blade/Twig/JS fragments, and config values untranslated.
- Do not store secrets, tokens, full logs, customer data, or environment credentials in `.agent`.
- Before finishing, update `.agent` if the task discovers durable project knowledge (see `.agent/CORE.md` for the routing table).

<!-- Опциональные секции плагинов раскомментируй при необходимости.

## graphify

This project has a knowledge graph at `graphify-out/` with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, invoke the `skill` tool with `skill: "graphify"` before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when `graphify-out/graph.json` exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts.
- Dirty `graphify-out/` files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

-->
