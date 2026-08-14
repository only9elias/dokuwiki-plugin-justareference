# Just a Reference Plugin for DokuWiki

DokuWiki plugin that indicates whether a wiki-internal link is the **original**
link (on the target’s owner page) or merely a **reference**.

## Features

- Structural original/reference classification (not “first occurrence on the page”)
- Optional full taxonomy: original, reference, self-page, and section links
- Prefix icons in the same style as core external/mailto link icons, plus a
  composed `title` tooltip
- Image-name links, interwiki, external, windows-share, and email links stay
  unmarked
- Server-side classification only (no JavaScript)
- Backlinks and other render formats keep working (markers are XHTML/print)

## Installation

1. Install into `lib/plugins/justareference/` (Extension Manager or manual
   copy). If the folder is named differently, the plugin will not work!
2. Ensure the plugin is enabled.
3. Optionally adjust settings under **Admin → Configuration Settings →
   Just a Reference Plugin**.
4. Purge the cache after CSS or markup changes (`&purge=true` or clear
   `data/cache`).

Please refer to https://www.dokuwiki.org/extensions for additional info on how
to install extensions in DokuWiki.

## How original vs reference is decided

For a link on the current page `$ID` to a resolved target page:

1. Section hashes (`[[page#section]]`, `[[#section]]`) are **section** links
   (folded into **reference** unless `classification_mode` is `full`).
2. A page link to the current page is **self** (also folded into **reference**
   in `simple` mode). Self takes precedence if a page is also its own owner.
3. Otherwise the link is **original** when `$ID` equals the target’s **owner
   page**, else **reference**.

Root targets (no parent namespace) have no owner in this version, so they are
never original.

### Owner page (`owner_mode`)

These match DokuWiki’s own namespace-default targets for `[[foo:bar:]]`:

| `owner_mode` | Owner of `a:b:c` | Owner of `a:b` |
| ------------ | ---------------- | -------------- |
| `nsname` (default) | `a:b:b` | `a:a` |
| `start` | `a:b:` + start page (usually `a:b:start`) | `a:` + start page |
| `nspage` | `a:b` | `a` |

Example with `nsname`: a link to `dingdong:blubb:schakalaka` is original only
on `dingdong:blubb:blubb`. A link to `dingdong:blubb:blubb` is original only
on `dingdong:dingdong` (when the computed owner would be the target itself,
the plugin walks up one namespace so index pages are not their own owners).
Root targets still have no owner.

## Link types

| Type | When | Marked? |
| ---- | ---- | ------- |
| original | Page link (no `#`) from the owner page | Only if `mark_originals` is on |
| reference | Other page links (and self/section in `simple` mode) | Yes |
| self | Page link to the current page (not a section link) | Yes in `full` mode |
| section | `[[page#s]]` or `[[#s]]` | Yes in `full` mode |
| unmarkable | `[[id\|{{image}}]]`, interwiki/external/windows/email from `[[…]]` | Never |

CamelCase links are included when `include_camelcase` is on (default). Autolink
*plugin* instructions are only classified when `include_autolink` is on and the
page id can be detected. Core `[[internal]]` links are always classified.

## Visuals

Marked links keep normal `wikilink1` / `wikilink2` styling and gain a class
such as `justareference-reference`. A small prefix icon is applied with CSS
(RTL-aware). The existing `title` (usually the page id) is composed with a
localized type label, e.g. `wiki:syntax – Reference`.

Unmarkable links are left unchanged.

## Configuration

| Setting | Default | Description |
| ------- | ------- | ----------- |
| `owner_mode` | `nsname` | Non-root owner: `nsname`, `start`, or `nspage` |
| `classification_mode` | `simple` | `simple` (original vs reference) or `full` taxonomy |
| `mark_originals` | off | If off, original page-links look like normal wikilinks |
| `show_to` | `all` | Who sees markers: `all` (no auth check), `logged_in`, `manager`, `admin`, or comma-separated groups |
| `include_camelcase` | on | Classify CamelCase links |
| `include_autolink` | off | Best-effort autolink plugin instructions |

When `show_to` is not `all`, page cache for affected XHTML is disabled so
markers cannot leak across users.

## Compatibility

- **XHTML:** prefix icons and tooltips
- **Print:** same icons via `print.css`
- **Metadata:** normal `internallink` / `locallink` registration (backlinks)
- **Text and other renderers:** unmodified link output (no markers)

## Roadmap

### After this version

- **Deep include/embed context:** classify links inside included/embedded
  content (include plugin, sidebar `tpl_include_page`, nested includes) with
  an explicit rule for whose page id the link is “on”
- **Root owner:** config for root targets (`none` / `start` / `sidebar`)
- Stronger autolink plugin integrations; optional hide-on-self-links;
  per-namespace overrides if still needed

### Later / lower priority

- Admin UI beyond Configuration Manager
- Visual markers in non-XHTML exports
- Auto owner mode mirroring core’s existence-check chain
- Screenshot for the dokuwiki.org plugin page

## Copyright

Copyright (C) only9elias <elias@noreply.blubb.app>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
