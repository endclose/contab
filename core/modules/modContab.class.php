<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *	\file       htdocs/core/modules/modContab.class.php
 *  \ingroup    product
 *  \brief      Page accueil des produits et services
 */

class modContab extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        $this->numero = 500000;

        $this->rights_class = 'contab';

        $this->family = 'financial';

        $this->module_position = 90;

        $this->name = preg_replace('/^mod/i', '', get_class($this));

        $this->description = 'Módulo de Contabilidad';

        $this->editor_name = 'Be-tech';
        $this->editor_url = 'http://www.be-tech.mx';

        $this->version = '1.0';

        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        $this->picto = 'contabilidad@contab';

        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 0,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 1,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 0,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                //    '/custom/contab/css/contab.css',
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(
                //   '/timbradomexico/js/timbradomexico.js.php',
            ),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(
                // 'data' => array(
                // 	'path',
                // ),
                // 'entity' => $conf->entity,
            ),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );

        $this->dirs = array("/contab/temp");
        $this->config_page_url = array("setup.php@contab");

        // TODO: Add const for configuration of module
        $this->const = array();

        if (!isset($conf->contab) || !isset($conf->contab->enabled)) {
            $conf->contab = new stdClass();
            $conf->contab->enabled = 0;
        }

        $this->tabs = array();

        $this->dictionaries = array();

        $this->boxes = array();

        $this->cronjobs = array();

        $this->rights = array();

        $this->menu = array();

        $this->menu[] = array(
            'fk_menu' => '', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'top', // This is a Top menu entry
            'titre' => 'Contabilidad', // Menu title
            'prefix' => img_picto('', 'fa-search-dollar', 'class="paddingright pictofixedwidth valignmiddle"'),
            'mainmenu' => 'contab',
            'leftmenu' => '',
            'url' => 'custom/contab/index.php',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1000 + 1,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 2, // 0=Menu for internal users, 1=external users, 2=both

        );
        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=contab', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Cuentas Contables', // Menu title
            'prefix' => img_picto('', 'fa-file-invoice', 'class="paddingright pictofixedwidth valignmiddle infobox-commande"'),
            'mainmenu' => 'contab',
            'leftmenu' => 'cuentas',
            'url' => 'custom/contab/index.php',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1001,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 2, // 0=Menu for internal users, 1=external users, 2=both

        );
        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=cuentas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'List', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_cuentas_list',
            'url' => '/custom/contab/cuentas/list.php?type=0',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1002,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both

        );
        $this->menu[]= array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=cuentas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Agregar cuenta', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_cuentas_new',
            'url' => '/custom/contab/cuentas/card.php?mode=2',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1003,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both

        );
        $this->menu[]= array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=cuentas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Auxiliar de cuentas', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_cuentas_aux',
            'url' => '/custom/contab/cuentas/listaux.php?mode=3',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1004,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both

        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=contab', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Polizas', // Menu title
            'prefix' => img_picto('', 'fa-file-invoice-dollar', 'class="paddingright pictofixedwidth valignmiddle infobox-commande"'),
            'mainmenu' => 'contab',
            'leftmenu' => 'polizas',
            'url' => '/custom/contab/polizas/index.php',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1005,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=polizas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'List', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_polizas_list',
            'url' => '/custom/contab/polizas/list.php?type=0',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1006,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=polizas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Agregar poliza', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_polizas_new',
            'url' => '/custom/contab/polizas/card.php?mode=2',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1007,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both
        );

        $this->menu[]= array(
            'fk_menu' => 'fk_mainmenu=contab,fk_leftmenu=polizas', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type' => 'left', // This is a Left menu entry
            'titre' => 'Movimientos', // Menu title
            'mainmenu' => 'contab',
            'leftmenu' => 'contab_polizas_mov',
            'url' => '/custom/contab/polizas/listmov.php?type=0',
            'langs' => 'contab@contab', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1008,
            'enabled' => 'isModEnabled("contab")', // Define condition to show or hide menu entry. Use 'isModEnabled("contab")' if entry must be visible if module is enabled.
            'perms' => '1', // Use 'perms'=>'$user->hasRight("contab", "myobject", "read")' if you want your menu with a permission rules
            'target' => '',
            'user' => 0, // 0=Menu for internal users, 1=external users, 2=both
        );


        $this->config_page_url = array("setup.php@contab");

    }

    public function init($options = '')
    {
        global $conf, $langs;

        $result = $this->_load_tables('/contab/sql/');
        if ($result < 0) {
            return -1;
        }

        $this->remove($options);
        $sql = array();


        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
