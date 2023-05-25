<?php
/**
 * Plugin Name: Whitelist User Signup
 * Plugin URI: https://github.com/dc4e/whitelist-user-signup
 * Description: A small plugin for creating a user signup whitelist.
 * Author: maybebernd
 * Version: 1.0.0
 * 
 */
namespace WUS;

defined( 'ABSPATH' ) || die();

// Registers the admin menu action hook for the backend settings sub menu and the option sanitize callback
if (
    \is_admin()
) {

    \add_action(
        'admin_menu',
        __NAMESPACE__ . '\add_settings_sub_page'
    );

    \add_action(
        'admin_init',
        __NAMESPACE__ . '\register_wus_settings'
    );

}

/**
 * Registers the settings of the signup whitelist
 * 
 * @since 1.0.0
 */
function register_wus_settings() {

    \register_setting(
        'wus-options',
        'wus_email_whitelist',
        [
            'type' => 'array',
            'description' => __( 'comma-separated list of E-Mails', 'whitelist-user-signup' ),
            'sanitize_callback' => __NAMESPACE__ . '\sanitize_email_whitelist',
            'show_in_rest' => false,
            'default' => ''
        ]
    );

}

/**
 * Sanitizes the transmitted emails
 * 
 * @since 1.0.0
 * 
 * @param string $email_list
 * @return array
 */
function sanitize_email_whitelist( $email_list ) {

    if ( empty( $email_list ) ) {
        return [];
    }

    if ( is_string( $email_list ) ) {
        $emails = explode( ',', $email_list );
    } else {
        $emails = $email_list;
    }

    $sanitized_emails = [];

    foreach( $emails as $email ) {

        if ( ! \is_email( $email ) ) {
            continue;
        }

        $sanitized_emails[] = \sanitize_email( trim( $email ) );

    }

    $sanitized_emails = array_unique( $sanitized_emails );

    return $sanitized_emails;

}

/**
 * Adds settings sub page
 * 
 * @since 1.0.0
 */
function add_settings_sub_page() {

    \add_options_page(
        \__( 'Signup Whitelist', 'whitelist-user-signup' ),
        \__( 'Signup Whitelist', 'whitelist-user-signup' ),
        'manage_options',
        'options_whitelist_user_signup',
        __NAMESPACE__ . '\display_options_page'
    );

}

// Adds the pre register email check filter
\add_filter(
    'pre_user_email',
    __NAMESPACE__ . '\validate_user_email',
    1,
    1
);

/**
 * Checks if the given user email is inside the whitelist.
 * 
 * @since 1.0.0
 * 
 * @param string $user_email
 * 
 * @return string|WP_Error
 */
function validate_user_email( string $user_email ) {

    if ( empty( $user_email ) ) {
        return '';
    }

    $email_whitelist = \get_option( 'wus_email_whitelist', [] );

    if ( ! in_array( $user_email, $email_whitelist ) ) {
        \wp_die( new \WP_Error( 'forbidden_user_email', __( 'Sorry, that email address is not allowed!', 'whitelist-user-signup' ) ) ); 
    }

    return $user_email;

}

/**
 * Displays the settings page html
 * 
 * @since 1.0.0
 */
function display_options_page() {

    $email_list = \get_option( 'wus_email_whitelist', [] );

    $email_list = implode( ',', $email_list );

    ?>
    <div id="wus-options-page-container">

        <h1>
            <?php \esc_html_e( 'Whitelist User Signup Settings', 'whitelist-user-signup' ); ?>
        </h1>

        <form action="options.php" method="post">
            <?php
            \settings_fields( 'wus-options' );
            \do_settings_sections( 'wus-options' );
            ?>

            <h5>
                <?php esc_html_e( 'E-Mail Whitelist', 'whitelist-user-signup' );?>
            </h5>
            <div style="display: flex; flex-direction: column; margin-right: 20px;">
                <label for="wus_email_whitelist">
                    <?php \esc_html_e( 'Enter a comma-separated list of E-Mails.', 'whitelist-user-signup' );?>
                </label>
                <textarea id="wus_email_whitelist" name="wus_email_whitelist"><?php echo \esc_html( $email_list );?></textarea>
            </div>


            <?php \submit_button(); ?>

        </form>

    </div>
    <?php

}