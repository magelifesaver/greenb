<?php

/**
 * @var string $title
 * @var array  $items
 */


$amount = $this->amount;
$items = $this->items;

$title_string = 
_n(
    __('%d Plugin','codepress-admin-columns'), 
    __('%d Plugins', 'codepress-admin-columns'), 
    $amount
);
$title = sprintf( $title_string, (int)$amount );
?>

<!-- TODO: Style the header -->
<h3><?= esc_html($title) ?></h3>

<table class="ac-table-items -clean -plugins">
    <thead>
        <tr>
            <th class="col-name"><?= __('Plugin Name') ?></th>
            <th class="col-network-activated"><?= __('Network Activated') ?></th>
            <th class="col-version"><?= __('Version') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($items as $item) : ?>
            <tr>
                <td class="col-name">
                    <?= esc_html($item['name']) ?>
                </td>
                <td class="col-network-activated">
                    <?= esc_html($item['is_network_active'])?>
                </td>
                <td class="col-version">
                    <?= esc_html($item['version']) ?>
                </td>
            </tr>
        <?php
        endforeach; ?>
    </tbody>
</table>