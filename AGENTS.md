# Development Guidelines

- Keep changes scoped to the assigned task. Preserve established PHP / Vue 2 patterns; do not reformat or upgrade unrelated vendor code.
- Never commit real `.env`, credentials, private keys, deployment records, database exports, uploaded books, chapter text caches or build output. Use generated fixtures for tests.
- Use environment configuration and generated runtime configuration. Client-side API signing material is public to clients and is not an authorization boundary.
- Do not access production hosts, use production credentials, publish, merge or deploy as part of a development task unless explicitly authorized.
- Before changing behavior, define observable acceptance checks. Cover representative valid, invalid and failure paths with focused tests.
- Preserve the chapter shard and body-file naming rules. MyISAM does not support rollback; database transactions and filesystem compensation must be designed explicitly.
- Treat EPUB files, HTML, XML and uploaded resource paths as untrusted. Do not fetch remote resources or render untrusted HTML.
- Run `python3 -m unittest discover -s tests` and `python3 scripts/check_repository.py` before committing. For frontend changes, run `python3 scripts/build_h5.py`; for service changes, run the documented smoke check against an isolated environment.
- Report actual validation results and untested boundaries. Never claim a build, import or deployment passed solely because code was generated.
- Keep `README.md` and `docs/PROJECT_STRUCTURE.md` aligned with changed commands and storage boundaries. Retain upstream license notices and do not assign a new blanket license to third-party code.
