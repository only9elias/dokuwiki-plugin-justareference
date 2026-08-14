<?php

/**
 * Configuration metadata for Just a Reference
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */

$meta['owner_mode'] = ['multichoice', '_choices' => ['nsname', 'start', 'nspage']];
$meta['classification_mode'] = ['multichoice', '_choices' => ['simple', 'full']];
$meta['mark_originals'] = ['onoff'];
$meta['show_to'] = ['string'];
$meta['include_camelcase'] = ['onoff'];
$meta['include_autolink'] = ['onoff'];
