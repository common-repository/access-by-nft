<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://accessbynft.com
 * @since      1.0.0
 *
 * @package    Web3devs_ABNFT
 * @subpackage Web3devs_ABNFT/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>Access by NFT Settings</h2>
    <table class="wp-list-table widefat fixed striped table-view-list pages">
        <thead>
            <tr>
                <th scope="col" id="network" class="manage-column column-primary">
                    Network
                </th>
                <th scope="col" id="contract" class="manage-column">
                    Token ID
                </th>
                <th scope="col" id="symbol" class="manage-column">
                    Label
                </th>
                <th scope="col" id="actions" class="manage-column">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!is_array($coins) || empty($coins)) : ?>
                <tr>
                    <td colspan="4">
                        <p>No tokens found.</p>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($coins as $key => $coin): ?>
                    <tr class="iedit author-self level-0 type-page status-draft hentry">
                        <td class="has-row-actions column-primary"><?php echo esc_html(strtoupper($coin['network'])); ?></td>
                        <td class="has-row-actions"><?php echo esc_html($coin['contract']); ?></td>
                        <td class="has-row-actions"><?php echo esc_html($coin['symbol']); ?></td>
                        <td class="has-row-actions">
                            <form method="POST" action="options.php">
                                <?php
                                    $option_name = 'web3devs_abnft_configured_coins_setting';
                                    settings_fields('web3devs_abnft_general_settings');
                                ?>
                                <input id="<?php echo esc_attr($option_name.'_remove_contract'); ?>" type="hidden" name="<?php echo esc_attr($option_name.'[remove][contract]'); ?>" value="<?php echo esc_attr($coin['contract']); ?>">
                                <?php submit_button('Remove'); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ;?>
            <?php endif ;?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-primary">Network</th>
                <th scope="col" class="manage-column">Contract address</th>
                <th scope="col" class="manage-column">Symbol</th>
                <th scope="col" class="manage-column">Actions</th>
            </tr>
        </tfoot>
    </table>
    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php
            settings_fields( 'web3devs_abnft_general_settings' );
            do_settings_sections( 'web3devs_abnft_general_settings' );
        ?>
        <?php submit_button('Add Token'); ?>
    </form>

    <form method="POST" action="options.php">
        <?php
            settings_fields( 'web3devs_abnft_denial_page_settings' );
            do_settings_sections( 'web3devs_abnft_denial_page_settings' );
        ?>
        Users without access to the token will be redirected to this page.
        <?php submit_button('Save denial page'); ?>
    </form>

    <form method="POST" action="options.php">
        <?php
            settings_fields( 'web3devs_abnft_api_key_settings' );
            do_settings_sections( 'web3devs_abnft_api_key_settings' );
        ?>
        Register at <a href="https://dashboard.accessbynft.com/" alt="https://dashboard.accessbynft.com/" target="_blank">https://dashboard.accessbynft.com/</a> and add Wordpress Integration to obtain API KEY.
        <?php submit_button('Save'); ?>
    </form>
</div>