<?php
/**
 * Motor Town Web Interface (MTWI) - Konfigurationsvorlage
 * Diese Datei wird vom Setup-Assistenten als Grundlage verwendet
 */

$config = array (
  'permissions' => 
  array (
    'roles' => 
    array (
      'master' => 
      array (
        'name' => 'Master Admin',
        'permissions' => 
        array (
          'dashboard_view' => true,
          'dashboard_server_control' => true,
          'player_ban' => true,
          'player_kick' => true,
          'player_view' => true,
          'chat_view' => true,
          'chat_send' => true,
          'player_list' => true,
          'player_history' => true,
          'ban_list' => true,
          'server_stats' => true,
          'settings_view' => true,
          'settings_account' => true,
          'settings_server' => true,
          'settings_api' => true,
          'settings_admins' => true,
          'settings_roles' => true,
          'admin_add' => true,
          'admin_edit' => true,
          'admin_delete' => true,
          'master_admin_edit' => true,
        ),
      ),
      'admin' => 
      array (
        'name' => 'Administrator',
        'permissions' => 
        array (
          'dashboard_view' => true,
          'dashboard_server_control' => true,
          'player_ban' => true,
          'player_kick' => true,
          'player_view' => true,
          'chat_view' => true,
          'chat_send' => true,
          'player_list' => true,
          'player_history' => true,
          'ban_list' => true,
          'server_stats' => true,
          'settings_view' => true,
          'settings_account' => true,
          'settings_server' => true,
          'settings_api' => true,
          'settings_admins' => true,
          'settings_roles' => true,
          'admin_add' => true,
          'admin_edit' => true,
          'admin_delete' => true,
          'master_admin_edit' => false,
        ),
      ),
      'moderator' => 
      array (
        'name' => 'Moderator',
        'permissions' => 
        array (
          'dashboard_view' => true,
          'dashboard_server_control' => false,
          'player_ban' => true,
          'player_kick' => true,
          'player_view' => true,
          'chat_view' => true,
          'chat_send' => true,
          'player_list' => true,
          'player_history' => true,
          'ban_list' => true,
          'server_stats' => true,
          'settings_view' => true,
          'settings_account' => true,
          'settings_server' => false,
          'settings_api' => true,
          'settings_admins' => false,
          'settings_roles' => false,
          'admin_add' => false,
          'admin_edit' => false,
          'admin_delete' => false,
          'master_admin_edit' => false,
        ),
      ),
      'viewer' => 
      array (
        'name' => 'Betrachter',
        'permissions' => 
        array (
          'dashboard_view' => true,
          'dashboard_server_control' => false,
          'player_ban' => false,
          'player_kick' => false,
          'player_view' => true,
          'chat_view' => true,
          'chat_send' => false,
          'player_list' => true,
          'player_history' => true,
          'ban_list' => false,
          'server_stats' => true,
          'settings_view' => true,
          'settings_account' => true,
          'settings_server' => false,
          'settings_api' => false,
          'settings_admins' => false,
          'settings_roles' => false,
          'admin_add' => false,
          'admin_edit' => false,
          'admin_delete' => false,
          'master_admin_edit' => false,
        ),
      ),
    ),
  ),
  'steam_api' => 
  array (
    'key' => '',
    'enable_profiles' => true,
  ),
  'api' => 
  array (
    'url' => '',
    'password' => '',
  ),
  'master_admin' => 
  array (
    'username' => '',
    'password' => '',
  ),
  'admins' => array(),
  'settings' => 
  array (
    'default_language' => 'de',
    'default_theme' => 'light',
    'refresh_interval' => 10,
    'setup_completed' => false,
  ),
  'database' => 
  array (
    'type' => 'sqlite',
    'file' => './data/mtwi.sqlite',
    'host' => 'localhost',
    'name' => 'mtwi',
    'user' => '',
    'pass' => '',
  ),
  'chat_server' => 
  array (
    'username' => 'admin',
    'url' => '',
    'port' => '',
    'password' => '',
  ),
);
