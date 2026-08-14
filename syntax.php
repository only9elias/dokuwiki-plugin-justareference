<?php

/**
 * Syntax component for Just a Reference (injected via PARSER_HANDLER_DONE)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  only9elias
 */

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Parsing\Handler;

class syntax_plugin_justareference extends SyntaxPlugin
{
    /** @var string[] */
    protected $allowedTypes = ['original', 'reference', 'self', 'section'];

    /**
     * @return string
     */
    public function getType()
    {
        return 'substition';
    }

    /**
     * @return string
     */
    public function getPType()
    {
        return 'normal';
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return 999;
    }

    /**
     * No wiki syntax; instructions are injected by the action component.
     *
     * @param string $mode
     * @return void
     */
    public function connectTo($mode)
    {
    }

    /**
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Handler $handler
     * @return array|bool
     */
    public function handle($match, $state, $pos, Handler $handler)
    {
        return false;
    }

    /**
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data
     * @return bool
     */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        if (!is_array($data) || empty($data['kind']) || empty($data['type'])) {
            return false;
        }

        $kind = $data['kind'];
        $args = $data['args'] ?? [];
        if (!is_array($args)) {
            return false;
        }

        if ($format === 'xhtml') {
            if ($this->isViewerDependent()) {
                $renderer->info['cache'] = false;
            }
            $this->renderXhtml($renderer, $kind, $data['type'], $args);
            return true;
        }

        if (method_exists($renderer, $kind)) {
            call_user_func_array([$renderer, $kind], $args);
            return true;
        }

        return false;
    }

    /**
     * @param Doku_Renderer $renderer
     * @param string $kind
     * @param string $type
     * @param array $args
     * @return void
     */
    protected function renderXhtml(Doku_Renderer $renderer, $kind, $type, array $args)
    {
        $type = $this->displayType($type);
        $html = $this->renderLinkHtml($renderer, $kind, $args);
        if ($html === '') {
            return;
        }

        if ($this->shouldMark($type)) {
            $html = $this->annotateHtml($html, $type);
        }

        $renderer->doc .= $html;
    }

    /**
     * Fold self/section into reference unless classification_mode is full.
     *
     * @param string $type
     * @return string
     */
    protected function displayType($type)
    {
        if (!in_array($type, $this->allowedTypes, true)) {
            return 'reference';
        }
        if ($this->getConf('classification_mode') !== 'full') {
            if ($type === 'self' || $type === 'section') {
                return 'reference';
            }
        }
        return $type;
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function shouldMark($type)
    {
        if (!$this->viewerShouldSeeMarks()) {
            return false;
        }
        if ($type === 'original' && !(int)$this->getConf('mark_originals')) {
            return false;
        }
        return in_array($type, $this->allowedTypes, true);
    }

    /**
     * True when HTML depends on the current user (must not be cached).
     *
     * @return bool
     */
    protected function isViewerDependent()
    {
        $showTo = trim((string)$this->getConf('show_to'));
        return $showTo !== '' && strtolower($showTo) !== 'all';
    }

    /**
     * Visibility is decided on the server. Default "all" skips auth checks.
     *
     * @return bool
     */
    protected function viewerShouldSeeMarks()
    {
        $showTo = trim((string)$this->getConf('show_to'));
        if ($showTo === '' || strtolower($showTo) === 'all') {
            return true;
        }

        global $INPUT;
        $user = isset($INPUT) ? $INPUT->server->str('REMOTE_USER') : '';

        $key = strtolower($showTo);
        if ($key === 'logged_in') {
            return $user !== '';
        }
        if ($key === 'manager') {
            return auth_ismanager();
        }
        if ($key === 'admin') {
            return auth_isadmin();
        }

        global $USERINFO;
        $groups = $USERINFO['grps'] ?? [];
        if (!is_array($groups)) {
            return false;
        }
        foreach (preg_split('/\s*,\s*/', $showTo, -1, PREG_SPLIT_NO_EMPTY) as $group) {
            if (in_array($group, $groups, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Doku_Renderer $renderer
     * @param string $kind
     * @param array $args
     * @return string
     */
    protected function renderLinkHtml(Doku_Renderer $renderer, $kind, array $args)
    {
        if ($kind === 'internallink' && method_exists($renderer, 'internallink')) {
            return (string)$renderer->internallink($args[0] ?? '', $args[1] ?? null, null, true);
        }
        if ($kind === 'camelcaselink' && method_exists($renderer, 'camelcaselink')) {
            return (string)$renderer->camelcaselink($args[0] ?? '', true);
        }
        if ($kind === 'locallink' && method_exists($renderer, 'locallink')) {
            return (string)$renderer->locallink($args[0] ?? '', $args[1] ?? null, true);
        }
        return '';
    }

    /**
     * Add type class and compose title with the existing tooltip.
     *
     * @param string $html
     * @param string $type
     * @return string
     */
    protected function annotateHtml($html, $type)
    {
        $class = 'justareference-' . $type;
        $label = $this->getLang('type_' . $type);
        if ($label === '' || $label === 'type_' . $type) {
            $label = $type;
        }
        $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!preg_match('/<a\b/i', $html)) {
            return $html;
        }

        if (preg_match('/<a\b[^>]*\bclass="/i', $html)) {
            $html = preg_replace(
                '/(<a\b[^>]*\bclass=")([^"]*)(")/i',
                '$1$2 ' . $class . '$3',
                $html,
                1
            );
        } else {
            $html = preg_replace('/<a\b/i', '<a class="' . $class . '"', $html, 1);
        }

        if (preg_match('/<a\b[^>]*\btitle="([^"]*)"/i', $html, $match)) {
            $composed = $match[1] . ' – ' . $labelEsc;
            $html = preg_replace(
                '/(<a\b[^>]*\btitle=")([^"]*)(")/i',
                '$1' . $composed . '$3',
                $html,
                1
            );
        } else {
            $html = preg_replace(
                '/<a\b/i',
                '<a title="' . $labelEsc . '"',
                $html,
                1
            );
        }

        return $html;
    }
}
