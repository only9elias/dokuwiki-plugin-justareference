<?php

/**
 * Classify wiki-internal links as original, reference, self, or section
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  only9elias
 */

use dokuwiki\Extension\Plugin;
use dokuwiki\File\PageResolver;

class justareference_classifier
{
    public const TYPE_ORIGINAL = 'original';
    public const TYPE_REFERENCE = 'reference';
    public const TYPE_SELF = 'self';
    public const TYPE_SECTION = 'section';

    public const KIND_INTERNAL = 'internallink';
    public const KIND_CAMEL = 'camelcaselink';
    public const KIND_LOCAL = 'locallink';

    /** @var Plugin */
    protected $plugin;

    /**
     * @param Plugin $plugin plugin instance (for getConf)
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Classify a parser/handler call, or return null if it should stay unmarked.
     *
     * @param array $call handler instruction [name, args, pos]
     * @return array{kind:string,type:string,args:array}|null
     */
    public function classifyCall(array $call)
    {
        if (!isset($call[0], $call[1]) || !is_array($call[1])) {
            return null;
        }

        $name = $call[0];
        $args = $call[1];

        if ($name === self::KIND_INTERNAL) {
            return $this->classifyInternal($args);
        }
        if ($name === self::KIND_LOCAL) {
            return $this->classifyLocal($args);
        }
        if ($name === self::KIND_CAMEL && $this->plugin->getConf('include_camelcase')) {
            return $this->classifyCamel($args);
        }
        if (
            $name === 'plugin'
            && $this->plugin->getConf('include_autolink')
        ) {
            return $this->classifyAutolinkPlugin($args);
        }

        return null;
    }

    /**
     * Owner page ID for a non-root target, or null when the target is root.
     *
     * @param string $page cleaned page ID without hash
     * @return string|null
     */
    public function ownerOf($page)
    {
        $ns = getNS($page);
        if ($ns === false || $ns === '') {
            return null;
        }

        $mode = $this->plugin->getConf('owner_mode');
        if ($mode === 'start') {
            global $conf;
            $start = $conf['start'] ?? 'start';
            return $ns . ':' . $start;
        }
        if ($mode === 'nspage') {
            return $ns;
        }

        return $ns . ':' . noNS($ns);
    }

    /**
     * @param array $args internallink args [id, name]
     * @return array{kind:string,type:string,args:array}|null
     */
    protected function classifyInternal(array $args)
    {
        $rawId = $args[0] ?? '';
        $name = $args[1] ?? null;
        if ($this->isImageName($name)) {
            return null;
        }

        [$page, $hash] = $this->resolvePageAndHash((string)$rawId);
        $type = $this->typeForPageLink($page, $hash !== '');
        return [
            'kind' => self::KIND_INTERNAL,
            'type' => $type,
            'args' => [$rawId, $name],
        ];
    }

    /**
     * @param array $args locallink args [hash, name]
     * @return array{kind:string,type:string,args:array}|null
     */
    protected function classifyLocal(array $args)
    {
        $name = $args[1] ?? null;
        if ($this->isImageName($name)) {
            return null;
        }

        return [
            'kind' => self::KIND_LOCAL,
            'type' => self::TYPE_SECTION,
            'args' => [$args[0] ?? '', $name],
        ];
    }

    /**
     * @param array $args camelcaselink args [match]
     * @return array{kind:string,type:string,args:array}|null
     */
    protected function classifyCamel(array $args)
    {
        $match = (string)($args[0] ?? '');
        if ($match === '') {
            return null;
        }

        [$page, $hash] = $this->resolvePageAndHash($match);
        $type = $this->typeForPageLink($page, $hash !== '');
        return [
            'kind' => self::KIND_CAMEL,
            'type' => $type,
            'args' => [$match],
        ];
    }

    /**
     * Best-effort classification of autolink plugin instructions.
     *
     * @param array $pluginCall args for a plugin instruction [pluginname, data, state, match]
     * @return array{kind:string,type:string,args:array}|null
     */
    protected function classifyAutolinkPlugin(array $pluginCall)
    {
        $pluginName = (string)($pluginCall[0] ?? '');
        if ($pluginName === '' || !preg_match('/autolink/i', $pluginName)) {
            return null;
        }
        if ($pluginName === 'justareference') {
            return null;
        }

        $data = $pluginCall[1] ?? null;
        $rawId = $this->extractAutolinkId($data);
        if ($rawId === null) {
            return null;
        }

        [$page, $hash] = $this->resolvePageAndHash($rawId);
        $type = $this->typeForPageLink($page, $hash !== '');
        return [
            'kind' => self::KIND_INTERNAL,
            'type' => $type,
            'args' => [$rawId, null],
        ];
    }

    /**
     * @param mixed $data
     * @return string|null
     */
    protected function extractAutolinkId($data)
    {
        if (is_string($data) && $data !== '' && !$this->looksLikeUrl($data)) {
            return $data;
        }
        if (!is_array($data)) {
            return null;
        }
        foreach (['id', 'page', 'target', 'link'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key]) && !$this->looksLikeUrl($data[$key])) {
                return $data[$key];
            }
        }
        if (isset($data[0]) && is_string($data[0]) && $data[0] !== '' && !$this->looksLikeUrl($data[0])) {
            return $data[0];
        }
        return null;
    }

    /**
     * @param mixed $name
     * @return bool
     */
    protected function isImageName($name)
    {
        return is_array($name);
    }

    /**
     * @param string $id
     * @return bool
     */
    protected function looksLikeUrl($id)
    {
        return (bool)preg_match('#^[a-z0-9+\-.]+://#i', $id);
    }

    /**
     * @param string $rawId
     * @return array{0:string,1:string} [page, hash]
     */
    protected function resolvePageAndHash($rawId)
    {
        global $ID;

        $id = $rawId;
        $parts = explode('?', $id, 2);
        $id = $parts[0];

        $context = (string)$ID;
        $resolved = (new PageResolver($context))->resolveId($id);
        return sexplode('#', $resolved, 2, '');
    }

    /**
     * @param string $page
     * @param bool $hasHash
     * @return string
     */
    protected function typeForPageLink($page, $hasHash)
    {
        if ($hasHash) {
            return self::TYPE_SECTION;
        }

        global $ID;
        $current = (string)$ID;

        if ($page !== '' && $page === $current) {
            return self::TYPE_SELF;
        }

        $owner = $this->ownerOf($page);
        if ($owner !== null && $owner === $current) {
            return self::TYPE_ORIGINAL;
        }

        return self::TYPE_REFERENCE;
    }
}
