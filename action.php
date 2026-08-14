<?php

/**
 * Action component for Just a Reference
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  only9elias
 */

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

class action_plugin_justareference extends ActionPlugin
{
    /**
     * @param EventHandler $controller
     * @return void
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook(
            'PARSER_HANDLER_DONE',
            'AFTER',
            $this,
            'handleParserDone',
            null,
            1000
        );
    }

    /**
     * Rewrite markable link instructions to the syntax component.
     *
     * @param Event $event
     * @param mixed $param
     * @return void
     */
    public function handleParserDone(Event $event, $param)
    {
        require_once __DIR__ . '/classifier.php';

        $handler = $event->data;
        if (!is_object($handler) || !isset($handler->calls) || !is_array($handler->calls)) {
            return;
        }

        $classifier = new justareference_classifier($this);
        foreach ($handler->calls as &$call) {
            if (!is_array($call)) {
                continue;
            }
            $classified = $classifier->classifyCall($call);
            if ($classified === null) {
                continue;
            }
            $pos = $call[2] ?? 0;
            $call = [
                'plugin',
                ['justareference', $classified, DOKU_LEXER_SPECIAL, ''],
                $pos,
            ];
        }
        unset($call);
    }
}
