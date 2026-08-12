# examples

The documents qPortal executes: routing, pages, endpoints. This directory is the
untouched reference — it ships with the repository and every update keeps it
current. It is not where you work.

To set up an installation:

```sh
cp -r examples/* program/
```

then in `config/config.ini`:

```ini
[runtime]
PROGRAM_DIR = "__ROOT_DIR/program"
```

`program/` is yours from that moment on and no update will touch it. When you
want to know what a later version added, diff it against this directory —
the same ritual as `config/default.ini` against your `config/config.ini`.

## Layout

| | |
|---|---|
| `main.xml` | routing tree — `FRONTEND_INDEX` points here |
| `realms/` | the worlds; `realms/<realm>/…` shadows the base path when that realm is active |
| `api/` | endpoints, in/out doctype per document |
| `templates/` | parameterised fragments pulled in via `<sub src="…"><param …/></sub>` |
| `validation/` | XSD, `XML_SCHEMA_DEFAULT` points at `validation/main.xsd` |
| `edit/` | editor surfaces — `EDIT_INDEX`, `INTERN` |

## Paths inside documents

Write `%PROGRAM_DIR%/…`, never `examples/…`. Both `<tree src>` and `<sub src>`
expand it, so a document keeps working after it is copied to `program/`.
`%ROOT_DIR%` still refers to the installation root and is right for anything
outside this directory — the faehub submodule, for instance.
