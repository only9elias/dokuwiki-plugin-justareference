<?php

/**
 * English language for Just a Reference settings
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */

$lang['owner_mode'] = 'Owner page for non-root targets. <code>nsname</code>: parent namespace + that namespace’s name (a:b:c → a:b:b). <code>start</code>: parent namespace start page. <code>nspage</code>: the parent namespace as a page (a:b:c → a:b).';
$lang['classification_mode'] = 'simple: mark original vs reference only (self and section links count as references). full: distinguish original, reference, self, and section links.';
$lang['mark_originals'] = 'Also mark original page-links (off: only non-original types get a prefix icon).';
$lang['show_to'] = 'Who sees markers. <code>all</code> (default, no auth check), <code>logged_in</code>, <code>manager</code>, <code>admin</code>, or a comma-separated list of groups.';
$lang['include_camelcase'] = 'Classify CamelCase links the same way as <code>[[internal]]</code> links.';
$lang['include_autolink'] = 'Best-effort: also classify links emitted as autolink plugin instructions (when detectable). Core internallink instructions are always classified.';
