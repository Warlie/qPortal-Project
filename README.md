# qPortal-Project
This project is purely experimental at this point and serves no practical purpose.

## Installation

The repository ships what applies to every installation; an installation adds
what differs. Two pairs follow that rule, and both work the same way.

**Content** — copy, then it is yours:

```sh
cp -r examples/* program/
```

```ini
; config/config.ini
[runtime]
PROGRAM_DIR = "__ROOT_DIR/program"
```

`program/` is ignored by git. No update touches it. See `examples/README.md`.

**Configuration** — `config/config.ini` is laid over `config/default.ini`, so it
only needs the lines that differ. Everything else stays inherited, including the
plugin registry in `[short]`, which means plugins added by a later version reach
existing installations on their own.

After an update, diff against the references to see what is new:

```sh
diff -ru examples/ program/
diff config/default.ini config/config.ini
```

## Submodules

```sh
git clone --recursive …    # or: git submodule update --init --recursive
```
