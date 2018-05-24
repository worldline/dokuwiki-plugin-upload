<?php
/**
 * Upload Action Plugin:   Handle Upload and temporarily disabling cache of page.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author    Franz Häfner <fhaefner@informatik.tu-cottbus.de>
 * @author    Randolf Rotta <rrotta@informatik.tu-cottbus.de>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once DOKU_PLUGIN . 'action.php';
require_once(DOKU_INC . 'inc/media.php');
require_once(DOKU_INC . 'inc/infoutils.php');

class action_plugin_upload extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
            'author' => 'Franz Häfner',
            'email' => 'fhaefner@informatik.tu-cottbus.de',
            'date' => '2010-09-07',
            'name' => 'upload plugin',
            'desc' => 'upload plugin can add a link to the media manager in your wikipage.
            			Basic syntax: {{upload>namespace|option1|option2}}
                Use @page@ as namespage to use ID of the actual page as namespace or @current@ to use the namespace the current page is in.',
            'url' => 'https://www.dokuwiki.org/plugin:upload',
        );
    }

    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, '_hook_function_cache');
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this, '_hook_function_upload');
    }

    function _hook_function_cache(&$event, $param) {
        if($_FILES['upload']['tmp_name']) {
            $event->preventDefault();
            $event->stopPropagation();
            $event->result = false;
        }

        $namespace = p_get_metadata($event->data->page, 'has_upload_form');
        if(!empty($namespace)) {
            $event->data->key .= '|ACL' . auth_quickaclcheck($namespace);
            $event->data->cache = getCacheName($event->data->key, $event->data->ext);
        }
    }

    function _hook_function_upload(&$event, $param) {
        global $lang;
        global $INPUT;
        // get namespace to display (either direct or from deletion order)
        $NS = $_POST['ns'];
        $NS = cleanID($NS);

        // check auth
        $AUTH = auth_quickaclcheck("$NS:*");
        if($AUTH < AUTH_UPLOAD  && $_FILES['upload']['tmp_name']) {
            msg($lang['uploadfail'], -1);
            return;
        }

        // handle upload
        if($_FILES['upload']['tmp_name']) {
            $_POST['mediaid'] = $INPUT->post->str('new_name');
            $JUMPTO = media_upload($NS, $AUTH);
            if($JUMPTO) {
                $NS = getNS($JUMPTO);
                $ID = $INPUT->post->str('page');
                $NS = getNS($ID);
            }
        }
    }
}
