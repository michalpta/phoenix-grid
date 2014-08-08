<?
if (!is_array($result)):
    ?>
    <?= $result ?>
    <?
else:
    foreach ($result as $row):
        ?>
        <? $item = $row['value']; ?>
        <input type='checkbox' name='phoenix_grid_filter[]' value="<?= isset($item) ? $item : "phoenix_grid_null" ?>" <? if ($row['checked'] == TRUE) echo 'checked="checked" '; ?>/><?= $item ?><br />

        <?
    endforeach;
endif;
?>
