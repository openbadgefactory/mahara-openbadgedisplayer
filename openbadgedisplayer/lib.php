<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2011 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage blocktype-openbadgedisplayer
 * @author     Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Discedum Oy http://discendum.com
 * @copyright  (C) 2011 Catalyst IT Ltd http://catalyst.net.nz
 *
 */


defined('INTERNAL') || die();

class PluginBlocktypeOpenbadgedisplayer extends SystemBlocktype {

    public static function single_only() {
        return false;
    }

    public static function get_title() {
        return get_string('title', 'blocktype.openbadgedisplayer');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.openbadgedisplayer');
    }

    public static function get_categories() {
        return array('external');
    }

    public static function get_viewtypes() {
        return array('portfolio', 'profile');
    }

    public static function get_backpack_source() {
        $source = get_config('openbadgedisplayer_source');
        if (empty($source)) {
            // default values
            $source = array(
                'backpack' => 'https://backpack.openbadges.org/',
                'passport' => null
            );
        }
        return $source;
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        if (empty($configdata) || !isset($configdata['badgegroup'])) {
            return;
        }

        $source = self::get_backpack_source();

        $host = $source['backpack'];
        $badgegroup = $configdata['badgegroup'];
        if (strpos($badgegroup, 'http') === 0) {
            list($proto, $url, $bid, $group) = explode(':', $badgegroup);
            $host = $proto . ':' . $url;
            $badgegroup = $bid . ':' . $group;
        }

        if ($editing) {
            list($bid, $group) = explode(':', $badgegroup);
            $res = mahara_http_request(array(CURLOPT_URL => $host . "displayer/{$bid}/groups.json"));
            $res = json_decode($res->data);
            if (!empty($res->groups)) {
                foreach ($res->groups AS $g) {
                    if ($badgegroup === $bid . ':' . $g->groupId) {
                        return hsc($g->name) . ' (' . get_string('nbadges', 'blocktype.openbadgedisplayer', $g->badges) . ')';
                    }
                }
            }
            return;
        }

        $smarty = smarty_core();
        $smarty->assign('baseurl', $host);
        $smarty->assign('id', $instance->get('id'));
        $smarty->assign('badgegroup', $badgegroup);
        return $smarty->fetch('blocktype:openbadgedisplayer:openbadgedisplayer.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        global $USER;

        $source = self::get_backpack_source();

        $configdata = $instance->get('configdata');

        $addresses = get_column('artefact_internal_profile_email', 'email', 'owner', $USER->id, 'verified', 1);

        $current_value = null;
        if (isset($configdata['badgegroup'])) {
            $current_value = $configdata['badgegroup'];
            if (substr_count($current_value, ':') == 1) {
                //legacy value, prepend host
                $current_value = $source['backpack'] . ':' . $current_value;
            }
        }

        $fields = array(
            'message' => array(
                'type' => 'html',
                'value' => '<p>'. get_string('confighelp', 'blocktype.openbadgedisplayer', $source['backpack']) .'</p>'
            ),
            'badgegroup' => array(
                'type' => 'radio',
                'options' => array(),
                'separator' => '<br>'
            ),
            'inlinejsformatter' => array(
                'type' => 'html',
                'value' => self::badgegroup_format()
            )
        );

        $fields['badgegroup']['options'] += self::get_form_fields($source['backpack'], $addresses);
        $fields['badgegroup']['options'] += self::get_form_fields($source['passport'], $addresses);

        if (empty($fields['badgegroup']['options'])) {
            return array(
                'colorcode' => array('type' => 'hidden', 'value' => ''),
                'title' => array('type' => 'hidden', 'value' => ''),
                'message' => array(
                    'type' => 'html',
                    'value' => '<p>'. get_string('nogroups', 'blocktype.openbadgedisplayer', $source['backpack']) .'</p>'
                )
            );
        }

        if ($current_value && isset($fields['badgegroup']['options'][$current_value])) {
            $default = $current_value;
        }
        else {
            list($default) = array_keys($fields['badgegroup']['options']);
        }

        $fields['badgegroup']['defaultvalue'] = $default;

        return $fields;
    }

    private static function badgegroup_format() {
        return <<<EOF
            <script type="text/javascript">
            (function ($) {
              $(function () {

                window.setTimeout( function () {
                    $('#instconf_badgegroup_container div.radio').each(function () {
                        var that = $(this);
                        if (that.find('input').val().match(/.+:0$/)) {
                            that.addClass('separator');
                        }
                    });
                }, 100);
              });
            })(jQuery)
            </script>
EOF;
    }


    private static function get_form_fields($host, $addresses) {
        if ( ! $host) {
            return array();
        }

        $backpackid = array();
        foreach ($addresses AS $email) {
            $backpackid[] = self::get_backpack_id($host, $email);
        }
        $backpackid = array_filter($backpackid);

        $opt = array();
        foreach ($backpackid AS $uid) {
            $opt += self::get_group_opt($host, $uid);
        }

        return $opt;

    }


    private static function get_backpack_id($host, $email) {
        $res = mahara_http_request(
            array(
                CURLOPT_URL        => $host . 'displayer/convert/email',
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => 'email=' . urlencode($email)
            )
        );
        $res = json_decode($res->data);
        if (isset($res->userId)) {
            return $res->userId;
        }
        return null;
    }


    private static function get_group_opt($host, $uid) {
        $opt = array();
        $res = mahara_http_request(array(CURLOPT_URL => $host . "displayer/{$uid}/groups.json"));
        $res = json_decode($res->data);
        if (!empty($res->groups)) {
            foreach ($res->groups AS $g) {
                if ($g->name == 'Public badges' && $g->groupId == 0) {
                    $name = get_string('obppublicbadges', 'blocktype.openbadgedisplayer');
                }
                else {
                    $name = hsc($g->name);
                }

                $name .= ' (' . get_string('nbadges', 'blocktype.openbadgedisplayer', $g->badges) . ')';
                $opt[$host . ':' . $uid . ':' . $g->groupId] = $name;
            }
        }
        return $opt;
    }


    public static function instance_config_save($values) {
        unset($values['message']);
        return $values;
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    public static function allowed_in_view(View $view) {
        return $view->get('owner') != null;
    }

    public static function get_instance_javascript() {
        return array(get_config('wwwroot') . 'js/preview.js');
    }

}
