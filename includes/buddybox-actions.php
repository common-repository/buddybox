<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


// BuddyPress / WordPress actions to BuddyBox ones
add_action( 'bp_init',               'buddybox_init',                    14 );
add_action( 'bp_ready',              'buddybox_ready',                   10 );
add_action( 'bp_setup_current_user', 'buddybox_setup_current_user',      10 );
add_action( 'bp_setup_theme',        'buddybox_setup_theme',             10 );
add_action( 'bp_after_setup_theme',  'buddybox_after_setup_theme',       10 );
add_action( 'bp_enqueue_scripts',    'buddybox_enqueue_scripts',         10 );
add_action( 'bp_setup_admin_bar',    'buddybox_setup_admin_bar',         10 );
add_action( 'bp_actions',            'buddybox_actions',                 10 );
add_action( 'bp_screens',            'buddybox_screens',                 10 );
add_action( 'admin_init',            'buddybox_admin_init',              10 );
add_action( 'admin_head',            'buddybox_admin_head',              10 );
add_action( 'buddybox_admin_init',   'buddybox_do_activation_redirect',  1  );
add_action( 'buddybox_admin_init',   'buddybox_admin_register_settings', 11 );


function buddybox_init(){
	do_action( 'buddybox_init' );
}

function buddybox_ready(){
	do_action( 'buddybox_ready' );
}

function buddybox_setup_current_user(){
	do_action( 'buddybox_setup_current_user' );
}

function buddybox_setup_theme(){
	do_action( 'buddybox_setup_theme' );
}

function buddybox_after_setup_theme(){
	do_action( 'buddybox_after_setup_theme' );
}

function buddybox_enqueue_scripts(){
	do_action( 'buddybox_enqueue_scripts' );
}

function buddybox_setup_admin_bar(){
	do_action( 'buddybox_setup_admin_bar' );
}

function buddybox_actions(){
	do_action( 'buddybox_actions' );
}

function buddybox_screens(){
	do_action( 'buddybox_screens' );
}

function buddybox_admin_init() {
	do_action( 'buddybox_admin_init' );
}

function buddybox_admin_head() {
	do_action( 'buddybox_admin_head' );
}

function buddybox_admin_register_settings() {
	do_action( 'buddybox_admin_register_settings' );
}

// Activation redirect
add_action( 'buddybox_activation', 'buddybox_add_activation_redirect' );